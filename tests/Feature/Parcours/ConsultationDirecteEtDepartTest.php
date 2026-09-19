<?php

namespace Tests\Feature\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureModuleActive;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Patient;
use App\Models\User;
use App\Services\Parcours\AccueilService;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Patient reparti et consultation directe du médecin. MySQL/MariaDB requis. */
class ConsultationDirecteEtDepartTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Employee $medecin;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->withoutMiddleware([CheckPermission::class, Authorize::class, EnsureModuleActive::class]);

        $etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->user = User::create(['name' => 'Dr Awa Diallo', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->user->forceFill(['etablissement_id' => $etab->id])->save();
        $this->actingAs($this->user);

        $departement = Department::create(['name' => 'Médecine générale']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $departement->id, 'user_id' => $this->user->id]);

        $this->patient = Patient::create(['first_name' => 'Sékou', 'last_name' => 'Kourouma', 'gender' => 'Homme', 'birth_date' => '1980-01-01']);
        $etab->patients()->syncWithoutDetaching([$this->patient->id]);
    }

    public function test_un_patient_reparti_sans_acte_ferme_sa_consultation(): void
    {
        $accueil = app(AccueilService::class);
        $visite = $accueil->arriveeSansRendezVous($this->patient, $this->medecin, [], $this->user);

        $accueil->marquerPartie($visite, 'Attente trop longue');

        $this->assertSame(StatutVisite::Partie, $visite->fresh()->statut);
        $this->assertSame(Consultation::ANNULEE, $visite->consultation->fresh()->statut);
    }

    public function test_un_patient_reparti_sans_annuler_la_facture_ferme_aussi_sa_consultation(): void
    {
        $accueil = app(AccueilService::class);
        $visite = $accueil->arriveeSansRendezVous($this->patient, $this->medecin, [], $this->user);

        $accueil->marquerPartie($visite, null, annulerFacture: false);

        $this->assertSame(Consultation::ANNULEE, $visite->consultation->fresh()->statut);
    }

    public function test_une_consultation_annulee_ne_s_ouvre_plus(): void
    {
        $accueil = app(AccueilService::class);
        $visite = $accueil->arriveeSansRendezVous($this->patient, $this->medecin, [], $this->user);
        $accueil->marquerPartie($visite);

        $this->get(route('parcours.consultation.show', $visite->consultation))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_la_consultation_directe_ouvre_l_ecran_de_consultation_et_passe_par_la_file(): void
    {
        $reponse = $this->post(route('parcours.consultation.directe'), ['patient_id' => $this->patient->id, 'motif' => 'Contrôle']);

        $consultation = Consultation::where('patient_id', $this->patient->id)->latest('id')->firstOrFail();
        $reponse->assertRedirect(route('parcours.consultation.show', $consultation));

        // Tracée comme une arrivée : visite du jour, prise en charge par ce médecin.
        $this->assertSame(StatutVisite::EnConsultation, $consultation->visite->statut);
        $this->assertSame($this->medecin->id, (int) $consultation->visite->medecin_id);
        $this->assertNotNull($consultation->visite->appele_le);
    }

    public function test_pas_deux_consultations_directes_le_meme_jour(): void
    {
        $this->post(route('parcours.consultation.directe'), ['patient_id' => $this->patient->id]);
        $this->post(route('parcours.consultation.directe'), ['patient_id' => $this->patient->id])->assertSessionHas('error');

        $this->assertSame(1, Consultation::where('patient_id', $this->patient->id)->count());
    }
}
