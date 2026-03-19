<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use DomainException;

class AppointmentBookingService
{
    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {

            $appointmentDate = Carbon::parse($data['appointment_date'])->format('Y-m-d');
            $appointmentTime = Carbon::createFromFormat('H:i', $data['appointment_time'])->format('H:i:s');

            $slot = AppointmentSlot::where('employee_id', $data['employee_id'])
                ->whereDate('date', $appointmentDate)
                ->where('time', $appointmentTime)
                ->where('is_available', true)
                ->lockForUpdate()
                ->first();

            if (!$slot) {
                throw new DomainException('Ce créneau n’est plus disponible.');
            }

            $existingAppointment = Appointment::where('patient_id', $data['patient_id'])
                ->where('employee_id', $data['employee_id'])
                ->whereDate('appointment_date', $appointmentDate)
                ->whereIn('status', ['pending', 'confirmed'])
                ->first();

            if ($existingAppointment) {
                throw new DomainException(
                    'Ce patient a déjà un rendez-vous actif avec ce médecin à cette date.'
                );
            }

            $pendingAppointmentsCount = Appointment::where('patient_id', $data['patient_id'])
                ->where('status', 'pending')
                ->count();

            if ($pendingAppointmentsCount >= 5) {
                throw new DomainException(
                    'Ce patient a trop de rendez-vous en attente. Veuillez finaliser ou annuler les rendez-vous existants.'
                );
            }

            $appointment = Appointment::create([
                'employee_id' => $data['employee_id'],
                'patient_id' => $data['patient_id'],
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'status' => 'confirmed'
            ]);

            $slot->update([
                'is_available' => false,
            ]);

            return $appointment->fresh(['employee', 'patient']);
        });
    }
}