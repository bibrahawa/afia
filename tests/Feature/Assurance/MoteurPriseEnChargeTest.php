<?php

namespace Tests\Feature\Assurance;

use App\Enums\Assurance\StatutCouverture;
use App\Models\Assurance\Adhesion;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Formule;
use App\Models\Assurance\PecUtilisation;
use App\Models\Assurance\PriseEnCharge;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Assurance\DroitsPatientService;
use App\Services\Assurance\ReferentielAssuranceService;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Lot 2b — moteur de prise en charge. MySQL/MariaDB requis.
 *
 * Contrat NSIA « Cadres » : 80 %, du 01/01/2026. Consultation 100 000 GNF,
 * échographie 200 000 GNF (famille imagerie), conventions au prix catalogue.
 */
class MoteurPriseEnChargeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private ReferentielAssuranceService $referentiel;
    private Department $departement;
    private Employee $medecin;
    private InsuranceCompany $nsia;
    private Contrat $contrat;
    private Formule $formule;
    private Service $consultation;
    private Service $echographie;
    private Patient $mamadou;
    private Patient $aissatou;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-25 09:00:00'));

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $user = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $user->forceFill(['etablissement_id' => $this->etab->id])->save();
        $this->actingAs($user);

        $this->referentiel = app(ReferentielAssuranceService::class);
        $this->departement = Department::create(['name' => 'Gynécologie']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id]);

        $this->consultation = Service::create(['name' => 'Consultation', 'amount' => 100000, 'department_id' => $this->departement->id, 'famille_acte' => 'consultation']);
        $this->echographie = Service::create(['name' => 'Échographie', 'amount' => 200000, 'department_id' => $this->departement->id, 'famille_acte' => 'imagerie']);

        $this->nsia = InsuranceCompany::create(['name' => 'NSIA', 'code' => 'NSIA', 'type' => 'assureur']);
        $this->conventionner($this->nsia);
        $this->contrat = Contrat::create(['insurance_company_id' => $this->nsia->id, 'numero_police' => 'GRP-1', 'date_debut' => '2026-01-01', 'statut' => StatutCouverture::Active]);
        $this->formule = Formule::create(['contrat_id' => $this->contrat->id, 'libelle' => 'Cadres', 'taux_prise_en_charge' => 80]);

        $this->mamadou = $this->patient('Mamadou');
        $this->aissatou = $this->patient('Aïssatou');
    }

    public function test_taux_par_famille_d_actes(): void
    {
        $this->formule->garanties()->create(['famille_acte' => 'imagerie', 'taux' => 50]);
        $this->adherer($this->mamadou);

        $t = $this->facturer($this->mamadou, [$this->consultation, $this->echographie]);

        $this->assertLignes($t, ['Consultation' => 80000, 'Échographie' => 100000]);
    }

    public function test_famille_exclue_non_prise_en_charge(): void
    {
        $this->formule->garanties()->create(['famille_acte' => 'imagerie', 'exclu' => true]);
        $this->adherer($this->mamadou);

        $t = $this->facturer($this->mamadou, [$this->echographie]);

        $this->assertSame('0.00', (string) $t->invoice->getRawOriginal('insurance_amount'));
        $this->assertSame('famille d\'actes exclue du contrat', $t->invoice->items->first()->repartition_assurance[0]['motif']);
    }

    public function test_le_plafond_est_decompte_au_fil_de_la_facture(): void
    {
        $this->formule->update(['plafond_annuel_beneficiaire' => 150000]);
        $this->adherer($this->mamadou);

        $t = $this->facturer($this->mamadou, [$this->consultation, $this->echographie]);

        // 80 000 + 160 000 souhaités, plafond 150 000 : l'échographie est limitée à 70 000.
        $this->assertLignes($t, ['Consultation' => 80000, 'Échographie' => 70000]);
        $this->assertNotEmpty($t->invoice->alertes_assurance);
    }

    public function test_le_plafond_repart_a_zero_a_l_exercice_suivant(): void
    {
        $this->formule->update(['plafond_annuel_beneficiaire' => 100000]);
        $this->adherer($this->mamadou);

        $this->facturer($this->mamadou, [$this->consultation]); // 80 000 consommés
        $this->assertSame('20000.00', (string) $this->facturer($this->mamadou, [$this->consultation])->invoice->getRawOriginal('insurance_amount'));

        $this->travelTo(Carbon::parse('2027-01-05 09:00:00')); // exercice 2027
        $this->assertSame('80000.00', (string) $this->facturer($this->mamadou, [$this->consultation])->invoice->getRawOriginal('insurance_amount'));
    }

    public function test_le_plafond_familial_est_partage(): void
    {
        $this->formule->update(['plafond_annuel_famille' => 100000]);
        $adhesion = $this->adherer($this->mamadou);
        $this->referentiel->ajouterBeneficiaire($adhesion, $this->aissatou, ['lien' => 'conjoint', 'date_debut' => '2026-01-01']);

        $this->facturer($this->mamadou, [$this->consultation]); // 80 000

        $t = $this->facturer($this->aissatou, [$this->consultation]);
        $this->assertSame('20000.00', (string) $t->invoice->getRawOriginal('insurance_amount'));
    }

    public function test_recalculer_une_facture_ne_la_limite_pas_par_sa_propre_consommation(): void
    {
        $this->formule->update(['plafond_annuel_beneficiaire' => 80000]);
        $this->adherer($this->mamadou);

        $t = $this->facturer($this->mamadou, [$this->consultation]);
        $t = app(BillingService::class)->recalculate($t);

        $this->assertSame('80000.00', (string) $t->invoice->getRawOriginal('insurance_amount'));
    }

    public function test_son_propre_contrat_paie_avant_celui_du_conjoint(): void
    {
        $sanlam = InsuranceCompany::create(['name' => 'Sanlam', 'code' => 'SANLAM']);
        $this->conventionner($sanlam);
        $this->referentiel->creerContratIndividuel($this->aissatou, $sanlam, ['policy_number' => 'IND-1', 'coverage_percentage' => 50, 'start_date' => '2026-06-01']);

        $adhesion = $this->adherer($this->mamadou); // plus ancien, mais Aïssatou n'y est qu'ayant droit
        $this->referentiel->ajouterBeneficiaire($adhesion, $this->aissatou, ['lien' => 'conjoint', 'date_debut' => '2026-01-01']);

        $t = $this->facturer($this->aissatou, [$this->consultation]);
        $parts = $t->invoice->items->first()->repartition_assurance;

        // Sanlam 50 % de 100 000, puis NSIA 80 % des 50 000 restants, patient 10 000.
        $this->assertSame('Sanlam', $parts[0]['payeur']);
        $this->assertEquals(50000, $parts[0]['montant']);
        $this->assertEquals(40000, $parts[1]['montant']);
        $this->assertSame('10000.00', (string) $t->invoice->getRawOriginal('patient_amount'));
        $this->assertSame(2, $t->invoice->insuranceClaims()->count());
    }

    public function test_le_tarif_de_convention_est_fixe_une_fois_pour_toute_la_chaine(): void
    {
        $sanlam = InsuranceCompany::create(['name' => 'Sanlam', 'code' => 'SANLAM']);
        $this->conventionner($sanlam, 120000);
        InsuranceCoverage::where('insurance_company_id', $this->nsia->id)->where('coverageable_id', $this->consultation->id)->update(['acte_price' => 90000]);

        $this->referentiel->creerContratIndividuel($this->mamadou, $sanlam, ['policy_number' => 'IND-2', 'coverage_percentage' => 50, 'start_date' => '2026-01-01']);
        $this->adherer($this->mamadou);

        $ligne = $this->facturer($this->mamadou, [$this->consultation])->invoice->items->first();

        // Prix de Sanlam (premier payeur) : 120 000 ; Sanlam 60 000, NSIA 80 % de 60 000 = 48 000.
        $this->assertSame('120000.00', (string) $ligne->getRawOriginal('total_amount'));
        $this->assertSame('108000.00', (string) $ligne->getRawOriginal('insurance_covered_amount'));
    }

    public function test_accord_prealable_sans_bon_puis_avec_bon(): void
    {
        $this->formule->garanties()->create(['famille_acte' => 'imagerie', 'accord_prealable' => true]);
        $adhesion = $this->adherer($this->mamadou);

        $sansBon = $this->facturer($this->mamadou, [$this->echographie]);
        $this->assertSame('0.00', (string) $sansBon->invoice->getRawOriginal('insurance_amount'));
        $this->assertStringContainsString('Accord préalable', $sansBon->invoice->alertes_assurance[0]);

        $bon = PriseEnCharge::create([
            'beneficiaire_id' => $adhesion->beneficiaires()->first()->id, 'numero' => 'PEC-77', 'famille_acte' => 'imagerie',
            'montant_accorde' => 120000, 'date_debut' => '2026-09-01', 'date_fin' => '2026-10-31', 'statut' => PriseEnCharge::ACCORDE,
        ]);

        $avecBon = $this->facturer($this->mamadou, [$this->echographie]);
        $this->assertSame('120000.00', (string) $avecBon->invoice->getRawOriginal('insurance_amount')); // 160 000 souhaités, bon limité à 120 000
        $this->assertSame(120000.0, (float) PecUtilisation::where('prise_en_charge_id', $bon->id)->sum('montant'));
        $this->assertSame(0.0, $bon->resteDisponible());

        app(BillingService::class)->recalculate($avecBon); // le bon n'est pas « consommé deux fois »
        $this->assertSame(120000.0, (float) PecUtilisation::where('prise_en_charge_id', $bon->id)->sum('montant'));
    }

    public function test_verification_des_droits(): void
    {
        $this->formule->update(['plafond_annuel_beneficiaire' => 500000]);
        $this->adherer($this->mamadou);
        $this->facturer($this->mamadou, [$this->consultation]);

        $droits = app(DroitsPatientService::class)->resume($this->mamadou);

        $this->assertCount(1, $droits['actives']);
        $this->assertSame(420000.0, $droits['actives'][0]['reste_beneficiaire']);
    }

    // ------------------------------------------------------------------ Outils

    private function adherer(Patient $patient): Adhesion
    {
        return $this->referentiel->creerAdhesion($this->formule, $patient, ['date_debut' => '2026-01-01']);
    }

    private function patient(string $prenom): Patient
    {
        $p = Patient::create(['first_name' => $prenom, 'last_name' => 'Barry', 'gender' => 'Homme', 'birth_date' => '1985-01-01']);
        $this->etab->patients()->syncWithoutDetaching([$p->id]);

        return $p;
    }

    private function conventionner(InsuranceCompany $organisme, ?float $prixConsultation = null): void
    {
        foreach ([$this->consultation, $this->echographie] as $service) {
            InsuranceCoverage::create([
                'insurance_company_id' => $organisme->id, 'coverageable_type' => Service::class, 'coverageable_id' => $service->id,
                'valid_from' => '2025-01-01', 'status' => 'active',
                'acte_price' => $service->is($this->consultation) && $prixConsultation ? $prixConsultation : $service->amount,
            ]);
        }
    }

    /** @param Service[] $services */
    private function facturer(Patient $patient, array $services): Transaction
    {
        $c = Consultation::create(['patient_id' => $patient->id, 'medecin_id' => $this->medecin->id, 'department_id' => $this->departement->id, 'motif' => 'Suivi', 'diagnostic' => 'RAS']);
        $c->services()->attach(collect($services)->pluck('id')->all());

        return app(BillingService::class)->createFromConsultation($c->fresh())->fresh(['invoice.items']);
    }

    private function assertLignes(Transaction $t, array $attendu): void
    {
        foreach ($attendu as $description => $montant) {
            $ligne = $t->invoice->items->firstWhere('description', $description);
            $this->assertSame(number_format($montant, 2, '.', ''), (string) $ligne->getRawOriginal('insurance_covered_amount'), $description);
        }
    }
}
