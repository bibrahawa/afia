<?php

namespace Tests\Feature\RendezVous;

use App\Models\Appointment;
use App\Models\ComptePatient;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAvailability;
use App\Models\EmployeeLeave;
use App\Models\Etablissement;
use App\Models\MotifRdv;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentBookingService;
use App\Services\DisponibiliteService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Correctifs RDV du 21/09/2026. Base MySQL/MariaDB de test requise
 * (migrations avec UPDATE … JOIN), comme CloisonnementTest.
 *
 * Référence temporelle : lundi 21/09/2026. Planning du médecin : 08:00-12:00
 * tous les jours. Motif : 15 min, marge 0 (sauf mention contraire).
 */
class PriseRendezVousTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $a;
    private Etablissement $b;
    private User $userA;
    private User $userB;
    private Department $departement;
    private Employee $medecin;
    private MotifRdv $motif;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake(); // aucun SMS réel pendant les tests

        $this->a = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->b = Etablissement::create(['nom' => 'Clinique B', 'slug' => 'clinique-b', 'type' => 'clinique', 'statut' => 'actif']);
        $this->userA = $this->utilisateur($this->a);
        $this->userB = $this->utilisateur($this->b);

        $this->actingAs($this->userA);

        $this->departement = Department::create(['name' => 'Gynécologie']);
        $this->medecin = $this->creerMedecin('Awa');
        $this->motif = MotifRdv::create([
            'department_id' => $this->departement->id,
            'code' => 'consultation',
            'nom' => 'Consultation',
            'duree_minutes_defaut' => 15,
            'marge_tampon_minutes' => 0,
        ]);

        $this->patient = $this->creerPatient('622000001');

        $this->travelTo(Carbon::parse('2026-09-21 10:07:23'));
    }

    // ------------------------------------------------------------------
    // Jour même
    // ------------------------------------------------------------------

    public function test_jour_meme_les_creneaux_restent_sur_la_grille(): void
    {
        $creneaux = $this->heures(Carbon::today());

        $this->assertSame('10:15', $creneaux[0]);
        foreach ($creneaux as $heure) {
            $this->assertSame(0, (int) substr($heure, 3, 2) % 15, "Créneau hors grille : {$heure}");
        }
    }

    public function test_reservation_jour_meme_quelques_minutes_apres_affichage_reussit(): void
    {
        $this->assertContains('10:15', $this->heures(Carbon::today()));

        $this->travelTo(Carbon::parse('2026-09-21 10:18:40')); // créneau commencé depuis 3 min 40

        $rdv = $this->reserver('10:15');

        $this->assertSame('10:15', $rdv->appointment_time->format('H:i'));
        $this->assertSame((int) $this->a->id, (int) $rdv->etablissement_id);
    }

    public function test_creneau_depasse_au_dela_de_la_tolerance_refuse(): void
    {
        $this->travelTo(Carbon::parse('2026-09-21 10:21:00'));

        $this->expectException(DomainException::class);
        $this->reserver('10:15');
    }

    // ------------------------------------------------------------------
    // Congés
    // ------------------------------------------------------------------

    public function test_conge_refuse_ne_bloque_pas_l_agenda(): void
    {
        $demain = Carbon::tomorrow();
        EmployeeLeave::create([
            'employee_id' => $this->medecin->id,
            'start_date' => $demain->copy()->setTime(8, 0),
            'end_date' => $demain->copy()->setTime(12, 0),
            'type' => 'Vacance',
            'status' => 'rejected',
        ]);

        $this->assertCount(16, $this->heures($demain));
    }

    public function test_conge_en_attente_bloque_l_agenda(): void
    {
        $demain = Carbon::tomorrow();
        EmployeeLeave::create([
            'employee_id' => $this->medecin->id,
            'start_date' => $demain->copy()->setTime(8, 0),
            'end_date' => $demain->copy()->setTime(10, 0),
            'type' => 'Vacance',
            'status' => 'pending',
        ]);

        $heures = $this->heures($demain);
        $this->assertSame('10:00', $heures[0]);
        $this->assertCount(8, $heures);
    }

    public function test_jours_disponibles_sur_periode(): void
    {
        $jours = app(DisponibiliteService::class)->joursDisponibles($this->medecin, Carbon::today(), 7, $this->motif);

        $this->assertCount(7, $jours);
        $this->assertSame(Carbon::today()->toDateString(), $jours->first());
    }

    // ------------------------------------------------------------------
    // Règle médecin / motif
    // ------------------------------------------------------------------

    public function test_surcharge_de_duree_ne_restreint_pas_les_autres_medecins(): void
    {
        $autre = $this->creerMedecin('Binta');
        $this->medecin->motifsAssocies()->attach($this->motif->id, ['actif' => true, 'duree_minutes' => 30]);

        $this->assertTrue($autre->peutPratiquerMotif($this->motif));
        $this->assertSame(30, $this->motif->dureePour($this->medecin));
        $this->assertSame(15, $this->motif->dureePour($autre));
    }

    public function test_medecin_exclu_d_un_motif_n_a_aucun_creneau(): void
    {
        $this->medecin->motifsAssocies()->attach($this->motif->id, ['actif' => false]);

        $this->assertSame([], $this->heures(Carbon::tomorrow()));
    }

    // ------------------------------------------------------------------
    // Reprogrammation et rdv fixé par le médecin
    // ------------------------------------------------------------------

    public function test_reprogrammation_reinitialise_les_rappels_et_conserve_le_statut(): void
    {
        $rdv = $this->reserver('11:00', Carbon::tomorrow());
        $rdv->update(['status' => 'confirmed', 'reminder_sent_at' => now(), 'last_minute_reminder_sent_at' => now()]);

        app(AppointmentBookingService::class)->reprogrammer($rdv->fresh(), Carbon::tomorrow()->addDay()->toDateString(), '09:00');

        $rdv->refresh();
        $this->assertSame('confirmed', $rdv->status);
        $this->assertNull($rdv->reminder_sent_at);
        $this->assertNull($rdv->last_minute_reminder_sent_at);
        $this->assertSame('09:00', $rdv->appointment_time->format('H:i'));
    }

    public function test_rdv_fixe_par_le_medecin_refuse_un_chevauchement(): void
    {
        $this->reserver('11:00', Carbon::tomorrow());

        $this->expectException(DomainException::class);
        app(AppointmentBookingService::class)->planifierParMedecin(
            $this->medecin->id,
            $this->creerPatient('622000002')->id,
            Carbon::tomorrow()->setTime(11, 10)
        );
    }

    public function test_rdv_fixe_par_le_medecin_hors_grille_accepte(): void
    {
        $rdv = app(AppointmentBookingService::class)->planifierParMedecin(
            $this->medecin->id,
            $this->patient->id,
            Carbon::tomorrow()->setTime(17, 30)
        );

        $this->assertSame('confirmed', $rdv->status);
        $this->assertSame(15, (int) $rdv->duree_minutes);
    }

    // ------------------------------------------------------------------
    // Cloisonnement
    // ------------------------------------------------------------------

    public function test_une_autre_clinique_ne_voit_pas_les_rendez_vous(): void
    {
        $rdv = $this->reserver('11:00', Carbon::tomorrow());

        $this->actingAs($this->userB);

        $this->assertNull(Appointment::find($rdv->id));
        $this->assertSame(0, Appointment::count());
    }

    public function test_un_rdv_ne_peut_pas_melanger_medecin_et_etablissement(): void
    {
        $this->actingAs($this->userB);

        $this->expectException(\LogicException::class);
        Appointment::create([
            'employee_id' => $this->medecin->id, // médecin de A
            'patient_id' => $this->patient->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '09:00:00',
            'status' => 'pending',
        ]);
    }

    // ------------------------------------------------------------------
    // Parcours public
    // ------------------------------------------------------------------

    public function test_verifier_patient_ne_divulgue_ni_nom_ni_identifiant(): void
    {
        auth()->logout();

        $reponse = $this->postJson(route('rdv-public.verifier-patient', ['etablissement' => $this->a->slug]), ['phone' => '622000001']);

        $reponse->assertOk()->assertJson(['exists' => true]);
        $this->assertArrayNotHasKey('patient', $reponse->json());
    }

    public function test_prise_rdv_publique_ignore_tout_patient_id_envoye(): void
    {
        $autrePatient = $this->creerPatient('622000009');
        auth()->logout();

        $this->postJson(route('rdv-public.prendre', ['etablissement' => $this->a->slug]), [
            'telephone' => '622000001',
            'patient_id' => $autrePatient->id, // tentative d'usurpation : doit être ignorée
            'employee_id' => $this->medecin->id,
            'motif_rdv_id' => $this->motif->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '09:00',
        ])->assertCreated();

        $rdv = Appointment::withoutGlobalScope('etablissement')->latest('id')->first();
        $this->assertSame($this->patient->id, $rdv->patient_id);
        $this->assertTrue($this->a->patients()->where('patients.id', $this->patient->id)->exists());
    }

    public function test_prise_rdv_publique_numero_inconnu_refusee(): void
    {
        auth()->logout();

        $this->postJson(route('rdv-public.prendre', ['etablissement' => $this->a->slug]), [
            'telephone' => '699999999',
            'employee_id' => $this->medecin->id,
            'motif_rdv_id' => $this->motif->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '09:00',
        ])->assertStatus(422);
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

    private function creerMedecin(string $prenom): Employee
    {
        $medecin = Employee::create([
            'first_name' => $prenom,
            'last_name' => 'Diallo',
            'address' => 'Conakry',
            'type' => 'Doctor',
            'department_id' => $this->departement->id,
            'is_active' => true,
        ]);

        foreach (['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'] as $jour) {
            EmployeeAvailability::create([
                'employee_id' => $medecin->id,
                'day_of_week' => $jour,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'slot_duration' => 15,
                'is_active' => true,
            ]);
        }

        return $medecin;
    }

    private function creerPatient(string $telephone): Patient
    {
        $patient = Patient::create(['first_name' => 'Fatou', 'last_name' => 'Camara', 'gender' => 'Femme']);
        $compte = ComptePatient::create(['telephone' => $telephone, 'statut' => 'actif']);
        $compte->patients()->attach($patient->id, ['role' => 'titulaire']);

        return $patient;
    }

    /** @return string[] heures H:i proposées */
    private function heures(Carbon $date): array
    {
        return app(DisponibiliteService::class)
            ->creneauxDisponibles($this->medecin, $date, $this->motif)
            ->map(fn ($c) => $c['debut']->format('H:i'))
            ->all();
    }

    private function reserver(string $heure, ?Carbon $date = null): Appointment
    {
        return app(AppointmentBookingService::class)->book([
            'employee_id' => $this->medecin->id,
            'patient_id' => $this->patient->id,
            'motif_rdv_id' => $this->motif->id,
            'appointment_date' => ($date ?? Carbon::today())->toDateString(),
            'appointment_time' => $heure,
        ]);
    }
}
