<?php

namespace Tests\Feature\Labo;

use App\Enums\Labo\StatutExamen;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Services\Labo\CompteRenduService;
use App\Services\Labo\PrelevementService;
use App\Services\Labo\ResultatService;
use App\Services\Labo\ValidationService;

class VerrouValidationTest extends LaboTestCase
{
    public function test_un_resultat_valide_par_le_biologiste_est_fige_jusqu_a_rectification(): void
    {
        $etab = $this->creerEtablissement('clinique-a');
        $bio = $this->creerBiologiste($etab);
        $demande = $this->creerDemande($bio, ['GLY']);

        foreach ($demande->echantillons as $e) {
            app(PrelevementService::class)->marquerPreleveEtRecu($e, $bio);
        }

        $ligne = $demande->examens()->first();
        $parametre = $ligne->examen->parametres->first();
        app(ResultatService::class)->enregistrer($ligne, [$parametre->id => '0,95'], $bio);
        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $bio);

        $ligne->refresh();
        $this->assertSame(StatutExamen::VALIDE_BIOLOGIQUE, $ligne->statut);

        // Verrou service
        try {
            app(ResultatService::class)->enregistrer($ligne, [$parametre->id => '1,20'], $bio);
            $this->fail('La saisie aurait dû être refusée');
        } catch (OperationLaboImpossible) {
        }

        // Verrou modèle : même un accès direct est bloqué
        $this->expectException(\LogicException::class);
        $ligne->resultats()->first()->update(['valeur_numerique' => 1.2]);
    }

    /** Prépare une glycémie saisie (0,95) et validée par le biologiste. */
    private function glycemieValidee(): array
    {
        $etab = $this->creerEtablissement('clinique-a');
        $bio = $this->creerBiologiste($etab);
        $demande = $this->creerDemande($bio, ['GLY']);
        foreach ($demande->echantillons as $e) {
            app(PrelevementService::class)->marquerPreleveEtRecu($e, $bio);
        }
        $ligne = $demande->examens()->first();
        $p = $ligne->examen->parametres->first();
        app(ResultatService::class)->enregistrer($ligne, [$p->id => '0,95'], $bio);
        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $bio);

        return [$bio, $demande, $ligne->fresh(), $p];
    }

    /**
     * Rouvrir AVANT publication : le patient n'a rien reçu, ce n'est pas une
     * rectification (pas de compteur, pas de compte rendu rectificatif).
     */
    public function test_correction_avant_publication_n_est_pas_une_rectification(): void
    {
        [$bio, , $ligne, $p] = $this->glycemieValidee();

        app(ValidationService::class)->rouvrirPourRectification($ligne, 'Erreur de saisie : 1,95', $bio);

        $ligne->refresh();
        $this->assertTrue($ligne->statut->permetSaisie());
        $this->assertSame(0, $ligne->nombre_rectifications);

        app(ResultatService::class)->enregistrer($ligne, [$p->id => '1,95'], $bio);
        $this->assertEquals(1.95, $ligne->resultats()->first()->valeur_numerique);
    }

    /**
     * Rouvrir APRÈS publication : rectification comptée, et la publication
     * suivante émet une v2 marquée rectificative, la v1 restant intacte.
     */
    public function test_rectification_apres_publication_emet_un_compte_rendu_rectificatif(): void
    {
        \Illuminate\Support\Facades\Bus::fake(); // pas de génération PDF ni de SMS pendant le test

        [$bio, $demande, $ligne, $p] = $this->glycemieValidee();
        $v1 = app(CompteRenduService::class)->publier($demande->fresh(), $bio, notifierPatient: false);
        $this->assertFalse($v1->est_rectificatif);

        app(ValidationService::class)->rouvrirPourRectification($ligne->fresh(), 'Erreur de saisie : 1,95', $bio);

        $ligne->refresh();
        $this->assertTrue($ligne->statut->permetSaisie());
        $this->assertSame(1, $ligne->nombre_rectifications);

        app(ResultatService::class)->enregistrer($ligne, [$p->id => '1,95'], $bio);
        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $bio);
        $v2 = app(CompteRenduService::class)->publier($demande->fresh(), $bio, notifierPatient: false);

        $this->assertSame(2, $v2->version);
        $this->assertTrue($v2->est_rectificatif);
        $this->assertStringContainsString('1,95', $v2->motif_rectification);
        $this->assertSame('0,95', $v1->fresh()->contenu['sections'][0]['examens'][0]['lignes'][0]['valeur'], 'la v1 émise reste inchangée');
    }
}
