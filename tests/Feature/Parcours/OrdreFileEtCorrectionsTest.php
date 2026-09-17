<?php

namespace Tests\Feature\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureModuleActive;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\MotifRdv;
use App\Models\Parcours\Grossesse;
use App\Models\Parcours\Visite;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Parcours\AccueilService;
use App\Services\Parcours\ConsultationRapideService;
use App\Services\Parcours\GrossesseService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Lot 3d — ordre de la file décidé par l'accueil et corrections du parcours. */
class OrdreFileEtCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private User $user;
    private Employee $medecin;
    private Department $departement;
    private Service $acte;
    private Service $acteMaternite;
    private MotifRdv $motif;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->travelTo(Carbon::parse('2026-10-01 08:00:00'));
        $this->withoutMiddleware([CheckPermission::class, Authorize::class, EnsureModuleActive::class]);

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->user = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->user->forceFill(['etablissement_id' => $this->etab->id])->save();
        $this->actingAs($this->user);

        $this->departement = Department::create(['name' => 'Gynécologie']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id, 'user_id' => $this->user->id]);
        $this->acte = Service::create(['name' => 'Consultation générale', 'amount' => 100000, 'department_id' => $this->departement->id, 'famille_acte' => 'consultation']);
        $this->acteMaternite = Service::create(['name' => 'Consultation prénatale', 'amount' => 80000, 'department_id' => $this->departement->id, 'famille_acte' => 'maternite']);
        $this->motif = MotifRdv::create(['department_id' => $this->departement->id, 'code' => 'consult', 'nom' => 'Consultation', 'duree_minutes_defaut' => 15, 'service_id' => $this->acte->id]);
    }

    public function test_l_accueil_fait_passer_un_patient_en_tete(): void
    {
        $premier = $this->arrivee('Fanta');
        $this->travelTo(now()->addMinutes(5));
        $second = $this->arrivee('Ousmane');

        app(AccueilService::class)->placerEnTete($second);

        $this->assertSame([$second->id, $premier->id], $this->file());
    }

    public function test_monter_et_descendre_d_une_place(): void
    {
        $a = $this->arrivee('Fanta');
        $this->travelTo(now()->addMinutes(5));
        $b = $this->arrivee('Ousmane');
        $this->travelTo(now()->addMinutes(5));
        $c = $this->arrivee('Mariam');

        app(AccueilService::class)->deplacer($c, -1);
        $this->assertSame([$a->id, $c->id, $b->id], $this->file());

        app(AccueilService::class)->deplacer($a->fresh(), 1);
        $this->assertSame([$c->id, $a->id, $b->id], $this->file());
    }

    public function test_regle_rendez_vous_d_abord(): void
    {
        $sansRdv = $this->arrivee('Fanta');
        $this->travelTo(now()->addMinutes(10));

        $patiente = $this->patient('Aïcha');
        $rdv = Appointment::create([
            'employee_id' => $this->medecin->id, 'patient_id' => $patiente->id, 'motif_rdv_id' => $this->motif->id,
            'duree_minutes' => 15, 'appointment_date' => today()->toDateString(), 'appointment_time' => '09:00:00', 'status' => 'confirmed',
        ]);
        $avecRdv = app(AccueilService::class)->arriveeDepuisRendezVous($rdv, [], $this->user);

        $this->etab->update(['ordre_file' => Visite::ORDRE_RENDEZ_VOUS]);
        $this->assertSame([$avecRdv->id, $sansRdv->id], $this->file());

        $this->etab->update(['ordre_file' => Visite::ORDRE_ARRIVEE]);
        $this->assertSame([$sansRdv->id, $avecRdv->id], $this->file());
    }

    public function test_deux_arrivees_simultanees_impossibles(): void
    {
        $patient = $this->patient('Fanta');
        app(AccueilService::class)->arriveeSansRendezVous($patient, $this->medecin, [], $this->user);

        $this->expectException(OperationParcoursImpossible::class);
        app(AccueilService::class)->arriveeSansRendezVous($patient, $this->medecin, [], $this->user);
    }

    public function test_un_acte_encaisse_ne_peut_plus_etre_retire(): void
    {
        $visite = $this->arrivee('Fanta', $this->acte->id);
        $transaction = $visite->consultation->transaction()->first();
        app(PaymentService::class)->payPatientTransaction($transaction, 100000, 'CASH');

        try {
            app(ConsultationRapideService::class)->enregistrer($visite->consultation->fresh(), [
                'action' => 'terminer', 'diagnostic' => 'Paludisme simple', 'actes' => [],
            ], $this->user);
            $this->fail('Le retrait d\'un acte encaissé devait être refusé.');
        } catch (OperationParcoursImpossible $e) {
            $this->assertStringContainsString('Consultation générale', $e->getMessage());
        }

        // Rien n'a été enregistré : la consultation garde son acte et son diagnostic vide.
        $this->assertSame(1, $visite->consultation->fresh()->services()->count());
        $this->assertNull($visite->consultation->fresh()->diagnostic);
    }

    public function test_patient_reparti_la_facture_est_annulee(): void
    {
        $visite = $this->arrivee('Fanta', $this->acte->id);
        $transaction = $visite->consultation->transaction()->first();

        app(AccueilService::class)->marquerPartie($visite, 'Attente trop longue');

        $this->assertSame(StatutVisite::Partie, $visite->fresh()->statut);
        $this->assertSame('cancel', Transaction::findOrFail($transaction->id)->status);
        $this->assertSame(0.0, (float) $transaction->fresh()->account->balance);
    }

    public function test_seule_une_consultation_de_maternite_valide_une_cpn(): void
    {
        $patiente = $this->patient('Aïcha');
        app(GrossesseService::class)->ouvrir($patiente, Carbon::parse('2026-03-02'), [], $this->user);

        $paludisme = app(AccueilService::class)->arriveeSansRendezVous($patiente, $this->medecin, ['service_id' => $this->acte->id], $this->user);
        app(GrossesseService::class)->rattacher($paludisme->consultation);
        $this->assertNull($paludisme->consultation->fresh()->grossesse_id);

        app(AccueilService::class)->terminer($paludisme);

        $cpn = app(AccueilService::class)->arriveeSansRendezVous($patiente, $this->medecin, ['service_id' => $this->acteMaternite->id], $this->user);
        app(GrossesseService::class)->rattacher($cpn->consultation);

        $grossesse = Grossesse::where('patient_id', $patiente->id)->firstOrFail();
        $this->assertSame($grossesse->id, (int) $cpn->consultation->fresh()->grossesse_id);
        $this->assertSame(1, $grossesse->consultations()->count());
    }

    // ------------------------------------------------------------------ Outils

    private function file(): array
    {
        return Visite::duJour()->where('medecin_id', $this->medecin->id)->actives()->ordreFile()->pluck('id')->all();
    }

    private function arrivee(string $prenom, ?int $serviceId = null): Visite
    {
        return app(AccueilService::class)->arriveeSansRendezVous(
            $this->patient($prenom), $this->medecin, ['service_id' => $serviceId], $this->user
        );
    }

    private function patient(string $prenom): Patient
    {
        $p = Patient::create(['first_name' => $prenom, 'last_name' => 'Camara', 'gender' => 'Femme', 'birth_date' => '1993-03-03']);
        $this->etab->patients()->syncWithoutDetaching([$p->id]);

        return $p;
    }
}
