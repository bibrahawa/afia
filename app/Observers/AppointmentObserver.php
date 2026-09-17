<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Events\AppointmentStatusChanged;
use App\Support\CacheDisponibilite;
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
            'etablissement_id' => $appointment->etablissement_id,
            'patient_id' => $appointment->patient_id,
            'employee_id' => $appointment->employee_id,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time
        ]);
    }

    /**
     * NOUVEAU — création, changement de statut, d'horaire ou de médecin :
     * les créneaux en cache de ce médecin (et de l'ancien médecin si le rdv
     * a changé de praticien) ne sont plus valables.
     */
    public function saved(Appointment $appointment)
    {
        CacheDisponibilite::invalider($appointment->employee_id);

        if ($appointment->wasChanged('employee_id')) {
            CacheDisponibilite::invalider($appointment->getOriginal('employee_id'));
        }
    }

    public function deleted(Appointment $appointment)
    {
        CacheDisponibilite::invalider($appointment->employee_id);
    }
}
