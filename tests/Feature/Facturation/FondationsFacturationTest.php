<?php

namespace Tests\Feature\Facturation;

use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\ConsultationService;
use App\Support\Etablissement\IdentiteDocument;
use App\Support\Facturation\TypesFacturables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Lot 0 — fondations de la facturation. MySQL/MariaDB requis (comme les
 * autres tests de cloisonnement). FacturationServiceProvider doit être
 * déclaré dans bootstrap/providers.php.
 */
class FondationsFacturationTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $a;
    private Etablissement $b;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = Etablissement::create(['nom' => 'Clinique Alpha', 'slug' => 'alpha', 'type' => 'clinique', 'statut' => 'actif', 'adresse' => 'Kaloum', 'contact' => '620 00 00 01']);
        $this->b = Etablissement::create(['nom' => 'Clinique Bêta', 'slug' => 'beta', 'type' => 'clinique', 'statut' => 'actif']);
        $this->userA = $this->utilisateur($this->a);
        $this->userB = $this->utilisateur($this->b);
    }

    // ------------------------------------------------------------------
    // Types polymorphes
    // ------------------------------------------------------------------

    public function test_la_carte_des_types_est_enregistree(): void
    {
        $this->assertSame('service', TypesFacturables::alias(Service::class));
        $this->assertSame('service', (new Service)->getMorphClass());
        $this->assertSame(['service', Service::class], TypesFacturables::variantes(Service::class));
    }

    public function test_une_saisie_de_formulaire_ne_peut_pas_designer_une_classe_arbitraire(): void
    {
        $this->assertSame(Service::class, TypesFacturables::depuisSaisie('Service', TypesFacturables::ACTES_COUVRABLES));
        $this->assertSame(\App\Models\Medicament::class, TypesFacturables::depuisSaisie('Médicament', TypesFacturables::ACTES_COUVRABLES));
        $this->assertSame(\App\Models\Test::class, TypesFacturables::depuisSaisie('examens', TypesFacturables::ACTES_COUVRABLES));
        $this->assertNull(TypesFacturables::depuisSaisie(User::class, TypesFacturables::ACTES_COUVRABLES));
        $this->assertNull(TypesFacturables::depuisSaisie('consultation', TypesFacturables::ACTES_COUVRABLES));
    }

    public function test_un_nom_de_classe_ecrit_par_un_ancien_code_est_enregistre_sous_alias(): void
    {
        $this->actingAs($this->userA);
        [$assureur, $service] = $this->assureurEtService();

        $couverture = InsuranceCoverage::create([
            'insurance_company_id' => $assureur->id,
            'coverageable_type' => Service::class, // ancienne écriture
            'coverageable_id' => $service->id,
            'valid_from' => now()->subDay(),
            'acte_price' => 50000,
        ]);

        $this->assertSame('service', DB::table('insurance_coverages')->where('id', $couverture->id)->value('coverageable_type'));
        $this->assertTrue($couverture->fresh()->coverageable->is($service));
    }

    public function test_une_convention_encore_stockee_avec_nom_de_classe_reste_trouvee(): void
    {
        $this->actingAs($this->userA);
        [$assureur, $service] = $this->assureurEtService();

        DB::table('insurance_coverages')->insert([
            'etablissement_id' => $this->a->id,
            'insurance_company_id' => $assureur->id,
            'coverageable_type' => Service::class,
            'coverageable_id' => $service->id,
            'valid_from' => now()->subDay()->toDateString(),
            'status' => 'active',
            'acte_price' => 42000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $trouvee = $assureur->getCoverageForService(TypesFacturables::alias(Service::class), $service->id, $assureur->id);

        $this->assertNotNull($trouvee);
        $this->assertSame('42000.00', (string) $trouvee->acte_price);
    }

    public function test_la_migration_convertit_les_anciens_noms_de_classe(): void
    {
        $this->actingAs($this->userA);
        [$assureur, $service] = $this->assureurEtService();

        $id = DB::table('insurance_coverages')->insertGetId([
            'etablissement_id' => $this->a->id, 'insurance_company_id' => $assureur->id,
            'coverageable_type' => Service::class, 'coverageable_id' => $service->id,
            'valid_from' => now()->toDateString(), 'status' => 'active', 'acte_price' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $idLibelle = DB::table('insurance_coverages')->insertGetId([
            'etablissement_id' => $this->a->id, 'insurance_company_id' => $assureur->id,
            'coverageable_type' => 'Package', 'coverageable_id' => 1,
            'valid_from' => now()->toDateString(), 'status' => 'active', 'acte_price' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/facturation/2026_09_22_090001_convertir_types_polymorphes_en_alias.php');
        $migration->up();

        $this->assertSame('service', DB::table('insurance_coverages')->where('id', $id)->value('coverageable_type'));
        $this->assertSame('package', DB::table('insurance_coverages')->where('id', $idLibelle)->value('coverageable_type'));
    }

    public function test_une_convention_sur_un_type_non_couvrable_est_refusee(): void
    {
        $this->actingAs($this->userA);
        [$assureur] = $this->assureurEtService();

        $this->expectException(\LogicException::class);
        InsuranceCoverage::create([
            'insurance_company_id' => $assureur->id,
            'coverageable_type' => User::class,
            'coverageable_id' => $this->userA->id,
            'valid_from' => now(),
            'acte_price' => 1,
        ]);
    }

    // ------------------------------------------------------------------
    // Identité des documents
    // ------------------------------------------------------------------

    public function test_l_identite_des_documents_est_celle_de_l_etablissement_courant(): void
    {
        $this->actingAs($this->userA);
        $this->assertSame('Clinique Alpha', IdentiteDocument::courante()->nom);
        $this->assertSame('Kaloum · 620 00 00 01', IdentiteDocument::courante()->coordonnees());

        $this->actingAs($this->userB);
        $this->assertSame('Clinique Bêta', IdentiteDocument::courante()->nom);
        $this->assertNull(IdentiteDocument::courante()->logoWeb());
    }

    public function test_une_ordonnance_de_la_clinique_b_ne_porte_plus_l_entete_aprosafe(): void
    {
        $this->actingAs($this->userB);
        $consultation = $this->consultation();

        $html = view('consultations.rapport.ordonnance-a5', [
            'consultation' => $consultation->load(['patient', 'medecin', 'department', 'medicaments']),
            'identite' => IdentiteDocument::courante(),
        ])->render();

        $this->assertStringContainsString('Clinique Bêta', $html);
        $this->assertStringNotContainsStringIgnoringCase('aprosafe', $html);
        $this->assertStringNotContainsString('628 16 44 22', $html);
    }

    // ------------------------------------------------------------------
    // Actes d'une autre clinique
    // ------------------------------------------------------------------

    public function test_on_ne_peut_pas_rattacher_le_service_d_une_autre_clinique(): void
    {
        $this->actingAs($this->userA);
        [, $serviceA] = $this->assureurEtService();

        $this->actingAs($this->userB);
        $consultation = $this->consultation();

        $this->expectException(ValidationException::class);
        app(ConsultationService::class)->attachItems($consultation, ['services' => [['id' => $serviceA->id]]]);
    }

    // ------------------------------------------------------------------
    // Outils
    // ------------------------------------------------------------------

    private function utilisateur(Etablissement $etab): User
    {
        $u = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $u->forceFill(['etablissement_id' => $etab->id])->save();

        return $u;
    }

    /** @return array{0: InsuranceCompany, 1: Service} dans l'établissement courant */
    private function assureurEtService(): array
    {
        $departement = Department::create(['name' => 'Médecine générale']);
        $assureur = InsuranceCompany::create(['name' => 'NSIA', 'code' => 'NSIA-' . Str::random(4)]);
        $service = Service::create(['name' => 'Consultation', 'amount' => 100000, 'department_id' => $departement->id]);

        return [$assureur, $service];
    }

    private function consultation(): Consultation
    {
        $departement = Department::create(['name' => 'Gynécologie']);
        $medecin = Employee::create([
            'first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry',
            'type' => 'Doctor', 'department_id' => $departement->id,
        ]);
        $patient = Patient::create(['first_name' => 'Fatou', 'last_name' => 'Camara', 'gender' => 'Femme']);

        return Consultation::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'department_id' => $departement->id,
            'motif' => 'Contrôle',
            'diagnostic' => 'RAS',
        ]);
    }
}
