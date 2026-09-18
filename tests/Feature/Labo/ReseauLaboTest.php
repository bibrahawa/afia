<?php

namespace Tests\Feature\Labo;

use App\Enums\Labo\ModeFacturation;
use App\Enums\Labo\OrigineDemande;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Etablissement;
use App\Models\Labo\LaboCompteRendu;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Patient;
use App\Models\User;
use App\Services\Labo\LaboReseauService;
use App\Services\Labo\PartenariatService;
use Database\Seeders\Labo\LaboReseauPermissionsSeeder;

/**
 * Lot 4a — laboratoire en réseau : une clinique sans laboratoire envoie ses
 * analyses à un laboratoire partenaire et récupère les comptes rendus.
 */
class ReseauLaboTest extends LaboTestCase
{
    private Etablissement $labo;
    private Etablissement $clinique;
    private User $biologiste;
    private User $medecin;
    private LaboPartenariat $partenariat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LaboReseauPermissionsSeeder::class);

        $this->labo = $this->creerEtablissement('labo-central');
        $this->clinique = $this->creerEtablissement('clinique-nord');
        $this->biologiste = $this->creerBiologiste($this->labo);
        $this->medecin = $this->creerUtilisateur($this->clinique); // les droits sont portés par les routes, pas par les services

        $this->actingAs($this->biologiste);
        $this->partenariat = app(PartenariatService::class)->creer(
            $this->clinique,
            ['mode_facturation_defaut' => 'partenaire', 'remise_pourcentage' => 10],
            $this->biologiste
        );

        // Le catalogue modèle est livré à 0 : chaque laboratoire fixe ses tarifs.
        LaboExamen::withoutGlobalScopes()
            ->where('etablissement_id', $this->labo->id)
            ->whereIn('code', ['GLY', 'NFS'])
            ->each(fn (LaboExamen $examen) => $examen->forceFill(['prix' => $examen->code === 'GLY' ? 20000 : 30000])->save());

        // La clinique doit accepter la proposition avant tout envoi (lot 4f).
        $this->actingAs($this->medecin);
        $this->partenariat = app(LaboReseauService::class)->accepterProposition($this->partenariat->id, $this->medecin);


    }

    public function test_la_clinique_voit_le_catalogue_du_partenaire_au_prix_negocie(): void
    {
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);

        $partenariat = $reseau->partenariat($this->partenariat->id);
        $catalogue = $reseau->catalogue($partenariat);

        $this->assertTrue($catalogue->isNotEmpty());

        $glycemie = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->where('code', 'GLY')->firstOrFail();
        $ligne = $catalogue->firstWhere('id', $glycemie->id);

        $this->assertSame(round((float) $glycemie->prix * 0.9), $ligne['prix']);
    }

    public function test_envoi_d_une_demande_au_laboratoire(): void
    {
        $patient = $this->patientDeLaClinique();
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);

        $examens = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->whereIn('code', ['GLY'])->pluck('id')->all();
        $demande = $reseau->envoyer($reseau->partenariat($this->partenariat->id), $patient, [
            'examens' => $examens,
            'renseignements_cliniques' => 'Suspicion de diabète',
        ], $this->medecin);

        // La demande appartient au laboratoire, la clinique en est le prescripteur.
        $this->assertSame($this->labo->id, (int) $demande->etablissement_id);
        $this->assertSame($this->clinique->id, (int) $demande->etablissement_prescripteur_id);
        $this->assertSame($this->partenariat->id, (int) $demande->partenariat_id);
        $this->assertSame(OrigineDemande::EXTERNE, $demande->origine);
        $this->assertSame(ModeFacturation::PARTENAIRE, $demande->mode_facturation);
        $this->assertSame(1, $demande->examens()->withoutGlobalScopes()->count());

        // Mode partenaire : le laboratoire n'encaisse pas le patient.
        $this->assertNull($demande->transaction()->withoutGlobalScopes()->first());

        // Le laboratoire la voit dans sa propre liste, la clinique dans ses envois.
        $this->actingAs($this->biologiste);
        $this->assertTrue(LaboDemande::whereKey($demande->id)->exists());

        $this->actingAs($this->medecin);
        $this->assertSame(1, $reseau->demandesEnvoyees()->count());
    }

    public function test_une_autre_clinique_ne_voit_rien(): void
    {
        $patient = $this->patientDeLaClinique();
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $examens = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->where('code', 'GLY')->pluck('id')->all();
        $demande = $reseau->envoyer($reseau->partenariat($this->partenariat->id), $patient, ['examens' => $examens], $this->medecin);

        $autreClinique = $this->creerEtablissement('clinique-sud');
        $autreMedecin = $this->creerUtilisateur($autreClinique);
        $this->actingAs($autreMedecin);

        $this->assertSame(0, app(LaboReseauService::class)->demandesEnvoyees()->count());
        $this->assertSame(0, app(LaboReseauService::class)->partenaires()->count());
    }

    public function test_partenariat_suspendu_bloque_l_envoi(): void
    {
        $this->actingAs($this->biologiste);
        app(PartenariatService::class)->basculerStatut($this->partenariat);

        $this->actingAs($this->medecin);

        $this->expectException(OperationLaboImpossible::class);
        app(LaboReseauService::class)->partenariat($this->partenariat->id);
    }

    public function test_examen_hors_catalogue_refuse(): void
    {
        $patient = $this->patientDeLaClinique();
        $autreLabo = $this->creerEtablissement('labo-autre');
        $examenEtranger = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $autreLabo->id)->where('code', 'GLY')->firstOrFail();

        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);

        $this->expectException(OperationLaboImpossible::class);
        $reseau->envoyer($reseau->partenariat($this->partenariat->id), $patient, ['examens' => [$examenEtranger->id]], $this->medecin);
    }

    public function test_compte_rendu_publie_lisible_par_la_clinique(): void
    {
        $patient = $this->patientDeLaClinique();
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $examens = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->where('code', 'GLY')->pluck('id')->all();
        $demande = $reseau->envoyer($reseau->partenariat($this->partenariat->id), $patient, ['examens' => $examens], $this->medecin);

        // La création est refusée hors du contexte du laboratoire : c'est sa donnée.
        $this->actingAs($this->biologiste);

        LaboCompteRendu::withoutGlobalScopes()->create([
            'etablissement_id' => $this->labo->id,
            'demande_id' => $demande->id,
            'version' => 1,
            'contenu' => ['examens' => []],
            'empreinte' => str_repeat('a', 32),
            'publie_par' => $this->biologiste->id,
            'publie_le' => now(),
        ]);

        $this->actingAs($this->medecin);
        $compteRendu = $reseau->compteRendu($reseau->demandePrescrite($demande->id));

        $this->assertNotNull($compteRendu);
        $this->assertSame(1, (int) $compteRendu->version);
    }

    private function patientDeLaClinique(): Patient
    {
        $patient = $this->creerPatient();
        $this->clinique->patients()->syncWithoutDetaching([$patient->id]);

        return $patient;
    }
}
