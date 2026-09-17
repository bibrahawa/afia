<?php

namespace Tests\Feature\Labo;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboDeclarationMdo;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboRemise;
use App\Models\User;
use App\Services\Labo\CompteRenduService;
use App\Services\Labo\DeclarationMdoService;
use App\Services\Labo\PrelevementService;
use App\Services\Labo\RemiseService;
use App\Services\Labo\ResultatService;
use App\Services\Labo\ValidationService;
use Illuminate\Support\Facades\Bus;

class RemiseEtDeclarationTest extends LaboTestCase
{
    private User $bio;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        $this->bio = $this->creerBiologiste($this->creerEtablissement('clinique-a'));
    }

    /** Demande saisie, validée et publiée pour un examen, avec la valeur donnée au premier paramètre. */
    private function demandePubliee(string $code, string $valeur): LaboDemande
    {
        $demande = $this->creerDemande($this->bio, [$code]);
        foreach ($demande->echantillons as $e) {
            app(PrelevementService::class)->marquerPreleveEtRecu($e, $this->bio);
        }
        $ligne = $demande->examens()->first();
        $saisies = $ligne->examen->parametres->mapWithKeys(fn ($p) => [$p->id => $valeur])->all();
        app(ResultatService::class)->enregistrer($ligne, $saisies, $this->bio);
        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $this->bio);
        app(CompteRenduService::class)->publier($demande->fresh(), $this->bio, notifierPatient: false);

        return $demande->fresh();
    }

    public function test_remise_au_patient_tracee_et_immuable(): void
    {
        $demande = $this->demandePubliee('GLY', '0,95');

        $remise = app(RemiseService::class)->remettre($demande, ['beneficiaire' => 'patient', 'piece_justificative' => 'cni'], $this->bio);

        $this->assertSame(1, $remise->version);
        $this->assertSame($demande->patient->full_name, $remise->nom_beneficiaire);
        $this->assertFalse($remise->avant_reglement);

        $this->expectException(\LogicException::class);
        $remise->update(['nom_beneficiaire' => 'Autre']);
    }

    public function test_representant_sans_lien_ni_piece_refuse(): void
    {
        $demande = $this->demandePubliee('GLY', '0,95');

        $this->expectException(OperationLaboImpossible::class);
        app(RemiseService::class)->remettre($demande, ['beneficiaire' => 'representant', 'nom_beneficiaire' => 'Mamadou Bah'], $this->bio);
    }

    public function test_impaye_remise_bloquee_sans_motif_mais_libre_pour_le_prescripteur(): void
    {
        $demande = $this->demandePubliee('GLY', '0,95');
        $demande->forceFill(['mode_facturation' => 'labo', 'resultats_retenus_si_impaye' => true])->save(); // impayé : aucune transaction

        try {
            app(RemiseService::class)->remettre($demande, ['beneficiaire' => 'patient'], $this->bio);
            $this->fail('Remise avant règlement sans motif : aurait dû être refusée');
        } catch (OperationLaboImpossible) {
        }

        $avecMotif = app(RemiseService::class)->remettre($demande, ['beneficiaire' => 'patient', 'motif_derogation' => 'Urgence'], $this->bio);
        $this->assertTrue($avecMotif->avant_reglement);

        $prescripteur = app(RemiseService::class)->remettre($demande, ['beneficiaire' => 'prescripteur', 'nom_beneficiaire' => 'Dr Barry'], $this->bio);
        $this->assertFalse($prescripteur->avant_reglement);
        $this->assertSame(2, LaboRemise::count());
    }

    public function test_resultat_positif_ouvre_une_declaration_et_resultat_negatif_non(): void
    {
        $this->assertNotNull(LaboExamen::where('code', 'TDR_PALU')->value('mdo_maladie'), 'catalogue importé avec la MDO');

        $positif = $this->demandePubliee('TDR_PALU', 'Positif (P. falciparum)');
        $this->demandePubliee('TDR_PALU', 'Négatif');
        $this->demandePubliee('TDR_PALU', 'Invalide');

        $this->assertSame(1, LaboDeclarationMdo::count());
        $declaration = LaboDeclarationMdo::first();
        $this->assertSame('Paludisme', $declaration->maladie);
        $this->assertSame($positif->examens()->first()->id, $declaration->demande_examen_id);

        app(DeclarationMdoService::class)->marquerDeclaree($declaration, ['destinataire' => 'Point focal DPS', 'reference' => 'FN-12'], $this->bio);
        $this->assertSame(LaboDeclarationMdo::DECLAREE, $declaration->fresh()->statut);
    }

    public function test_rectification_vers_negatif_rend_la_declaration_sans_objet(): void
    {
        $demande = $this->demandePubliee('TDR_PALU', 'Positif (P. falciparum)');
        $ligne = $demande->examens()->first();
        $p = $ligne->examen->parametres->first();

        app(ValidationService::class)->rouvrirPourRectification($ligne, 'Erreur de patient', $this->bio);
        app(ResultatService::class)->enregistrer($ligne->fresh(), [$p->id => 'Négatif'], $this->bio);
        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $this->bio);

        $this->assertSame(LaboDeclarationMdo::SANS_OBJET, LaboDeclarationMdo::first()->statut);
    }
}
