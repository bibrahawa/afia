<?php

namespace Tests\Feature\Labo;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Etablissement;
use App\Models\Labo\LaboCreancePartenaire;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Labo\LaboRelevePartenaire;
use App\Models\Patient;
use App\Models\User;
use App\Services\Labo\DemandeService;
use App\Services\Labo\FacturationPartenaireService;
use App\Services\Labo\LaboReseauService;
use App\Services\Labo\PartenariatService;
use Carbon\Carbon;
use Database\Seeders\Labo\LaboReseauPermissionsSeeder;

/** Lot 4b — le laboratoire facture la clinique partenaire : créances, relevés, règlements. */
class FacturationPartenaireTest extends LaboTestCase
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
        $this->travelTo(Carbon::parse('2026-10-06 09:00:00'));

        $this->labo = $this->creerEtablissement('labo-central');
        $this->clinique = $this->creerEtablissement('clinique-nord');
        $this->biologiste = $this->creerBiologiste($this->labo);
        $this->medecin = $this->creerUtilisateur($this->clinique);

        $this->actingAs($this->biologiste);
        $this->partenariat = app(PartenariatService::class)->creer(
            $this->clinique,
            ['mode_facturation_defaut' => 'partenaire', 'remise_pourcentage' => 10, 'delai_paiement_jours' => 30],
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

    public function test_une_demande_partenaire_cree_une_creance_au_prix_negocie(): void
    {
        $demande = $this->envoyer(['GLY']);
        $creance = LaboCreancePartenaire::withoutGlobalScopes()->where('demande_id', $demande->id)->firstOrFail();

        $prix = (float) LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->where('code', 'GLY')->value('prix');

        $this->assertSame(round($prix * 0.9), (float) $creance->montant);
        $this->assertSame(LaboCreancePartenaire::A_FACTURER, $creance->statut);
        // Le patient n'est pas facturé par le laboratoire.
        $this->assertNull($demande->transaction()->withoutGlobalScopes()->first());
    }

    public function test_releve_prepare_puis_envoye_fige_les_montants(): void
    {
        $this->envoyer(['GLY']);
        $this->envoyer(['NFS']);

        $this->actingAs($this->biologiste);
        $facturation = app(FacturationPartenaireService::class);

        $releve = $facturation->preparerReleve($this->partenariat, today()->startOfMonth(), today()->endOfMonth(), $this->biologiste);

        $this->assertSame(2, $releve->creances()->count());
        $this->assertGreaterThan(0, (float) $releve->montant_total);
        // Échéance = fin de période + 30 jours convenus au partenariat.
        $this->assertSame('2026-11-30', $releve->echeance->toDateString());

        $facturation->envoyerReleve($releve);

        $this->assertSame(LaboRelevePartenaire::ENVOYE, $releve->fresh()->statut);
        $this->assertSame(2, LaboCreancePartenaire::where('releve_id', $releve->id)->where('statut', LaboCreancePartenaire::FACTUREE)->count());
    }

    public function test_reglement_automatique_solde_le_releve(): void
    {
        $this->envoyer(['GLY']);
        $this->actingAs($this->biologiste);
        $facturation = app(FacturationPartenaireService::class);

        $releve = $facturation->preparerReleve($this->partenariat, today()->startOfMonth(), today()->endOfMonth(), $this->biologiste);
        $facturation->envoyerReleve($releve);
        $montant = (float) $releve->fresh()->montant_total;

        // Paiement partiel puis solde.
        $facturation->enregistrerReglement($this->partenariat, ['montant' => 1000, 'mode' => 'virement', 'recu_le' => today()->toDateString()], [], $this->biologiste);
        $this->assertSame(LaboRelevePartenaire::ENVOYE, $releve->fresh()->statut);

        $facturation->enregistrerReglement($this->partenariat, ['montant' => $montant - 1000, 'mode' => 'virement', 'recu_le' => today()->toDateString()], [], $this->biologiste);

        $this->assertSame(LaboRelevePartenaire::SOLDE, $releve->fresh()->statut);
        $this->assertSame(0.0, $facturation->resume($this->partenariat)['reste_du']);
    }

    public function test_montant_superieur_aux_creances_refuse(): void
    {
        $this->envoyer(['GLY']);
        $this->actingAs($this->biologiste);
        $facturation = app(FacturationPartenaireService::class);
        $montant = (float) LaboCreancePartenaire::sum('montant');

        $this->expectException(OperationLaboImpossible::class);
        $facturation->enregistrerReglement($this->partenariat, ['montant' => $montant + 50000, 'mode' => 'virement', 'recu_le' => today()->toDateString()], [], $this->biologiste);
    }

    public function test_annulation_impossible_apres_envoi_du_releve(): void
    {
        $demande = $this->envoyer(['GLY']);
        $this->actingAs($this->biologiste);
        $facturation = app(FacturationPartenaireService::class);

        $releve = $facturation->preparerReleve($this->partenariat, today()->startOfMonth(), today()->endOfMonth(), $this->biologiste);
        $facturation->envoyerReleve($releve);

        $this->expectException(OperationLaboImpossible::class);
        app(DemandeService::class)->annuler(LaboDemande::findOrFail($demande->id), 'Erreur de saisie', $this->biologiste);
    }

    public function test_la_clinique_voit_le_releve_recu(): void
    {
        $this->envoyer(['GLY']);
        $this->actingAs($this->biologiste);
        $facturation = app(FacturationPartenaireService::class);
        $releve = $facturation->preparerReleve($this->partenariat, today()->startOfMonth(), today()->endOfMonth(), $this->biologiste);

        // Tant qu'il n'est pas envoyé, la clinique ne le voit pas.
        $this->actingAs($this->medecin);
        $this->assertSame(0, app(LaboReseauService::class)->relevesRecus()->count());

        $this->actingAs($this->biologiste);
        $facturation->envoyerReleve($releve);

        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $this->assertSame(1, $reseau->relevesRecus()->count());
        $this->assertSame((float) $releve->fresh()->montant_total, $reseau->resteDuPartenaires());
        $this->assertSame(1, $reseau->releveRecu($releve->id)->creances->count());
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
