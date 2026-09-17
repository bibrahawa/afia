<?php

namespace Tests\Feature\Parcours;

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureModuleActive;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\MotifRdv;
use App\Models\Parcours\Grossesse;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\Parcours\AccueilService;
use App\Services\Parcours\DossierPatientService;
use App\Services\Parcours\GrossesseService;
use App\Services\Parcours\StatistiquesParcoursService;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Lot 3c — dossier en frise, suivi de grossesse, statistiques. MySQL/MariaDB requis. */
class DossierEtGrossesseTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private User $user;
    private Employee $medecin;
    private Department $departement;
    private Service $acte;
    private MotifRdv $motif;
    private Patient $patiente;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->travelTo(Carbon::parse('2026-09-30 09:00:00'));
        $this->withoutMiddleware([CheckPermission::class, Authorize::class, EnsureModuleActive::class]);

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->user = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->user->forceFill(['etablissement_id' => $this->etab->id])->save();
        $this->actingAs($this->user);

        $this->departement = Department::create(['name' => 'Gynécologie']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id, 'user_id' => $this->user->id]);
        $this->acte = Service::create(['name' => 'Consultation prénatale', 'amount' => 80000, 'department_id' => $this->departement->id, 'famille_acte' => 'maternite']);
        $this->motif = MotifRdv::create(['department_id' => $this->departement->id, 'code' => 'cpn', 'nom' => 'CPN', 'duree_minutes_defaut' => 20, 'service_id' => $this->acte->id]);

        $this->patiente = Patient::create(['first_name' => 'Aminata', 'last_name' => 'Bah', 'gender' => 'Femme', 'birth_date' => '1996-02-02']);
        $this->etab->patients()->syncWithoutDetaching([$this->patiente->id]);
    }

    public function test_ouverture_d_un_suivi_terme_et_dpa(): void
    {
        $grossesse = app(GrossesseService::class)->ouvrir($this->patiente, Carbon::parse('2026-03-02'), ['gestite' => 2, 'parite' => 1], $this->user);

        // 02/03/2026 + 280 jours = 07/12/2026 ; au 30/09/2026 : 30 SA 2 j.
        $this->assertSame('2026-12-07', $grossesse->dpa->toDateString());
        $this->assertSame([30, 2], $grossesse->terme());
        $this->assertSame('30 SA 2 j', $grossesse->termeLisible());
    }

    public function test_une_seule_grossesse_en_cours(): void
    {
        app(GrossesseService::class)->ouvrir($this->patiente, Carbon::parse('2026-03-02'), [], $this->user);

        $this->expectException(\App\Exceptions\Parcours\OperationParcoursImpossible::class);
        app(GrossesseService::class)->ouvrir($this->patiente, Carbon::parse('2026-04-02'), [], $this->user);
    }

    public function test_calendrier_cpn_et_rattachement_de_la_consultation(): void
    {
        $grossesse = app(GrossesseService::class)->ouvrir($this->patiente, Carbon::parse('2026-03-02'), [], $this->user);

        $visite = app(AccueilService::class)->arriveeSansRendezVous(
            $this->patiente, $this->medecin, ['motif_rdv_id' => $this->motif->id, 'service_id' => $this->acte->id], $this->user
        );
        app(GrossesseService::class)->rattacher($visite->consultation);
        $visite->consultation->update(['diagnostic' => 'Grossesse évolutive']);

        $this->assertSame($grossesse->id, (int) $visite->consultation->fresh()->grossesse_id);

        $calendrier = collect($grossesse->fresh()->calendrier());
        $this->assertSame(8, $calendrier->count());
        $this->assertSame('faite', $calendrier->firstWhere('semaines', 30)['statut']); // consultation du jour, 30 SA
        $this->assertSame('a_venir', $calendrier->firstWhere('semaines', 38)['statut']);
        $this->assertSame(34, $grossesse->fresh()->prochainContact()['semaines']);
    }

    public function test_correction_de_la_ddr_et_cloture(): void
    {
        $service = app(GrossesseService::class);
        $grossesse = $service->ouvrir($this->patiente, Carbon::parse('2026-03-02'), [], $this->user);

        $service->corrigerDdr($grossesse, Carbon::parse('2026-03-16'));
        $this->assertSame('2026-12-21', $grossesse->fresh()->dpa->toDateString());

        $service->cloturer($grossesse->fresh(), 'accouchement', Carbon::parse('2026-09-29'), 'Garçon, 3,1 kg');
        $this->assertSame(Grossesse::TERMINEE, $grossesse->fresh()->statut);
        $this->assertNull($service->enCours($this->patiente));
    }

    public function test_frise_du_dossier(): void
    {
        app(GrossesseService::class)->ouvrir($this->patiente, Carbon::parse('2026-03-02'), [], $this->user);
        $visite = app(AccueilService::class)->arriveeSansRendezVous($this->patiente, $this->medecin, ['service_id' => $this->acte->id], $this->user);
        $visite->consultation->update(['diagnostic' => 'Grossesse évolutive']);

        $frise = app(DossierPatientService::class)->frise($this->patiente);

        $this->assertSame(['consultation', 'grossesse'], $frise->pluck('type')->unique()->sort()->values()->all());
        $this->assertSame('Grossesse évolutive', $frise->firstWhere('type', 'consultation')['titre']);

        $filtree = app(DossierPatientService::class)->frise($this->patiente, ['types' => ['grossesse']]);
        $this->assertCount(1, $filtree);
    }

    public function test_statistiques_de_la_periode(): void
    {
        $accueil = app(AccueilService::class);

        $visite = $accueil->arriveeSansRendezVous($this->patiente, $this->medecin, ['service_id' => $this->acte->id], $this->user);
        $this->travelTo(now()->addMinutes(20));
        $accueil->appeler($visite, $this->medecin);
        $visite->consultation->update(['diagnostic' => 'Grossesse évolutive']);
        $accueil->terminer($visite->fresh());

        $autre = $accueil->arriveeSansRendezVous($this->patientSecondaire(), $this->medecin, [], $this->user);
        $accueil->marquerPartie($autre, 'Attente trop longue');

        $stats = app(StatistiquesParcoursService::class)->resume(today(), today());

        $this->assertSame(2, $stats['visites']);
        $this->assertSame(1, $stats['terminees']);
        $this->assertSame(1, $stats['parties']);
        $this->assertSame(20, $stats['attente_moyenne']);
        $this->assertSame(2, $stats['sans_rendez_vous']);
        $this->assertSame(80000.0, $stats['recette_actes']);
        $this->assertSame(1, $stats['diagnostics']['Grossesse évolutive']);
    }

    private function patientSecondaire(): Patient
    {
        $p = Patient::create(['first_name' => 'Kadia', 'last_name' => 'Sow', 'gender' => 'Femme', 'birth_date' => '1999-01-01']);
        $this->etab->patients()->syncWithoutDetaching([$p->id]);

        return $p;
    }
}
