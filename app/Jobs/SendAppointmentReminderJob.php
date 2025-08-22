<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\AppointmentSmsLog;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointment;
    protected $reminderType;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // Réessayer après 1min, 5min, 15min


    public function __construct(Appointment $appointment, string $reminderType = 'reminder_24h')
    {
        $this->appointment = $appointment;
        $this->reminderType = $reminderType;
    }

    public function handle(SmsService $smsService)
    {
        try {

            // Vérifier que le RDV est toujours valide
            $appointment = $this->appointment->fresh();
            
            if (!$appointment || !in_array($appointment->status, ['pending', 'confirmed', 'cancelled'])) {
                Log::info("Rappel SMS annulé - RDV non valide", [
                    'appointment_id' => $this->appointment->id,
                    'status' => $appointment->status ?? 'null'
                ]);

                return;
            }

            // Générer le message selon le type
            $message = $this->generateMessage($appointment);
            
            // Créer le log SMS
            $smsLog = AppointmentSmsLog::create([
                'appointment_id' => $appointment->id,
                'sms_type' => $this->reminderType,
                'phone_number' => $appointment->patient->user->phone,
                'message' => $message,
                'status' => 'pending'
            ]);

            // Envoyer le SMS
            $result = $smsService->sendSms(
                $appointment->patient->user->phone,
                $message
            );

            if ($result['success']) {
                $smsLog->markAsSent();
                
                // Marquer le rappel comme envoyé selon le type
                if ($this->reminderType === 'reminder_24h') {
                    $appointment->markReminderSent();
                }

                Log::info("Rappel SMS envoyé avec succès", [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'phone' => $appointment->patient->user->phone,
                    'type' => $this->reminderType
                ]);

            } else {

                $smsLog->markAsFailed($result['error']);
                
                Log::error("Échec envoi rappel SMS", [
                    'appointment_id' => $appointment->id,
                    'error' => $result['error']
                ]);

                throw new \Exception($result['error']);
            }

        } catch (\Exception $e) {
            
            Log::error("Erreur dans SendAppointmentReminderJob", [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Relancer pour déclencher les tentatives
        }
    }

    private function generateMessage(Appointment $appointment): string
    {
        $patientName = $appointment->patient->getFullNameAttribute();
        $doctorName = $appointment->employee->getFullNameAttribute();
        $appointmentDate = $appointment->getFormattedDateAttribute();
        
        $messages = [
            'reminder_24h' => "Bonjour {$patientName}, rappel de votre rendez-vous avec Dr {$doctorName} demain ({$appointmentDate}). Merci de confirmer en répondant OUI ou d'annuler si nécessaire. Clinique Aprosafe.",
            
            'reminder_2h' => "Rappel urgent {$patientName} : Votre RDV avec Dr {$doctorName} est dans 2h ({$appointmentDate}). N'oubliez pas d'apporter vos documents médicaux. Clinique Aprosafe.",
            
            'confirmation' => "Confirmation {$patientName} : Votre rendez-vous avec Dr {$doctorName} le {$appointmentDate} est confirmé. Merci de votre confiance. Clinique Aprosafe.",
            
            'cancellation' => "Annulation {$patientName} : Votre rendez-vous du {$appointmentDate} avec Dr {$doctorName} a été annulé. Veuillez nous contacter pour reporter. Clinique Aprosafe.",
            
            'rescheduling' => "Report {$patientName} : Votre rendez-vous avec Dr {$doctorName} a été reporté. Nouveau créneau : {$appointmentDate}. Merci de confirmer. Clinique Aprosafe."
        ];

        return $messages[$this->reminderType] ?? $messages['reminder_24h'];
    }

    public function failed(\Exception $exception)
    {
        Log::error("Échec définitif SendAppointmentReminderJob", [
            'appointment_id' => $this->appointment->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Marquer le SMS comme échoué définitivement
        AppointmentSmsLog::where('appointment_id', $this->appointment->id)
            ->where('sms_type', $this->reminderType)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'error_message' => 'Échec définitif après ' . $this->tries . ' tentatives'
            ]);
    }
}