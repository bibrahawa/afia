<?php

namespace Tests\Feature;

use App\Jobs\ProcessDoctorUnavailabilityJob;
use App\Jobs\SendAppointmentReminderJob;
use App\Models\Appointment;
use App\Models\ComptePatient;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Patient;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * Rappels SMS des rendez-vous.
 *
 * Réécrit : le patient est un Patient avec son ComptePatient (c'est lui qui
 * porte le téléphone depuis la refonte), le médecin est un Employee, et les
 * rendez-vous portent une heure et un établissement, tous deux obligatoires.
 */
class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etablissement;
    private Patient $patient;
    private Employee $medecin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->etablissement = Etablissement::create([
            'nom' => 'Clinique des rappels', 'slug' => 'clinique-rappels', 'type' => 'clinique', 'statut' => 'actif',
        ]);

        $utilisateur = User::create([
            'name' => 'Accueil', 'email' => Str::random(8) . '@test.gn',
            'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('secret'),
        ]);
        $utilisateur->forceFill(['etablissement_id' => $this->etablissement->id])->save();
        $this->actingAs($utilisateur);

        $departement = Department::create(['name' => 'Médecine générale']);

        $this->medecin = Employee::create([
            'first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry',
            'type' => 'Doctor', 'department_id' => $departement->id,
        ]);

        $this->patient = Patient::create([
            'first_name' => 'Fanta', 'last_name' => 'Camara', 'gender' => 'Femme', 'birth_date' => '1992-04-04',
        ]);
        $this->etablissement->patients()->syncWithoutDetaching([$this->patient->id]);

        // Le téléphone du patient vit sur son compte, pas sur un User.
        $compte = ComptePatient::create(['telephone' => '622099672', 'statut' => 'actif']);
        $this->patient->comptesPatients()->attach($compte->id, ['role' => 'titulaire']);
    }

    public function test_un_rendez_vous_a_24h_est_detecte(): void
    {
        $rdv = $this->rendezVous(now()->addHours(24));

        // Hors de la fenêtre 22-26 h : ne doit pas ressortir.
        $this->rendezVous(now()->addDays(3));

        $aRappeler = Appointment::needingReminder()->get();

        $this->assertCount(1, $aRappeler);
        $this->assertSame($rdv->id, $aRappeler->first()->id);
    }

    public function test_le_rappel_envoie_un_sms_et_journalise(): void
    {
        $rdv = $this->rendezVous(now()->addDay(), 'confirmed');

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('sendSms')
            ->once()
            ->withArgs(fn ($telephone, $message) => $telephone === '622099672' && is_string($message) && $message !== '')
            ->andReturn(['success' => true, 'message_id' => 'essai']);

        $this->app->instance(SmsService::class, $sms);

        (new SendAppointmentReminderJob($rdv, 'reminder_24h'))->handle($sms);

        $this->assertNotNull($rdv->fresh()->reminder_sent_at);
        $this->assertDatabaseHas('appointment_sms_logs', [
            'appointment_id' => $rdv->id, 'sms_type' => 'reminder_24h', 'status' => 'sent',
        ]);
    }

    public function test_un_rendez_vous_annule_ne_declenche_aucun_sms(): void
    {
        Queue::fake();

        $rdv = $this->rendezVous(now()->addDay(), 'cancelled');

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('sendSms');
        $this->app->instance(SmsService::class, $sms);

        (new SendAppointmentReminderJob($rdv, 'reminder_24h'))->handle($sms);

        $this->assertDatabaseMissing('appointment_sms_logs', ['appointment_id' => $rdv->id]);
        $this->assertNull($rdv->fresh()->reminder_sent_at);
    }

    public function test_un_rappel_deja_envoye_n_est_pas_renvoye(): void
    {
        $rdv = $this->rendezVous(now()->addDay(), 'confirmed');
        $rdv->markReminderSent();

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('sendSms');
        $this->app->instance(SmsService::class, $sms);

        (new SendAppointmentReminderJob($rdv->fresh(), 'reminder_24h'))->handle($sms);

        $this->assertDatabaseMissing('appointment_sms_logs', ['appointment_id' => $rdv->id]);
    }

    public function test_un_patient_sans_telephone_ne_bloque_rien(): void
    {
        $sansTelephone = Patient::create([
            'first_name' => 'Sans', 'last_name' => 'Téléphone', 'gender' => 'Homme', 'birth_date' => '1985-01-01',
        ]);
        $this->etablissement->patients()->syncWithoutDetaching([$sansTelephone->id]);

        $rdv = $this->rendezVous(now()->addDay(), 'confirmed', $sansTelephone);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('sendSms');
        $this->app->instance(SmsService::class, $sms);

        // Le job journalise et s'arrête : aucune exception ne doit remonter.
        (new SendAppointmentReminderJob($rdv, 'reminder_24h'))->handle($sms);

        $this->assertDatabaseMissing('appointment_sms_logs', ['appointment_id' => $rdv->id]);
    }

    public function test_l_indisponibilite_du_medecin_annule_les_rendez_vous_concernes(): void
    {
        $dansLaPeriode = $this->rendezVous(now()->addDays(2), 'confirmed');
        $aussiDansLaPeriode = $this->rendezVous(now()->addDays(5), 'pending');
        $horsPeriode = $this->rendezVous(now()->addDays(10), 'confirmed');

        (new ProcessDoctorUnavailabilityJob(
            $this->medecin->id,
            now()->addDay(),
            now()->addDays(7),
            'Congés'
        ))->handle();

        $this->assertSame('cancelled', $dansLaPeriode->fresh()->status);
        $this->assertSame('cancelled', $aussiDansLaPeriode->fresh()->status);
        $this->assertSame('confirmed', $horsPeriode->fresh()->status);
    }

    private function rendezVous(\Carbon\Carbon $quand, string $statut = 'pending', ?Patient $patient = null): Appointment
    {
        return Appointment::create([
            'patient_id' => ($patient ?? $this->patient)->id,
            'employee_id' => $this->medecin->id,
            'appointment_date' => $quand->toDateString(),
            'appointment_time' => $quand->format('H:i:s'),
            'duree_minutes' => 20,
            'status' => $statut,
        ]);
    }
}
