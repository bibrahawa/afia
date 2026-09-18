<?php

namespace Tests\Feature\Labo;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Etablissement;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Patient;
use App\Models\User;
use App\Services\Labo\LaboReseauService;
use App\Services\Labo\PartenariatService;
use Database\Seeders\Labo\LaboReseauPermissionsSeeder;

/** Lot 4f — accord de la clinique au partenariat et trace du consentement du patient. */
class ReseauAccordTest extends LaboTestCase
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
        $this->medecin = $this->creerUtilisateur($this->clinique);

        $this->actingAs($this->biologiste);
        $this->partenariat = app(PartenariatService::class)->creer(
            $this->clinique,
            ['mode_facturation_defaut' => 'patient', 'remise_pourcentage' => 5],
            $this->biologiste
        );

        LaboExamen::withoutGlobalScopes()
            ->where('etablissement_id', $this->labo->id)
            ->where('code', 'GLY')
            ->update(['prix' => 20000]);
    }

    public function test_un_partenariat_nait_en_attente_de_la_clinique(): void
    {
        $this->assertSame(LaboPartenariat::PROPOSE, $this->partenariat->statut);
        $this->assertNotNull($this->partenariat->propose_le);

        // Tant qu'il n'est pas accepté, il n'apparaît pas dans les partenaires actifs.
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);

        $this->assertSame(0, $reseau->partenaires()->count());
        $this->assertSame(1, $reseau->propositions()->count());
    }

    public function test_envoi_refuse_avant_acceptation(): void
    {
        $this->actingAs($this->medecin);

        $this->expectException(OperationLaboImpossible::class);
        app(LaboReseauService::class)->partenariat($this->partenariat->id);
    }

    public function test_le_laboratoire_ne_peut_pas_suspendre_une_proposition(): void
    {
        $this->actingAs($this->biologiste);

        $this->expectException(OperationLaboImpossible::class);
        app(PartenariatService::class)->basculerStatut($this->partenariat);
    }

    public function test_acceptation_puis_envoi(): void
    {
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);

        $accepte = $reseau->accepterProposition($this->partenariat->id, $this->medecin);

        $this->assertSame(LaboPartenariat::ACTIF, $accepte->statut);
        $this->assertNotNull($accepte->accepte_le);
        $this->assertSame($this->medecin->id, (int) $accepte->accepte_par);

        $demande = $this->envoyer(['GLY'], ['consentement_partage' => true]);

        $this->assertSame($this->labo->id, (int) $demande->etablissement_id);
        $this->assertNotNull(LaboDemande::withoutGlobalScopes()->findOrFail($demande->id)->consentement_partage_le);
    }

    public function test_refus_avec_motif(): void
    {
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);

        $refuse = $reseau->refuserProposition($this->partenariat->id, 'Tarifs trop élevés');

        $this->assertSame(LaboPartenariat::REFUSE, $refuse->statut);
        $this->assertSame('Tarifs trop élevés', $refuse->motif_refus);
        $this->assertSame(0, $reseau->propositions()->count());
        $this->assertSame(0, $reseau->partenaires()->count());
    }

    public function test_consentement_non_coche_laisse_la_trace_vide(): void
    {
        $this->actingAs($this->medecin);
        app(LaboReseauService::class)->accepterProposition($this->partenariat->id, $this->medecin);

        $demande = $this->envoyer(['GLY'], ['consentement_partage' => false]);

        $this->assertNull(LaboDemande::withoutGlobalScopes()->findOrFail($demande->id)->consentement_partage_le);
    }

    private function envoyer(array $codes, array $options = []): LaboDemande
    {
        $patient = Patient::create(['first_name' => 'Aïssatou', 'last_name' => 'Diallo', 'gender' => 'Femme', 'birth_date' => '1994-04-04']);
        $this->clinique->patients()->syncWithoutDetaching([$patient->id]);

        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $examens = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->whereIn('code', $codes)->pluck('id')->all();

        return $reseau->envoyer(
            $reseau->partenariat($this->partenariat->id),
            $patient,
            $options + ['examens' => $examens],
            $this->medecin
        );
    }
}
