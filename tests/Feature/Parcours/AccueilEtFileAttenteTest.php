<?php

namespace Tests\Feature\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureModuleActive;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\MotifRdv;
use App\Models\Parcours\Visite;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\Parcours\AccueilService;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Lot 3a — accueil, constantes, file d'attente. MySQL/MariaDB requis. */
class AccueilEtFileAttenteTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private User $accueil;
    private User $userMedecin;
    private Employee $medecin;
    private Employee $confrere;
    private Department $departement;
    private MotifRdv $motif;
    private Service $acte;
    private Patient $patient;
    private AccueilService $service;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Queue::fake(); // aucun SMS pendant les tests
        $this->travelTo(Carbon::parse('2026-09-28 08:30:00'));

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->accueil = $this->utilisateur();
        $this->userMedecin = $this->utilisateur();
        $this->actingAs($this->accueil);

        $this->departement = Department::create(['name' => 'Médecine générale']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id, 'user_id' => $this->userMedecin->id]);
        $this->confrere = Employee::create(['first_name' => 'Sékou', 'last_name' => 'Touré', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id]);

        $this->acte = Service::create(['name' => 'Consultation générale', 'amount' => 100000, 'department_id' => $this->departement->id]);
        $this->motif = MotifRdv::create(['department_id' => $this->departement->id, 'code' => 'consult', 'nom' => 'Consultation', 'duree_minutes_defaut' => 15, 'service_id' => $this->acte->id]);

        $this->patient = $this->creerPatient('Fanta');
        $this->service = app(AccueilService::class);
    }

    public function test_arrivee_depuis_un_rendez_vous_ouvre_la_consultation_et_facture_l_acte(): void
    {
        $rdv = $this->rendezVous();

        $visite = $this->service->arriveeDepuisRendezVous($rdv, [], $this->accueil);

        $this->assertSame(StatutVisite::EnAttente, $visite->statut);
        $this->assertSame($rdv->id, $visite->appointment_id);
        $consultation = $visite->consultation;
        $this->assertSame(Consultation::EN_COURS, $consultation->statut);
        $this->assertSame('Consultation', $consultation->motif);
        $this->assertSame($this->medecin->id, (int) $consultation->medecin_id);
        $this->assertSame('100000.00', (string) $consultation->transaction->getRawOriginal('total'));
    }

    public function test_une_arrivee_n_est_enregistree_qu_une_fois(): void
    {
        $rdv = $this->rendezVous();
        $this->service->arriveeDepuisRendezVous($rdv, [], $this->accueil);

        $this->expectException(OperationParcoursImpossible::class);
        $this->service->arriveeDepuisRendezVous($rdv->fresh(), [], $this->accueil);
    }

    public function test_les_urgences_passent_en_tete_de_file(): void
    {
        $premier = $this->service->arriveeSansRendezVous($this->patient, $this->medecin, [], $this->accueil);
        $this->travelTo(now()->addMinutes(10));
        $urgence = $this->service->arriveeSansRendezVous($this->creerPatient('Ousmane'), $this->medecin, ['urgence' => true], $this->accueil);

        $file = Visite::duJour()->where('medecin_id', $this->medecin->id)->ordreFile()->pluck('id')->all();

        $this->assertSame([$urgence->id, $premier->id], $file);
    }

    public function test_un_autre_medecin_ne_peut_pas_appeler_le_patient(): void
    {
        $visite = $this->service->arriveeSansRendezVous($this->patient, $this->medecin, [], $this->accueil);

        $this->expectException(OperationParcoursImpossible::class);
        $this->service->appeler($visite, $this->confrere);
    }

    public function test_constantes_imc_et_taille_reprise(): void
    {
        $visite1 = $this->service->arriveeSansRendezVous($this->patient, $this->medecin, [], $this->accueil);
        $this->service->enregistrerConstantes($visite1, ['poids_kg' => 70, 'taille_cm' => 175, 'temperature' => 38.6], $this->accueil);
        $this->service->terminer($visite1);

        $visite2 = $this->service->arriveeSansRendezVous($this->patient, $this->medecin, [], $this->accueil);
        $constante = $this->service->enregistrerConstantes($visite2, ['poids_kg' => 72], $this->accueil);

        $this->assertSame('175.0', (string) $constante->getRawOriginal('taille_cm'));
        $this->assertSame(23.5, $constante->imc());
        $this->assertContains('température', $visite1->constantes()->first()->alertes());
    }

    public function test_le_medecin_enregistre_la_consultation_et_la_visite_se_termine(): void
    {
        $this->withoutMiddleware([CheckPermission::class, Authorize::class, EnsureModuleActive::class]);
        $rdv = $this->rendezVous();
        $visite = $this->service->arriveeDepuisRendezVous($rdv, [], $this->accueil);

        $this->actingAs($this->userMedecin);
        $this->service->appeler($visite, $this->medecin);

        $this->put(route('consultation.update', $visite->consultation), [
            'patient_id' => $this->patient->id,
            'motif' => 'Consultation',
            'diagnostic' => 'Paludisme simple',
            'services' => [$this->acte->id],
        ])->assertRedirect(route('parcours.file.index'));

        $visite->refresh();
        $this->assertSame(StatutVisite::Terminee, $visite->statut);
        $this->assertSame('completed', $rdv->fresh()->status);
        $this->assertSame(Consultation::TERMINEE, $visite->consultation->statut);
        $this->assertSame($this->medecin->id, (int) $visite->consultation->medecin_id);
    }

    public function test_un_confrere_ne_peut_pas_modifier_la_consultation(): void
    {
        $this->withoutMiddleware([CheckPermission::class, Authorize::class, EnsureModuleActive::class]);
        $visite = $this->service->arriveeSansRendezVous($this->patient, $this->medecin, [], $this->accueil);

        $userConfrere = $this->utilisateur();
        $this->confrere->update(['user_id' => $userConfrere->id]);
        $this->actingAs($userConfrere);

        $this->put(route('consultation.update', $visite->consultation), [
            'patient_id' => $this->patient->id, 'motif' => 'X', 'diagnostic' => 'Y',
        ])->assertForbidden();
    }

    public function test_rendez_vous_absent(): void
    {
        $rdv = $this->rendezVous();

        $this->service->marquerAbsent($rdv);

        $this->assertSame('no_show', $rdv->fresh()->status);
    }

    // ------------------------------------------------------------------ Outils

    private function utilisateur(): User
    {
        $u = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $u->forceFill(['etablissement_id' => $this->etab->id])->save();

        return $u;
    }

    private function creerPatient(string $prenom): Patient
    {
        $p = Patient::create(['first_name' => $prenom, 'last_name' => 'Camara', 'gender' => 'Femme', 'birth_date' => '1990-05-05']);
        $this->etab->patients()->syncWithoutDetaching([$p->id]);

        return $p;
    }

    private function rendezVous(): Appointment
    {
        return Appointment::create([
            'employee_id' => $this->medecin->id, 'patient_id' => $this->patient->id, 'motif_rdv_id' => $this->motif->id,
            'duree_minutes' => 15, 'appointment_date' => today()->toDateString(), 'appointment_time' => '09:00:00', 'status' => 'confirmed',
        ]);
    }
}
