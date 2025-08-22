<?php


namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Jobs\SendAppointmentReminderJob;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;

class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer des utilisateurs de test
        $this->patient = User::factory()->create([
            // 'role' => 'patient',
            'phone' => '629818358'
        ]);
        
        $this->doctor = User::factory()->create([
            // 'role' => 'doctor'
            'phone' => '622099672'
        ]);
        
    }


    public function test_appointment_needing_24h_reminder_is_detected()
    {
        // Créer un RDV dans 24h
        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'employee_id' => $this->doctor->id,
            'appointment_date' => now()->addHours(24),
            'status' => 'pending'
        ]);

        $appointmentsNeedingReminder = Appointment::needingReminder()->get();
        
        $this->assertCount(1, $appointmentsNeedingReminder);
        $this->assertEquals($appointment->id, $appointmentsNeedingReminder->first()->id);
    }

    public function test_appointment_reminder_job_sends_sms()
    {
        // Mock du service SMS
        $smsService = Mockery::mock(SmsService::class);
        $smsService->shouldReceive('sendSms')
            ->once()
            ->with(
                '+224622099672',
                Mockery::type('string'),
                'Aprosafe'
            )
            ->andReturn(['success' => true, 'message_id' => 'test123']);

        $this->app->instance(SmsService::class, $smsService);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'employee_id' => $this->doctor->id,
            'appointment_date' => now()->addDay(),
            'status' => 'confirmed'
        ]);

        // Exécuter le job
        $job = new SendAppointmentReminderJob($appointment, 'reminder_24h');
        $job->handle($smsService);

        // Vérifier que le rappel est marqué comme envoyé
        $appointment->refresh();
        $this->assertNotNull($appointment->reminder_sent_at);

        // Vérifier le log SMS
        $this->assertDatabaseHas('appointment_sms_logs', [
            'appointment_id' => $appointment->id,
            'sms_type' => 'reminder_24h',
            'status' => 'sent'
        ]);
    }

    public function test_cancelled_appointment_does_not_send_reminder()
    {
        Queue::fake();

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'employee_id' => $this->doctor->id,
            'appointment_date' => now()->addDay(),
            'status' => 'cancelled'
        ]);

        $job = new SendAppointmentReminderJob($appointment, 'reminder_24h');
        
        // Mock du service SMS pour s'assurer qu'il n'est pas appelé
        $smsService = Mockery::mock(SmsService::class);
        $smsService->shouldNotReceive('sendSms');

        $this->app->instance(SmsService::class, $smsService);

        $job->handle($smsService);

        // Vérifier qu'aucun log SMS n'a été créé
        $this->assertDatabaseMissing('appointment_sms_logs', [
            'appointment_id' => $appointment->id
        ]);
    }

    public function test_doctor_unavailability_cancels_appointments()
    {
        // Créer plusieurs RDV pour le médecin
        $appointment1 = Appointment::create([
            'patient_id' => $this->patient->id,
            'employee_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(2),
            'status' => 'confirmed'
        ]);

        $appointment2 = Appointment::create([
            'patient_id' => $this->patient->id,
            'employee_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(5),
            'status' => 'pending'
        ]);

        // RDV en dehors de la période d'indisponibilité
        $appointment3 = Appointment::create([
            'patient_id' => $this->patient->id,
            'employee_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(10),
            'status' => 'confirmed'
        ]);

        // Marquer le médecin indisponible
        $unavailableFrom = now()->addDay();
        $unavailableTo = now()->addDays(7);

        $job = new \App\Jobs\ProcessDoctorUnavailabilityJob(
            $this->doctor->id,
            $unavailableFrom,
            $unavailableTo,
            'Congés médicaux'
        );

        $job->handle();

        // Vérifier que les bons RDV sont annulés
        $appointment1->refresh();
        $appointment2->refresh();
        $appointment3->refresh();

        $this->assertEquals('cancelled', $appointment1->status);
        $this->assertEquals('cancelled', $appointment2->status);
        $this->assertEquals('confirmed', $appointment3->status); // Non affecté
    }
}