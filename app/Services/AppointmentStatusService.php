<?php

namespace App\Services;

use App\Models\Appointment;
use DomainException;

class AppointmentStatusService
{
    public function confirm(Appointment $appointment): Appointment
    {
        if ($appointment->status !== 'pending') {
            throw new DomainException('Seul un rendez-vous en attente peut être confirmé.');
        }

        $appointment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'patient_confirmed' => true,
        ]);

        return $appointment->fresh(['employee', 'patient']);
    }

    public function complete(Appointment $appointment): Appointment
    {
        if (!in_array($appointment->status, ['pending', 'confirmed'])) {
            throw new DomainException('Ce rendez-vous ne peut pas être marqué comme terminé.');
        }

        $appointment->update(['status' => 'completed']);

        return $appointment->fresh(['employee', 'patient']);
    }

    /**
     * Plus besoin de libérer une ligne `appointment_slots` : la
     * disponibilité est recalculée à la volée (DisponibiliteService) à
     * partir des rendez-vous encore actifs — un rendez-vous annulé
     * disparaît naturellement du calcul, sans étape de "libération"
     * séparée à maintenir en cohérence.
     */
    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        if (!in_array($appointment->status, ['pending', 'confirmed'])) {
            throw new DomainException('Ce rendez-vous ne peut pas être annulé.');
        }

        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $appointment->fresh(['employee', 'patient']);
    }
}
