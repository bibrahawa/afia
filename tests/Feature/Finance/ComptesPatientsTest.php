<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\Etablissement;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PatientAccountService;
use App\Services\PaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Priorité 3 — comptes patients, encaissements, suppression de pièces. MySQL requis. */
class ComptesPatientsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private User $caissier;
    private Patient $patient;
    private PatientAccountService $comptes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->caissier = User::create(['name' => 'Caisse', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->caissier->forceFill(['etablissement_id' => $this->etab->id])->save();
        $this->actingAs($this->caissier);

        $this->patient = Patient::create(['first_name' => 'Kadiatou', 'last_name' => 'Sow', 'gender' => 'Femme']);
        $this->comptes = app(PatientAccountService::class);
    }

    /** Pièce de $total GNF, sans facture (cas des anciennes consultations), déjà portée au compte. */
    private function piece(float $total): Transaction
    {
        $compte = $this->comptes->credit($this->patient, $total);

        return Transaction::create([
            'transactionable_type' => Patient::class, 'transactionable_id' => $this->patient->id,
            'account_id' => $compte->id, 'user_id' => $this->caissier->id, 'patient_id' => $this->patient->id,
            'description' => 'Test', 'sub_total' => $total, 'tax_amount' => 0, 'discount' => 0, 'total' => $total, 'status' => 'pending',
        ]);
    }

    public function test_un_seul_compte_par_patient_et_par_etablissement(): void
    {
        $a = $this->comptes->getOrCreate($this->patient);
        $b = $this->comptes->getOrCreate($this->patient);
        $this->assertSame($a->id, $b->id);

        $this->expectException(QueryException::class);
        DB::table('accounts')->insert(['etablissement_id' => $this->etab->id, 'owner_type' => Patient::class, 'owner_id' => $this->patient->id, 'balance' => 0]);
    }

    public function test_montants_exacts_en_decimal(): void
    {
        $compte = $this->comptes->getOrCreate($this->patient);
        foreach (range(1, 10) as $i) {
            $this->comptes->credit($this->patient, 0.1);
        }

        $this->assertSame('1.00', (string) Account::find($compte->id)->getRawOriginal('balance'));
    }

    public function test_encaissement_sur_piece_sans_facture_solde_et_statut(): void
    {
        $piece = $this->piece(150000);

        $resultat = app(PaymentService::class)->payPatientForPatient($this->patient, 100000, 'CASH');
        $this->assertEquals(100000, $resultat['paid_amount']);
        $this->assertSame('partial', $piece->fresh()->status);
        $this->assertEquals(50000, Account::find($piece->account_id)->balance);

        $resultat = app(PaymentService::class)->payPatientForPatient($this->patient, 80000, 'CASH');
        $this->assertEquals(50000, $resultat['paid_amount']);
        $this->assertEquals(30000, $resultat['remaining_amount'], 'trop-perçu signalé, jamais affecté');
        $this->assertSame('paid', $piece->fresh()->status);
        $this->assertEquals(0, Account::find($piece->account_id)->balance);
    }

    public function test_piece_deja_reglee_refuse_un_nouvel_encaissement(): void
    {
        $piece = $this->piece(20000);
        app(PaymentService::class)->payPatientTransaction($piece, 20000, 'CASH');

        $this->expectException(\InvalidArgumentException::class);
        app(PaymentService::class)->payPatientTransaction($piece->fresh(), 5000, 'CASH');
    }

    public function test_retirer_une_piece_ne_retire_que_le_reste_du(): void
    {
        $piece = $this->piece(100000);
        $this->piece(40000);
        app(PaymentService::class)->payPatientTransaction($piece, 30000, 'CASH');

        $this->comptes->retirerTransaction($piece->fresh());

        // Reste : 40 000 (autre pièce). L'ancien code retirait le total : -30 000.
        $this->assertEquals(40000, Account::find($piece->account_id)->balance);
    }

    public function test_commande_detecte_puis_corrige_les_ecarts(): void
    {
        $piece = $this->piece(75000);
        Paiement::create(['user_id' => $this->caissier->id, 'patient_id' => $this->patient->id, 'transaction_id' => $piece->id, 'type' => 'paiement', 'montant' => 25000]);
        // Paiement saisi sans débit du compte : solde enregistré 75 000, attendu 50 000.

        $this->artisan('aprosafe:comptes')->expectsOutputToContain('1 compte(s) en écart')->assertSuccessful();
        $this->assertEquals(75000, Account::find($piece->account_id)->balance, 'le rapport ne modifie rien');

        $this->artisan('aprosafe:comptes --corriger')->assertSuccessful();
        $this->assertEquals(50000, Account::find($piece->account_id)->balance);
        $this->assertDatabaseHas('activity_logs', ['action' => 'comptes.solde_recalcule', 'subject_id' => $piece->account_id]);

        $this->artisan('aprosafe:comptes')->expectsOutputToContain('Aucun écart')->assertSuccessful();
    }

    public function test_une_facture_encaissee_ne_peut_plus_etre_supprimee_en_base(): void
    {
        $piece = $this->piece(10000);
        app(PaymentService::class)->payPatientTransaction($piece, 10000, 'CASH');

        $this->expectException(QueryException::class);
        DB::table('transactions')->where('id', $piece->id)->delete(); // RESTRICT : l'argent encaissé ne disparaît pas
    }

    /**
     * Écran « factures impayées » : la route account.payer est servie par
     * PaymentController::processPayment (encaissement d'UNE facture), pas par
     * AccountController::payer.
     */
    public function test_ecran_encaissement_d_une_facture(): void
    {
        if (! Route::has('account.payer')) {
            $this->markTestSkipped('Route account.payer absente');
        }
        // On teste l'encaissement, pas la configuration des permissions de la route.
        $this->withoutMiddleware([\App\Http\Middleware\CheckPermission::class, \Illuminate\Auth\Middleware\Authorize::class]);
        $piece = $this->piece(60000);

        $this->post(route('account.payer'), [
            'patient_id' => $this->patient->id, 'transaction_id' => $piece->id, 'montant' => 60000, 'source' => 'CASH',
        ])->assertSessionHas('success');

        $this->assertSame('paid', $piece->fresh()->status);
        $this->assertEquals(0, Account::find($piece->account_id)->balance);

        // Une seconde tentative sur la facture réglée est refusée, sans paiement de 0 GNF.
        $this->post(route('account.payer'), [
            'patient_id' => $this->patient->id, 'transaction_id' => $piece->id, 'montant' => 1000, 'source' => 'CASH',
        ])->assertSessionHas('error');
        $this->assertSame(1, Paiement::where('transaction_id', $piece->id)->count());
    }
}
