<?php

namespace Tests\Feature\Rapports;

use App\Http\Middleware\CheckPermission;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\Parcours\AccueilService;
use App\Services\PaymentService;
use App\Services\Rapports\RapportService;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Lot 5a — écrans de rapports. */
class RapportsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private User $user;
    private Employee $medecin;
    private Service $acte;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->travelTo(Carbon::parse('2026-10-10 09:00:00'));
        $this->withoutMiddleware([CheckPermission::class, Authorize::class, \App\Http\Middleware\EnsureModuleActive::class]);

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->user = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->user->forceFill(['etablissement_id' => $this->etab->id])->save();
        $this->actingAs($this->user);

        $departement = Department::create(['name' => 'Médecine générale']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $departement->id, 'user_id' => $this->user->id]);
        $this->acte = Service::create(['name' => 'Consultation générale', 'amount' => 100000, 'department_id' => $departement->id, 'famille_acte' => 'consultation']);
    }

    public function test_le_catalogue_liste_les_rapports_disponibles(): void
    {
        $catalogue = app(RapportService::class)->catalogue();

        $this->assertArrayHasKey('activite', $catalogue);
        $this->assertArrayHasKey('recettes', $catalogue);
        $this->assertArrayHasKey('impayes', $catalogue);
        $this->assertTrue(app(RapportService::class)->existe('assurance'));
        $this->assertFalse(app(RapportService::class)->existe('inconnu'));
    }

    public function test_rapport_d_activite_et_de_recettes(): void
    {
        $visite = $this->recevoirUnPatient();
        app(PaymentService::class)->payPatientTransaction($visite->consultation->transaction()->first(), 60000, 'CASH');

        $rapports = app(RapportService::class);

        $activite = $rapports->produire('activite', today(), today());
        $this->assertSame('Activité de la clinique', $activite['titre']);
        $this->assertSame(1, $activite['indicateurs']['Patients reçus']);

        $recettes = $rapports->produire('recettes', today(), today());
        $this->assertStringContainsString('60 000', $recettes['indicateurs']['Total encaissé']);
    }

    public function test_rapport_des_impayes(): void
    {
        $this->recevoirUnPatient();

        $impayes = app(RapportService::class)->produire('impayes', today(), today());

        // Rien n'a été encaissé : la totalité de l'acte reste due.
        $this->assertSame(1, $impayes['indicateurs']['Factures non soldées']);
        $this->assertStringContainsString('100 000', $impayes['indicateurs']['Reste à encaisser']);
    }

    public function test_ecran_et_export_csv(): void
    {
        $this->recevoirUnPatient();

        $this->get(route('rapports.index'))->assertOk()->assertSee('Rapports');

        $this->get(route('rapports.show', 'activite') . '?debut=' . today()->toDateString() . '&fin=' . today()->toDateString())
            ->assertOk()
            ->assertSee('Activité de la clinique');

        $reponse = $this->get(route('rapports.csv', 'activite') . '?debut=' . today()->toDateString() . '&fin=' . today()->toDateString());
        $reponse->assertOk();
        $this->assertStringContainsString('text/csv', $reponse->headers->get('Content-Type'));
        $this->assertStringContainsString('Patients reçus', $reponse->streamedContent());
    }

    public function test_rapport_inconnu_renvoie_404(): void
    {
        $this->get(route('rapports.show', 'inexistant'))->assertNotFound();
    }

    private function recevoirUnPatient()
    {
        $patient = Patient::create(['first_name' => 'Fanta', 'last_name' => 'Camara', 'gender' => 'Femme', 'birth_date' => '1990-01-01']);
        $this->etab->patients()->syncWithoutDetaching([$patient->id]);

        return app(AccueilService::class)->arriveeSansRendezVous($patient, $this->medecin, ['service_id' => $this->acte->id], $this->user);
    }
}
