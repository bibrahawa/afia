<?php

namespace Tests\Feature\Facturation;

use App\Exceptions\Facturation\OperationFacturationImpossible;
use App\Models\Account;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BillingService;
use App\Services\Facturation\AnnulationPaiementService;
use App\Services\Facturation\RemiseService;
use App\Services\PaymentService;
use App\Support\Facturation\SoldeTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Lot 1 — facturation fiable. MySQL/MariaDB requis.
 *
 * Tarifs : consultation 100 000 GNF, échographie 50 000 GNF, bilan 30 000 GNF.
 */
class FacturationFiableTest extends TestCase
{
    use RefreshDatabase;

    private User $caissier;
    private Department $departement;
    private Employee $medecin;
    private Patient $patient;
    private Service $consultationActe;
    private Service $echographie;
    private Service $bilan;

    protected function setUp(): void
    {
        parent::setUp();

        $etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->caissier = User::create(['name' => 'Caisse', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->caissier->forceFill(['etablissement_id' => $etab->id])->save();
        $this->actingAs($this->caissier);

        $this->departement = Department::create(['name' => 'Gynécologie']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id]);
        $this->patient = Patient::create(['first_name' => 'Kadiatou', 'last_name' => 'Sow', 'gender' => 'Femme']);

        $this->consultationActe = Service::create(['name' => 'Consultation', 'amount' => 100000, 'department_id' => $this->departement->id]);
        $this->echographie = Service::create(['name' => 'Échographie', 'amount' => 50000, 'department_id' => $this->departement->id]);
        $this->bilan = Service::create(['name' => 'Bilan', 'amount' => 30000, 'department_id' => $this->departement->id]);
    }

    // ------------------------------------------------------------------
    // Part patient / part assurance
    // ------------------------------------------------------------------

    public function test_le_reglement_de_l_assureur_ne_fausse_plus_le_reste_du_patient(): void
    {
        $this->assurerPatientA80Pourcent();
        $transaction = $this->facturer([$this->consultationActe]);

        $solde = SoldeTransaction::pour($transaction);
        $this->assertSame(20000.0, $solde->partPatient);
        $this->assertSame(80000.0, $solde->partAssurance);

        app(PaymentService::class)->payPatientTransaction($transaction, 20000, 'CASH');
        app(PaymentService::class)->payInsuranceTransaction($transaction->fresh(), 80000);

        $solde = SoldeTransaction::pour($transaction->fresh());
        $this->assertSame(20000.0, $solde->payePatient);
        $this->assertSame(0.0, $solde->resteDuPatient());
        $this->assertSame('paid', $solde->statutPatient());
        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertSame('100000.00', (string) $transaction->fresh()->getRawOriginal('montant_payer'));
    }

    public function test_un_montant_superieur_au_reste_du_n_est_pas_encaisse_au_dela(): void
    {
        $transaction = $this->facturer([$this->consultationActe]);

        app(PaymentService::class)->payPatientTransaction($transaction, 150000, 'CASH');

        $this->assertSame(100000.0, SoldeTransaction::pour($transaction->fresh())->payePatient);
    }

    // ------------------------------------------------------------------
    // Remises
    // ------------------------------------------------------------------

    public function test_une_remise_appliquee_deux_fois_n_est_comptee_qu_une_fois_et_garde_les_autres_lignes(): void
    {
        $transaction = $this->facturer([$this->consultationActe, $this->echographie]);

        app(RemiseService::class)->appliquer($transaction, ['Service' => [$this->consultationActe->id => 10000]]);
        app(RemiseService::class)->appliquer($transaction->fresh(), ['Service' => [$this->consultationActe->id => 10000]]);

        $transaction = $transaction->fresh('invoice');
        $this->assertSame('140000.00', (string) $transaction->invoice->getRawOriginal('total_amount'));
        $this->assertSame('140000.00', (string) $transaction->invoice->getRawOriginal('patient_amount'));
        $this->assertSame('140000.00', (string) $transaction->getRawOriginal('total'));
        $this->assertSame('10000.00', (string) $transaction->getRawOriginal('discount'));
        $this->assertSame(140000.0, (float) Account::find($transaction->account_id)->balance);
    }

    public function test_une_remise_ne_peut_pas_depasser_la_part_patient(): void
    {
        $this->assurerPatientA80Pourcent();
        $transaction = $this->facturer([$this->consultationActe]); // part patient : 20 000

        $this->expectException(OperationFacturationImpossible::class);
        app(RemiseService::class)->appliquer($transaction, ['Service' => [$this->consultationActe->id => 30000]]);
    }

    public function test_un_recalcul_conserve_les_remises(): void
    {
        $transaction = $this->facturer([$this->consultationActe]);
        app(RemiseService::class)->appliquer($transaction, ['Service' => [$this->consultationActe->id => 10000]]);

        $this->consultation($transaction)->services()->attach($this->echographie->id);
        app(BillingService::class)->recalculate($transaction->fresh());

        $transaction = $transaction->fresh('invoice');
        $this->assertSame('140000.00', (string) $transaction->getRawOriginal('total'));
        $this->assertSame('10000.00', (string) $transaction->getRawOriginal('discount'));
    }

    // ------------------------------------------------------------------
    // Figement
    // ------------------------------------------------------------------

    public function test_on_peut_ajouter_un_acte_apres_encaissement(): void
    {
        $transaction = $this->facturer([$this->consultationActe]);
        app(PaymentService::class)->payPatientTransaction($transaction, 100000, 'CASH');

        $this->consultation($transaction)->services()->attach($this->bilan->id);
        app(BillingService::class)->recalculate($transaction->fresh());

        $solde = SoldeTransaction::pour($transaction->fresh());
        $this->assertSame(130000.0, $solde->partPatient);
        $this->assertSame(30000.0, $solde->resteDuPatient());
        $this->assertSame('partial', $transaction->fresh()->status);
    }

    public function test_une_facture_ne_descend_pas_sous_ce_qui_a_ete_encaisse(): void
    {
        $transaction = $this->facturer([$this->consultationActe, $this->echographie]);
        app(PaymentService::class)->payPatientTransaction($transaction, 150000, 'CASH');

        $this->consultation($transaction)->services()->detach($this->echographie->id);

        try {
            app(BillingService::class)->recalculate($transaction->fresh());
            $this->fail('Le recalcul aurait dû être refusé.');
        } catch (OperationFacturationImpossible $e) {
            $this->assertStringContainsString('Annulez', $e->getMessage());
        }

        // Rien n'a bougé.
        $this->assertSame('150000.00', (string) $transaction->fresh()->getRawOriginal('total'));
        $this->assertCount(2, $transaction->fresh('invoice')->invoice->items);
    }

    public function test_une_facture_dont_la_reclamation_est_transmise_ne_se_recalcule_plus(): void
    {
        $this->assurerPatientA80Pourcent();
        $transaction = $this->facturer([$this->consultationActe]);

        InsuranceClaim::where('invoice_id', $transaction->invoice->id)->update(['status' => 'submitted']);
        $this->consultation($transaction)->services()->attach($this->bilan->id);

        $this->expectException(OperationFacturationImpossible::class);
        app(BillingService::class)->recalculate($transaction->fresh());
    }

    // ------------------------------------------------------------------
    // Annulation de paiement
    // ------------------------------------------------------------------

    public function test_annuler_un_paiement_le_rend_de_nouveau_du_sans_effacer_sa_trace(): void
    {
        $transaction = $this->facturer([$this->consultationActe]);
        app(PaymentService::class)->payPatientTransaction($transaction, 100000, 'CASH');
        $paiement = Paiement::where('transaction_id', $transaction->id)->firstOrFail();

        app(AnnulationPaiementService::class)->annuler($paiement, 'Double saisie en caisse', $this->caissier);

        $transaction = $transaction->fresh();
        $this->assertSame(100000.0, SoldeTransaction::pour($transaction)->resteDuPatient());
        $this->assertSame('pending', $transaction->status);
        $this->assertSame(100000.0, (float) Account::find($transaction->account_id)->balance);
        $this->assertSame(0, Paiement::where('transaction_id', $transaction->id)->count());
        $this->assertSame(1, Paiement::annules()->where('transaction_id', $transaction->id)->count());

        $this->expectException(OperationFacturationImpossible::class);
        app(AnnulationPaiementService::class)->annuler($paiement, 'Encore une fois', $this->caissier);
    }

    public function test_apres_annulation_on_peut_corriger_la_facture(): void
    {
        $transaction = $this->facturer([$this->consultationActe, $this->echographie]);
        app(PaymentService::class)->payPatientTransaction($transaction, 150000, 'CASH');
        $paiement = Paiement::where('transaction_id', $transaction->id)->firstOrFail();

        app(AnnulationPaiementService::class)->annuler($paiement, 'Échographie non réalisée', $this->caissier);
        $this->consultation($transaction)->services()->detach($this->echographie->id);
        app(BillingService::class)->recalculate($transaction->fresh());

        $this->assertSame('100000.00', (string) $transaction->fresh()->getRawOriginal('total'));
        $this->assertSame(100000.0, (float) Account::find($transaction->account_id)->balance);
    }

    // ------------------------------------------------------------------
    // Outils
    // ------------------------------------------------------------------

    /** @param Service[] $services */
    private function facturer(array $services): Transaction
    {
        $consultation = Consultation::create([
            'patient_id' => $this->patient->id, 'medecin_id' => $this->medecin->id,
            'department_id' => $this->departement->id, 'motif' => 'Contrôle', 'diagnostic' => 'RAS',
        ]);
        $consultation->services()->attach(collect($services)->pluck('id')->all());

        return app(BillingService::class)->createFromConsultation($consultation->fresh())->fresh('invoice');
    }

    private function consultation(Transaction $transaction): Consultation
    {
        return Consultation::findOrFail($transaction->transactionable_id);
    }

    private function assurerPatientA80Pourcent(): void
    {
        $assureur = InsuranceCompany::create(['name' => 'NSIA', 'code' => 'NSIA']);

        PatientInsurance::create([
            'patient_id' => $this->patient->id, 'insurance_company_id' => $assureur->id,
            'coverage_percentage' => 80, 'policy_number' => 'POL-1',
            'start_date' => now()->subMonth()->toDateString(), 'status' => 'active',
        ]);

        InsuranceCoverage::create([
            'insurance_company_id' => $assureur->id,
            'coverageable_type' => Service::class, 'coverageable_id' => $this->consultationActe->id,
            'valid_from' => now()->subMonth()->toDateString(), 'status' => 'active', 'acte_price' => 100000,
        ]);
    }
}
