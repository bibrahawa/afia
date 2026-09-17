<?php

namespace Tests\Feature\Labo;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Etablissement;
use App\Models\Labo\LaboCreancePartenaire;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Patient;
use App\Models\User;
use App\Services\Labo\LaboReseauService;
use App\Services\Labo\PartenariatService;
use App\Services\Labo\PrelevementService;
use Database\Seeders\Labo\LaboReseauPermissionsSeeder;

/** Lot 4d — corrections du réseau : lectures hors cloisonnement, bon d'analyses, annulation par la clinique. */
class ReseauCorrectionsTest extends LaboTestCase
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
            ['mode_facturation_defaut' => 'partenaire', 'remise_pourcentage' => 10],
            $this->biologiste
        );

        // Le catalogue modèle est livré à 0 : chaque laboratoire fixe ses tarifs.
        LaboExamen::withoutGlobalScopes()
            ->where('etablissement_id', $this->labo->id)
            ->whereIn('code', ['GLY', 'NFS'])
            ->each(fn (LaboExamen $examen) => $examen->forceFill(['prix' => $examen->code === 'GLY' ? 20000 : 30000])->save());

        // L'écran du bon d'analyses passe par la permission du réseau.
        $this->medecin->givePermissionTo(['labo.reseau.view', 'labo.reseau.demander']);

    }

    public function test_la_clinique_voit_le_nom_du_laboratoire_et_les_examens(): void
    {
        $demande = $this->envoyer(['GLY', 'NFS']);

        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);

        $ligne = $reseau->demandesEnvoyees()->first();
        $this->assertSame($this->labo->nom, $ligne->etablissement?->nom);
        $this->assertSame(2, $ligne->examens->count());

        $detail = $reseau->demandePrescrite($demande->id);
        $this->assertSame($this->labo->nom, $detail->partenariat?->laboratoire?->nom);
        $this->assertNotNull($detail->examens->first()->examen);

        $this->assertSame(1, $reseau->partenaires()->count());
        $this->assertSame($this->labo->nom, $reseau->partenaires()->first()->laboratoire?->nom);
    }

    public function test_le_prescripteur_est_lisible_par_le_laboratoire(): void
    {
        $demande = $this->envoyer(['GLY']);

        $this->actingAs($this->biologiste);
        $vueLabo = LaboDemande::findOrFail($demande->id);

        $this->assertStringContainsString($this->clinique->nom, (string) $vueLabo->prescripteur_externe);
    }

    public function test_la_creance_suit_le_prix_negocie_ligne_par_ligne(): void
    {
        $demande = $this->envoyer(['GLY', 'NFS']);

        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $catalogue = $reseau->catalogue($reseau->partenariat($this->partenariat->id));

        $attendu = $demande->examens()->withoutGlobalScopes()->get()
            ->sum(fn ($ligne) => $catalogue->firstWhere('id', $ligne->examen_id)['prix']);

        $creance = LaboCreancePartenaire::withoutGlobalScopes()->where('demande_id', $demande->id)->firstOrFail();

        $this->assertSame((float) $attendu, (float) $creance->montant);
    }

    public function test_la_clinique_annule_une_demande_non_prelevee(): void
    {
        $demande = $this->envoyer(['GLY']);

        $this->actingAs($this->medecin);
        app(LaboReseauService::class)->annulerEnvoi($demande, 'Patient parti', $this->medecin);

        $this->actingAs($this->biologiste);
        $this->assertSame('annulee', LaboDemande::findOrFail($demande->id)->statut->value);
        $this->assertSame(
            LaboCreancePartenaire::ANNULEE,
            LaboCreancePartenaire::where('demande_id', $demande->id)->firstOrFail()->statut
        );
    }

    public function test_annulation_refusee_apres_prelevement(): void
    {
        $demande = $this->envoyer(['GLY']);

        $this->actingAs($this->biologiste);
        $echantillon = LaboDemande::findOrFail($demande->id)->echantillons()->firstOrFail();
        app(PrelevementService::class)->marquerPreleve($echantillon, $this->biologiste);

        $this->actingAs($this->medecin);

        $this->expectException(OperationLaboImpossible::class);
        app(LaboReseauService::class)->annulerEnvoi($demande, 'Trop tard', $this->medecin);
    }

    public function test_bon_d_analyses_imprimable(): void
    {
        $demande = $this->envoyer(['GLY']);

        $this->actingAs($this->medecin);

        $this->get(route('labo.reseau.bon', $demande->id))
            ->assertOk()
            ->assertSee('Bon d\'analyses', false)
            ->assertSee($this->labo->nom);
    }

    private function envoyer(array $codes): LaboDemande
    {
        $patient = Patient::create(['first_name' => 'Aïssatou', 'last_name' => 'Diallo', 'gender' => 'Femme', 'birth_date' => '1994-04-04']);
        $this->clinique->patients()->syncWithoutDetaching([$patient->id]);

        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $examens = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->whereIn('code', $codes)->pluck('id')->all();

        return $reseau->envoyer($reseau->partenariat($this->partenariat->id), $patient, ['examens' => $examens], $this->medecin);
    }
}
