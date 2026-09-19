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
            $appointment = $this->appointment->fresh(['patient.comptesPatients', 'employee', 'etablissement']);

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
            // CORRIGÉ — seul le compte du portail était lu : un patient enregistré à
            // l'accueil (sans compte portail, le cas le plus courant) ne recevait
            // AUCUN rappel. Le numéro de sa fiche sert désormais de repli.
            $telephone = $appointment->patient?->comptesPatients?->first()?->telephone
                ?: $appointment->patient?->telephone;

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

            $result = $smsService->sendSms($telephone, $message, [
                'etablissement' => $appointment->etablissement ?? $appointment->etablissement_id,
                'type' => 'rdv_' . $this->reminderType,
                'sujet' => $appointment,
            ]);

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
        // Sans accents : un seul accent fait passer le SMS en Unicode (70 caractères
        // au lieu de 160) et en double le coût. « Dr » vient de nom_affiche
        // (CORRIGÉ : « Dr Dr » quand le prénom contenait déjà le titre).
        $patientName = \Illuminate\Support\Str::ascii($appointment->patient->first_name ?: $appointment->patient->getFullNameAttribute());
        $doctorName = \Illuminate\Support\Str::ascii($appointment->employee->nom_affiche);
        $appointmentDate = $appointment->getFormattedDateShortAttribute();
        $appointmentTime = $appointment->getFormattedTimeAttribute();

        // Nom et téléphone de la clinique DU RENDEZ-VOUS (et non plus
        // « Clinique X / 628 16 44 22 » pour toutes les cliniques).
        $identite = \App\Support\Etablissement\IdentiteDocument::pour($appointment->etablissement);
        $clinicName = \Illuminate\Support\Str::ascii($identite->nom);
        $clinicPhone = $identite->contact ?: config('clinic.phone', '');

        // NOUVEAU — lien d'annulation intégré DANS le SMS de confirmation
        // lui-même plutôt que dans un second message séparé (c'était le
        // doublon signalé). Raccourci via LienCourtService : un lien signé
        // Laravel brut dépasse 50 caractères, coûteux en SMS et peu
        // engageant à lire sur un téléphone d'entrée de gamme.
        $lienAnnulation = '';
        if (in_array($this->reminderType, ['confirmation', 'rescheduling'], true)) {
            $lienAnnulation = $this->genererLienAnnulationCourt($appointment);
        }

        // NOUVEAU — rappel de la veille avec « Empêché ? » : le patient libère son
        // créneau en un clic au lieu de ne pas venir.
        $lienEmpeche = $this->reminderType === 'reminder_24h' ? $this->genererLienAnnulationCourt($appointment, 'empeche') : '';

        $messages = [
            'reminder_24h' => trim("{$patientName}, rappel : RDV demain {$appointmentDate} a {$appointmentTime} avec {$doctorName}, {$clinicName}."
                . ($lienEmpeche ? " Empeche ? Liberez le creneau : {$lienEmpeche}" : ($clinicPhone ? " Empeche ? Appelez le {$clinicPhone}" : ''))),
            'reminder_2h' => "{$patientName}, votre RDV avec {$doctorName} est a {$appointmentTime}. Arrivez 15 min avant. {$clinicName}" . ($clinicPhone ? " {$clinicPhone}" : ''),
            'confirmation' => "{$patientName}, RDV confirme le {$appointmentDate} a {$appointmentTime} avec {$doctorName}, {$clinicName}. Pas vous ? Annulez : {$lienAnnulation}",
            'cancellation' => "{$patientName}, votre RDV du {$appointmentDate} avec {$doctorName} est annule. Reprenez RDV au {$clinicPhone}. {$clinicName}",
            'rescheduling' => "{$patientName}, votre RDV avec {$doctorName} est deplace au {$appointmentDate} a {$appointmentTime}. Pas d'accord ? Annulez : {$lienAnnulation}",
            'no_availability' => "{$patientName}, aucun creneau libre pour l'instant avec {$doctorName}. Nous vous rappellerons. {$clinicName} {$clinicPhone}",
        ];

        return $messages[$this->reminderType] ?? $messages['reminder_24h'];
    }

    /**
     * Best-effort : si la génération du lien échoue pour une raison
     * quelconque (établissement introuvable, etc.), on renvoie une chaîne
     * vide plutôt que de faire échouer tout l'envoi du SMS de confirmation
     * — le rendez-vous reste confirmé même sans ce filet de sécurité.
     */
    protected function genererLienAnnulationCourt(Appointment $appointment, ?string $motif = null): string
    {
        try {
            $etablissement = $appointment->etablissement ?? $appointment->employee->etablissement;

            if (! $etablissement) {
                return '';
            }

            // Lien valable jusqu'à l'heure du rendez-vous (au moins 2 h).
            $expiration = $motif === 'empeche'
                ? max(now()->addHours(2), $appointment->appointment_datetime->copy())
                : now()->addHours(48);

            $parametres = ['etablissement' => $etablissement->slug, 'appointment' => $appointment->id];
            if ($motif) {
                $parametres['motif'] = $motif; // couvert par la signature
            }

            $lienSigne = \Illuminate\Support\Facades\URL::temporarySignedRoute('rdv-public.annulation', $expiration, $parametres);

            return app(LienCourtService::class)->creer($lienSigne, $expiration);
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
