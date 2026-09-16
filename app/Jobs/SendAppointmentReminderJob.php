<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\AppointmentSmsLog;
use App\Services\LienCourtService;
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
    public $timeout = 30;
    public $backoff = [60, 300, 900];

    public function __construct(Appointment $appointment, string $reminderType = 'reminder_24h')
    {
        $this->appointment = $appointment;
        $this->reminderType = $reminderType;
    }

    public function handle(SmsService $smsService)
    {
        try {
            // CORRIGÉ — 'patient.user' n'a plus de sens depuis ComptePatient ;
            // charge la relation qui contient réellement le téléphone
            // aujourd'hui.
            $appointment = $this->appointment->fresh(['patient.comptesPatients', 'employee']);

            if (!$appointment || !in_array($appointment->status, ['pending', 'confirmed'])) {
                Log::info("Rappel SMS annulé - RDV non valide", [
                    'appointment_id' => $this->appointment->id,
                    'status' => $appointment->status ?? 'null'
                ]);
                return;
            }

            if ($this->reminderType === 'reminder_24h' && $appointment->hasReminderBeenSent()) {
                Log::info("Rappel 24h déjà envoyé", ['appointment_id' => $appointment->id]);
                return;
            }

            if ($this->reminderType === 'reminder_2h' && $appointment->hasLastMinuteReminderBeenSent()) {
                Log::info("Rappel 2h déjà envoyé", ['appointment_id' => $appointment->id]);
                return;
            }

            // CORRIGÉ — le téléphone vient désormais du ComptePatient lié,
            // pas de l'ancien User staff. Un patient peut en théorie avoir
            // plusieurs comptes liés (délégation) — on prend le premier,
            // cohérent avec le reste de la plateforme (voir AppointmentController).
            $telephone = $appointment->patient?->comptesPatients?->first()?->telephone;

            if (!$telephone) {
                Log::warning("Pas de numéro de téléphone", [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id
                ]);
                return;
            }

            $message = $this->generateMessage($appointment);
            if ($this->reminderType === 'reminder_24h') {
                $appointment->markReminderSent();
            } elseif ($this->reminderType === 'reminder_2h') {
                $appointment->markLastMinuteReminderSent();
            }

            $smsLog = AppointmentSmsLog::create([
                'appointment_id' => $appointment->id,
                'sms_type' => $this->reminderType,
                'phone_number' => $telephone,
                'message' => $message,
                'status' => 'pending'
            ]);

            $result = $smsService->sendSms($telephone, $message);

            if ($result['success']) {
                $smsLog->markAsSent();

                Log::info("Rappel SMS envoyé avec succès", [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'phone' => $telephone,
                    'type' => $this->reminderType
                ]);
            } else {
                $smsLog->markAsFailed($result['error']);

                Log::error("Échec envoi rappel SMS", [
                    'appointment_id' => $appointment->id,
                    'error' => $result['error']
                ]);
            }

        } catch (\Throwable $e) {
            // CORRIGÉ — ne relance PLUS l'exception. Un rappel SMS qui
            // échoue est un incident secondaire à journaliser, jamais une
            // raison de faire remonter une erreur jusqu'à la requête qui a
            // déclenché ce job — en particulier tant que ce job tourne en
            // synchrone (pas de vrai worker de queue), où "relancer"
            // revient concrètement à faire planter la création du
            // rendez-vous lui-même. Sur une vraie queue, ce choix reste
            // correct : Laravel gère déjà les tentatives via $tries/$backoff
            // sans qu'il soit nécessaire de propager l'exception plus loin.
            Log::error("Erreur dans SendAppointmentReminderJob", [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
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

        // NOUVEAU — lien d'annulation intégré DANS le SMS de confirmation
        // lui-même plutôt que dans un second message séparé (c'était le
        // doublon signalé). Raccourci via LienCourtService : un lien signé
        // Laravel brut dépasse 50 caractères, coûteux en SMS et peu
        // engageant à lire sur un téléphone d'entrée de gamme.
        $lienAnnulation = '';
        if ($this->reminderType === 'confirmation') {
            $lienAnnulation = $this->genererLienAnnulationCourt($appointment);
        }

        $messages = [
            'reminder_24h' => "Bonjour {$patientName}, nous vous rappelons votre RDV avec Dr {$doctorName} demain le {$appointmentDate} à {$appointmentTime}. N'oubliez pas vos documents médicaux. {$clinicName}",
            'reminder_2h' => "Rappel {$patientName} : Votre RDV avec Dr {$doctorName} est dans 2h à {$appointmentTime}. Arrivée conseillée 15min avant. Tel: {$clinicPhone}",
            'confirmation' => "{$patientName} : Votre RDV avec Dr {$doctorName} le {$appointmentDate} à {$appointmentTime} est confirmé. Pas vous ? Annulez : {$lienAnnulation}",
            'cancellation' => "Annulation {$patientName} : Votre RDV du {$appointmentDate} avec Dr {$doctorName} est annulé. Reprenez RDV via l'app ou au {$clinicPhone}. {$clinicName}",
            'rescheduling' => "Report {$patientName} : Votre RDV avec Dr {$doctorName} a été reporté au {$appointmentDate} à {$appointmentTime}. Merci de confirmer. {$clinicName}",
            'no_availability' => "Bonjour {$patientName}, aucun créneau disponible actuellement avec Dr {$doctorName}. Nous vous contacterons dès qu'un créneau se libère. Contact: {$clinicPhone}"
        ];

        return $messages[$this->reminderType] ?? $messages['reminder_24h'];
    }

    /**
     * Best-effort : si la génération du lien échoue pour une raison
     * quelconque (établissement introuvable, etc.), on renvoie une chaîne
     * vide plutôt que de faire échouer tout l'envoi du SMS de confirmation
     * — le rendez-vous reste confirmé même sans ce filet de sécurité.
     */
    protected function genererLienAnnulationCourt(Appointment $appointment): string
    {
        try {
            $etablissement = $appointment->employee->etablissement;

            if (! $etablissement) {
                return '';
            }

            $lienSigne = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'rdv-public.annulation',
                now()->addHours(48),
                ['etablissement' => $etablissement->slug, 'appointment' => $appointment->id]
            );

            return app(LienCourtService::class)->creer($lienSigne, now()->addHours(48));
        } catch (\Throwable $e) {
            Log::warning('Échec génération lien annulation court', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
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
