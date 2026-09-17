<?php

namespace Tests\Feature\Assurance;

use App\Enums\Assurance\StatutCouverture;
use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Exceptions\Facturation\OperationFacturationImpossible;
use App\Models\Account;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Formule;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Assurance\BordereauService;
use App\Services\Assurance\ReclamationService;
use App\Services\Assurance\ReferentielAssuranceService;
use App\Services\Assurance\ReglementAssuranceService;
use App\Services\BillingService;
use App\Services\Facturation\AnnulationPaiementService;
use App\Services\PatientAccountService;
use App\Services\PaymentService;
use App\Support\Facturation\SoldeTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Lot 2c — réclamations, bordereaux, règlements. MySQL/MariaDB requis.
 * NSIA 80 % ; consultation 100 000 GNF (réclamé 80 000), échographie 50 000 GNF.
 */
class ReclamationsReglementsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $departement;
    private Employee $medecin;
    private InsuranceCompany $nsia;
    private Formule $formule;
    private Service $consultation;
    private Service $echographie;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-26 09:00:00'));

        $etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->user = User::create(['name' => 'Gestionnaire', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->user->forceFill(['etablissement_id' => $etab->id])->save();
        $this->actingAs($this->user);

        $this->departement = Department::create(['name' => 'Médecine']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id]);
        $this->consultation = Service::create(['name' => 'Consultation', 'amount' => 100000, 'department_id' => $this->departement->id]);
        $this->echographie = Service::create(['name' => 'Échographie', 'amount' => 50000, 'department_id' => $this->departement->id, 'famille_acte' => 'imagerie']);

        $this->nsia = $this->organisme('NSIA');
        $contrat = Contrat::create(['insurance_company_id' => $this->nsia->id, 'numero_police' => 'GRP-1', 'date_debut' => '2026-01-01', 'statut' => StatutCouverture::Active]);
        $this->formule = Formule::create(['contrat_id' => $contrat->id, 'libelle' => 'Standard', 'taux_prise_en_charge' => 80]);

        $this->patient = Patient::create(['first_name' => 'Mariama', 'last_name' => 'Sylla', 'gender' => 'Femme', 'birth_date' => '1990-01-01']);
        $etab->patients()->syncWithoutDetaching([$this->patient->id]);
        app(ReferentielAssuranceService::class)->creerAdhesion($this->formule, $this->patient, ['date_debut' => '2026-01-01']);
    }

    // ------------------------------------------------------------------ Détail et bordereau

    public function test_la_reclamation_est_detaillee_par_acte(): void
    {
        $reclamation = $this->reclamation($this->facturer([$this->consultation, $this->echographie]));

        $this->assertSame('120000.00', (string) $reclamation->getRawOriginal('claimed_amount'));
        $this->assertCount(2, $reclamation->lignes);
        $this->assertEquals(120000, $reclamation->lignes->sum('montant_reclame'));
    }

    public function test_bordereau_envoye_fige_la_facture_et_peut_etre_rouvert(): void
    {
        $transaction = $this->facturer([$this->consultation]);
        $service = app(BordereauService::class);

        $bordereau = $service->creer($this->nsia, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));
        $this->assertSame(1, $bordereau->reclamations()->count());

        $service->envoyer($bordereau, today());
        $this->assertSame('submitted', $this->reclamation($transaction)->status);

        try {
            app(BillingService::class)->recalculate($transaction);
            $this->fail('La facture envoyée ne doit plus se recalculer.');
        } catch (OperationFacturationImpossible) {
        }

        $service->rouvrir($bordereau->fresh());
        $this->assertSame('draft', $this->reclamation($transaction)->status);
    }

    // ------------------------------------------------------------------ Réponse de l'assureur

    public function test_un_rejet_partiel_exige_un_motif(): void
    {
        $reclamation = $this->envoyer($this->facturer([$this->consultation]));

        $this->expectException(OperationAssuranceImpossible::class);
        app(ReclamationService::class)->enregistrerReponse($reclamation, [$reclamation->lignes->first()->id => ['montant_accepte' => 60000]]);
    }

    public function test_ecart_transfere_au_patient(): void
    {
        $transaction = $this->facturer([$this->consultation]);
        $reclamation = $this->envoyer($transaction);
        $this->repondre($reclamation, 60000);

        app(ReclamationService::class)->transfererEcartAuPatient($reclamation->fresh());

        $transaction = $transaction->fresh('invoice');
        $this->assertSame('60000.00', (string) $transaction->invoice->getRawOriginal('insurance_amount'));
        $this->assertSame('40000.00', (string) $transaction->invoice->getRawOriginal('patient_amount'));
        $this->assertSame('100000.00', (string) $transaction->getRawOriginal('total'));
        $this->assertSame(60000.0, $reclamation->fresh()->resteDu());
    }

    public function test_seul_l_accepte_consomme_le_plafond(): void
    {
        $this->formule->update(['plafond_annuel_beneficiaire' => 100000]);
        $reclamation = $this->envoyer($this->facturer([$this->consultation]));
        $this->repondre($reclamation, 50000);

        $suivante = $this->facturer([$this->consultation]);

        $this->assertSame('50000.00', (string) $suivante->invoice->getRawOriginal('insurance_amount'));
    }

    // ------------------------------------------------------------------ Règlements

    public function test_reglement_automatique_encaisse_et_debite_le_compte(): void
    {
        $transaction = $this->facturer([$this->consultation]);
        app(PaymentService::class)->payPatientTransaction($transaction, 20000, 'CASH');

        $reglement = app(ReglementAssuranceService::class)->regler($this->nsia, 80000, [], ['payment_method' => 'virement', 'payment_reference' => 'VIR-889']);

        $this->assertSame('paid', $this->reclamation($transaction)->status);
        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertSame(1, Paiement::where('transaction_id', $transaction->id)->where('type', 'remboursement')->count());
        $this->assertSame(0.0, (float) Account::find($transaction->account_id)->balance);
        $this->assertSame('80000.00', (string) $reglement->getRawOriginal('paid_amount'));
    }

    public function test_ecart_passe_en_perte_au_reglement(): void
    {
        $transaction = $this->facturer([$this->consultation]);
        app(PaymentService::class)->payPatientTransaction($transaction, 20000, 'CASH');
        $reclamation = $this->envoyer($transaction);
        $this->repondre($reclamation, 60000);

        app(ReglementAssuranceService::class)->regler($this->nsia, 60000, [$reclamation->id => ['paye' => 60000, 'ecart' => 20000]]);

        $transaction = $transaction->fresh();
        $compte = Account::find($transaction->account_id);
        $this->assertSame('paid', $reclamation->fresh()->status);
        $this->assertSame('paid', $transaction->status);
        $this->assertSame(0.0, SoldeTransaction::pour($transaction)->resteDuAssurance());
        $this->assertSame(0.0, (float) $compte->balance);
        $this->assertSame(0.0, app(PatientAccountService::class)->soldeAttendu($compte));
    }

    public function test_un_montant_superieur_aux_creances_est_refuse(): void
    {
        $this->facturer([$this->consultation]);

        $this->expectException(OperationAssuranceImpossible::class);
        app(ReglementAssuranceService::class)->regler($this->nsia, 90000);
    }

    public function test_la_deuxieme_assurance_d_une_facture_se_regle_enfin(): void
    {
        $sanlam = $this->organisme('Sanlam');
        app(ReferentielAssuranceService::class)->creerContratIndividuel($this->patient, $sanlam, ['policy_number' => 'IND-5', 'coverage_percentage' => 50, 'start_date' => '2026-01-01']);

        $transaction = $this->facturer([$this->consultation]);
        $this->assertSame(2, InsuranceClaim::where('invoice_id', $transaction->invoice->id)->count());

        $sanlamClaim = InsuranceClaim::where('invoice_id', $transaction->invoice->id)->where('insurance_company_id', $sanlam->id)->firstOrFail();
        app(ReglementAssuranceService::class)->regler($sanlam, (float) $sanlamClaim->claimed_amount);

        $this->assertSame('paid', $sanlamClaim->fresh()->status);
        $this->assertNotSame('paid', InsuranceClaim::where('invoice_id', $transaction->invoice->id)->where('insurance_company_id', $this->nsia->id)->value('status'));
    }

    public function test_annuler_le_paiement_d_un_reglement_rouvre_la_reclamation(): void
    {
        $transaction = $this->facturer([$this->consultation]);
        app(ReglementAssuranceService::class)->regler($this->nsia, 80000);
        $paiement = Paiement::where('transaction_id', $transaction->id)->where('type', 'remboursement')->firstOrFail();

        app(AnnulationPaiementService::class)->annuler($paiement, 'Virement rejeté par la banque', $this->user);

        $reclamation = $this->reclamation($transaction);
        $this->assertNotSame('paid', $reclamation->status);
        $this->assertSame(80000.0, $reclamation->resteDu());
    }

    public function test_la_caisse_passe_par_le_meme_chemin(): void
    {
        $transaction = $this->facturer([$this->consultation]);

        app(PaymentService::class)->payInsuranceTransaction($transaction, 80000);

        $this->assertSame('paid', $this->reclamation($transaction)->status);
        $this->assertSame(1, $this->reclamation($transaction)->settlementItems()->count());
    }

    // ------------------------------------------------------------------ Outils

    private function organisme(string $nom): InsuranceCompany
    {
        $o = InsuranceCompany::create(['name' => $nom, 'code' => strtoupper($nom), 'type' => 'assureur']);
        foreach ([$this->consultation, $this->echographie] as $s) {
            InsuranceCoverage::create(['insurance_company_id' => $o->id, 'coverageable_type' => Service::class, 'coverageable_id' => $s->id,
                'valid_from' => '2026-01-01', 'status' => 'active', 'acte_price' => $s->amount]);
        }

        return $o;
    }

    private function facturer(array $services): Transaction
    {
        $c = Consultation::create(['patient_id' => $this->patient->id, 'medecin_id' => $this->medecin->id, 'department_id' => $this->departement->id, 'motif' => 'Suivi', 'diagnostic' => 'RAS']);
        $c->services()->attach(collect($services)->pluck('id')->all());

        return app(BillingService::class)->createFromConsultation($c->fresh())->fresh(['invoice']);
    }

    private function reclamation(Transaction $t): InsuranceClaim
    {
        return InsuranceClaim::with('lignes')->where('invoice_id', $t->invoice->id)->where('insurance_company_id', $this->nsia->id)->firstOrFail();
    }

    private function envoyer(Transaction $t): InsuranceClaim
    {
        $service = app(BordereauService::class);
        $service->envoyer($service->creer($this->nsia, null, null), today());

        return $this->reclamation($t);
    }

    private function repondre(InsuranceClaim $c, float $accepte): void
    {
        app(ReclamationService::class)->enregistrerReponse($c, [$c->lignes->first()->id => ['montant_accepte' => $accepte, 'motif_rejet' => 'Tarif plafonné par l\'assureur']]);
    }
}
