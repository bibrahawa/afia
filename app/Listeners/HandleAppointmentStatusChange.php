<?php

namespace App\Listeners;

use App\Events\AppointmentStatusChanged;
use App\Jobs\SendAppointmentReminderJob;
use Illuminate\Support\Facades\Log;

class HandleAppointmentStatusChange
{
    public function handle(AppointmentStatusChanged $event)
    {
        $appointment = $event->appointment;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;

        Log::info("Changement statut RDV", [
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);

        // Envoyer SMS selon le changement de statut
        switch ($newStatus) {
            case 'confirmed':
                if ($oldStatus === 'pending') {
                    SendAppointmentReminderJob::dispatch($appointment, 'confirmation')->delay(now()->addSeconds(5));
                }
            break;

            case 'cancelled':
                if (in_array($oldStatus, ['pending', 'confirmed'])) {
                    SendAppointmentReminderJob::dispatch($appointment, 'cancellation')->delay(now()->addSeconds(5));
                }
            break;
        }
    }
}