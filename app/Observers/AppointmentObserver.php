<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Events\AppointmentStatusChanged;
use Illuminate\Support\Facades\Log;

class AppointmentObserver
{
    public function updating(Appointment $appointment)
    {
        // Détecter les changements de statut
        if ($appointment->isDirty('status')) {
            $oldStatus = $appointment->getOriginal('status');
            $newStatus = $appointment->status;

            // Déclencher l'événement immédiatement après la mise à jour
            event(new AppointmentStatusChanged($appointment, $oldStatus, $newStatus));
        }
    }

    public function created(Appointment $appointment)
    {
        Log::info("Nouveau RDV créé", [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'employee_id' => $appointment->employee_id,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time
        ]);
    }
}