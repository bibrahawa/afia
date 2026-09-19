<?php

namespace Tests\Feature\Facturation;

use App\Http\Middleware\CheckPermission;
use App\Models\Etablissement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Caisse : la part assurance calculée à la facturation n'est jamais
 * encaissée au guichet, seule la part patient l'est. MySQL/MariaDB requis.
 */
class CaisseTest extends TestCase
{
    use RefreshDatabase;

    private User $caissiere;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([CheckPermission::class, Authorize::class]);

        $etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->caissiere = User::create(['name' => 'Caisse', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->caissiere->forceFill(['etablissement_id' => $etab->id])->save();
        $this->actingAs($this->caissiere);

        $this->patient = Patient::create(['first_name' => 'Aminata', 'last_name' => 'Touré', 'gender' => 'Femme', 'birth_date' => '1994-09-18']);
        $etab->patients()->syncWithoutDetaching([$this->patient->id]);
    }

    /** Facture de 100 000 GNF dont l'assurance prend 80 % (calculé par le moteur à la facturation). */
    private function factureAssuree(float $total = 100000, float $partAssurance = 80000): Transaction
    {
        $transaction = Transaction::create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->caissiere->id,
            'description' => 'Consultation générale',
            'sub_total' => $total,
            'tax_amount' => 0,
            'discount' => 0,
            'status' => 'pending',
        ]);

        $invoice = Invoice::create([
            'transaction_id' => $transaction->id,
            'total_amount' => $total,
            'insurance_amount' => $partAssurance,
            'patient_amount' => $total - $partAssurance,
            'insurance_status' => $partAssurance > 0 ? 'pending' : null,
            'patient_amount_status' => 'pending',
        ]);

        // Une ligne d'acte, comme l'écrit InvoiceService à la facturation.
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'coverage_type_type' => 'service',
            'coverage_type_id' => 1,
            'description' => 'Consultation générale',
            'unit_price' => $total,
            'quantity' => 1,
            'total_amount' => $total,
            'discount' => 0,
            'insurance_covered_amount' => $partAssurance,
            'patient_amount' => $total - $partAssurance,
        ]);

        return $transaction->fresh('invoice');
    }

    public function test_la_caisse_affiche_seulement_la_part_patient(): void
    {
        $this->factureAssuree();

        $this->get(route('caisse.index'))->assertOk()->assertSee('Aminata Touré')->assertSee('20 000');
        $this->get(route('caisse.show', $this->patient->id))->assertOk()->assertSee('80 000')->assertSee('20 000');
    }

    public function test_encaisser_la_part_patient_ne_touche_pas_la_part_assurance(): void
    {
        $transaction = $this->factureAssuree();

        $this->post(route('caisse.encaisser', $this->patient->id), ['montant' => 20000, 'source' => 'CASH'])
            ->assertRedirect(route('caisse.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, Paiement::where('transaction_id', $transaction->id)->count());
        $paiement = Paiement::where('transaction_id', $transaction->id)->first();
        $this->assertSame(Paiement::TYPE_PATIENT, $paiement->type);
        $this->assertEquals(20000, (float) $paiement->montant);

        // Part patient soldée, assurance toujours attendue : statut « approved ».
        $this->assertSame('approved', $transaction->fresh()->status);
        $this->assertSame('pending', $transaction->fresh('invoice')->invoice->insurance_status);

        // Le patient sort de la caisse.
        $this->get(route('caisse.index'))->assertOk()->assertDontSee('Aminata Touré');
    }

    public function test_un_montant_superieur_au_reste_du_est_refuse(): void
    {
        $transaction = $this->factureAssuree();

        $this->post(route('caisse.encaisser', $this->patient->id), ['montant' => 100000, 'source' => 'CASH'])
            ->assertSessionHas('error');

        $this->assertSame(0, Paiement::where('transaction_id', $transaction->id)->count());
        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_un_acompte_laisse_le_reste_a_payer(): void
    {
        $transaction = $this->factureAssuree();

        $this->post(route('caisse.encaisser', $this->patient->id), ['montant' => 5000, 'source' => 'MOBILE'])
            ->assertRedirect(route('caisse.show', $this->patient->id));

        $this->assertSame('partial', $transaction->fresh()->status);
        $this->get(route('caisse.show', $this->patient->id))->assertSee('15 000');
    }

    public function test_une_remise_reduit_seulement_la_part_patient(): void
    {
        $transaction = $this->factureAssuree();

        $this->post(route('caisse.remises', $transaction->id), ['remises' => ['service' => [1 => 5000]]])
            ->assertRedirect(route('caisse.show', $this->patient->id))
            ->assertSessionHas('success');

        $invoice = $transaction->fresh('invoice')->invoice;
        $this->assertEquals(80000, (float) $invoice->insurance_amount); // l'assurance ne bouge pas
        $this->assertEquals(15000, (float) $invoice->patient_amount);
        $this->get(route('caisse.show', $this->patient->id))->assertSee('15 000');
    }

    public function test_une_remise_superieure_a_la_part_patient_est_refusee(): void
    {
        $transaction = $this->factureAssuree();

        $this->post(route('caisse.remises', $transaction->id), ['remises' => ['service' => [1 => 30000]]])
            ->assertSessionHas('error');

        $this->assertEquals(20000, (float) $transaction->fresh('invoice')->invoice->patient_amount);
    }
}
