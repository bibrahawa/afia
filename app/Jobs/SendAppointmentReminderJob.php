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
    public $timeout = 30; // ✅ Timeout de 30 secondes
    public $backoff = [60, 300, 900]; // Réessayer après 1min, 5min, 15min

    public function __construct(Appointment $appointment, string $reminderType = 'reminder_24h')
    {
        $this->appointment = $appointment;
        $this->reminderType = $reminderType;
    }

    public function handle(SmsService $smsService)
    {
        try {
            // ✅ Récupérer la version fraîche du RDV avec les relations
            $appointment = $this->appointment->fresh(['patient.user', 'employee']);
            
            // Vérifier que le RDV est toujours valide
            if (!$appointment || !in_array($appointment->status, ['pending', 'confirmed'])) {
                Log::info("Rappel SMS annulé - RDV non valide", [
                    'appointment_id' => $this->appointment->id,
                    'status' => $appointment->status ?? 'null'
                ]);
                return;
            }

            // ✅ Vérifier que le rappel n'a pas déjà été envoyé (évite doublons)
            if ($this->reminderType === 'reminder_24h' && $appointment->hasReminderBeenSent()) {
                Log::info("Rappel 24h déjà envoyé", ['appointment_id' => $appointment->id]);
                return;
            }

            if ($this->reminderType === 'reminder_2h' && $appointment->hasLastMinuteReminderBeenSent()) {
                Log::info("Rappel 2h déjà envoyé", ['appointment_id' => $appointment->id]);
                return;
            }

            // Vérifier que le patient a un numéro de téléphone
            if (!$appointment->patient->user->phone) {
                Log::warning("Pas de numéro de téléphone", [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id
                ]);
                return;
            }

            // Générer le message selon le type
            $message = $this->generateMessage($appointment);
            
            // ✅ Marquer IMMÉDIATEMENT comme envoyé pour éviter les doublons
            // Même si l'envoi SMS échoue, on ne renverra pas
            if ($this->reminderType === 'reminder_24h') {
                $appointment->markReminderSent();
            } elseif ($this->reminderType === 'reminder_2h') {
                $appointment->markLastMinuteReminderSent();
            }

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

                // ⚠️ Ne pas relancer l'exception car on a déjà marqué comme envoyé
                // pour éviter les doublons
            }

        } catch (\Exception $e) {
            Log::error("Erreur dans SendAppointmentReminderJob", [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            
            throw $e;
            // Ne pas relancer pour éviter les doublons
        }
    }

    private function generateMessage(Appointment $appointment): string
    {
        $patientName = $appointment->patient->getFullNameAttribute();
        $doctorName = $appointment->employee->getFullNameAttribute();
        $appointmentDate = $appointment->getFormattedDateShortAttribute();
        $appointmentTime = $appointment->getFormattedTimeAttribute();
        
        $clinicName = config('app.name', 'Clinique Aprosafe');
        $clinicPhone = config('clinic.phone', '628 16 44 22');

        $messages = [
            'reminder_24h' => "Bonjour {$patientName}, nous vous rappelons votre RDV avec Dr {$doctorName} demain le {$appointmentDate} à {$appointmentTime}. N'oubliez pas vos documents médicaux. {$clinicName}",
            
            'reminder_2h' => "Rappel {$patientName} : Votre RDV avec Dr {$doctorName} est dans 2h à {$appointmentTime}. Arrivée conseillée 15min avant. Tel: {$clinicPhone}",
            
            'confirmation' => "{$patientName} : Votre RDV avec Dr {$doctorName} le {$appointmentDate} à {$appointmentTime} est confirmé. {$clinicName}",
            
            'cancellation' => "Annulation {$patientName} : Votre RDV du {$appointmentDate} avec Dr {$doctorName} est annulé. Reprenez RDV via l'app ou au {$clinicPhone}. {$clinicName}",
            
            'rescheduling' => "Report {$patientName} : Votre RDV avec Dr {$doctorName} a été reporté au {$appointmentDate} à {$appointmentTime}. Merci de confirmer. {$clinicName}",
            
            'no_availability' => "Bonjour {$patientName}, aucun créneau disponible actuellement avec Dr {$doctorName}. Nous vous contacterons dès qu'un créneau se libère. Contact: {$clinicPhone}"
        ];

        return $messages[$this->reminderType] ?? $messages['reminder_24h'];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Échec définitif SendAppointmentReminderJob", [
            'appointment_id' => $this->appointment->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}