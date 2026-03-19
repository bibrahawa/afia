<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use Illuminate\Support\Facades\DB;
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

        $appointment->update([
            'status' => 'completed',
        ]);

        return $appointment->fresh(['employee', 'patient']);
    }

    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        if (!in_array($appointment->status, ['pending', 'confirmed'])) {
            throw new DomainException('Ce rendez-vous ne peut pas être annulé.');
        }

        DB::transaction(function () use ($appointment, $reason) {
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            AppointmentSlot::where('employee_id', $appointment->employee_id)
                ->whereDate('date', $appointment->appointment_date)
                ->where('time', $appointment->appointment_time)
                ->update([
                    'is_available' => true,
                ]);
        });

        return $appointment->fresh(['employee', 'patient']);
    }
}