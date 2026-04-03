<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\EmployeeLeave;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class DoctorLeaveService
{
    public function __construct(
        protected AppointmentSlotService $slotService
    ) {
    }

    public function create(int $employeeId, array $data): EmployeeLeave
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        if ($this->hasConflict($employeeId, $startDate, $endDate)) {
            throw new DomainException('Un congé existe déjà pour cette période.');
        }

        return DB::transaction(function () use ($employeeId, $data, $startDate, $endDate) {
            $leave = EmployeeLeave::create([
                'employee_id' => $employeeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $data['type'],
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]);

            $this->cancelAppointmentsForLeave(
                $employeeId,
                $startDate,
                $endDate,
                $data['type']
            );

            $this->slotService->deleteSlotsInPeriod($employeeId, $startDate, $endDate);

            return $leave;
        });
    }

    public function update(EmployeeLeave $leave, int $employeeId, array $data): array
    {
        if ($leave->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        if (in_array($leave->status, ['approved', 'rejected'])) {
            throw new DomainException('Ce congé ne peut plus être modifié.');
        }

        $newStart = Carbon::parse($data['start_date']);
        $newEnd = Carbon::parse($data['end_date']);

        if ($this->hasConflict($employeeId, $newStart, $newEnd, $leave->id)) {
            throw new DomainException('Un autre congé existe déjà pour cette période.');
        }

        return DB::transaction(function () use ($leave, $employeeId, $data, $newStart, $newEnd) {
            $oldStart = $leave->start_date->copy();
            $oldEnd = $leave->end_date->copy();

            // 1. Restaurer les slots de l’ancienne période
            $restoredSlots = $this->slotService->restoreSlotsInPeriod($employeeId, $oldStart, $oldEnd);

            // 2. Restaurer les rendez-vous annulés dans l’ancienne période
            $restoredAppointments = $this->restoreCancelledAppointmentsForLeavePeriod(
                $employeeId,
                $oldStart,
                $oldEnd,
                $leave->id
            );

            // 3. Mettre à jour le congé
            $leave->update([
                'type' => $data['type'],
                'start_date' => $newStart,
                'end_date' => $newEnd,
                'reason' => $data['reason'] ?? null,
            ]);

            // 4. Supprimer les slots de la nouvelle période
            $deletedSlots = $this->slotService->deleteSlotsInPeriod($employeeId, $newStart, $newEnd);

            // 5. Ré-annuler les rendez-vous de la nouvelle période
            $cancelledAppointments = $this->cancelAppointmentsForLeave(
                $employeeId,
                $newStart,
                $newEnd,
                $data['type']
            );

            return [
                'restored_slots' => $restoredSlots,
                'restored_appointments' => $restoredAppointments,
                'deleted_slots' => $deletedSlots,
                'cancelled_appointments' => $cancelledAppointments,
            ];
        });
    }

    public function delete(EmployeeLeave $leave, int $employeeId): array
    {
        if ($leave->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        if ($leave->status === 'approved' && $leave->start_date <= now()) {
            throw new DomainException('Un congé déjà approuvé et commencé ne peut être supprimé.');
        }

        return DB::transaction(function () use ($leave, $employeeId) {
            $startDate = $leave->start_date->copy();
            $endDate = $leave->end_date->copy();

            // 1. Restaurer les slots
            $restoredSlots = $this->slotService->restoreSlotsInPeriod($employeeId, $startDate, $endDate);

            // 2. Restaurer les rendez-vous annulés à cause du congé
            $restoredAppointments = $this->restoreCancelledAppointmentsForLeavePeriod(
                $employeeId,
                $startDate,
                $endDate,
                $leave->id
            );

            // 3. Supprimer le congé
            $leave->delete();

            return [
                'restored_slots' => $restoredSlots,
                'restored_appointments' => $restoredAppointments,
            ];
        });
    }

    // protected function cancelAppointmentsForLeave(
    //     int $employeeId,
    //     Carbon $startDate,
    //     Carbon $endDate,
    //     string $reason
    // ): int {

    //     $appointments = Appointment::where('employee_id', $employeeId)
    //         ->whereIn('status', ['pending', 'confirmed'])
    //         ->whereBetween('appointment_datetime', [$startDate, $endDate])
    //         ->get();

    //     $count = 0;

    //     foreach ($appointments as $appointment) {
    //         $appointment->update([
    //             'status' => 'cancelled',
    //             'cancelled_at' => now(),
    //             'cancellation_reason' => "Indisponibilité du médecin : {$reason}",
    //         ]);

    //         AppointmentSlot::where('employee_id', $employeeId)
    //             ->whereDate('date', $appointment->appointment_date)
    //             ->where('time', $appointment->appointment_time)
    //             ->update([
    //                 'is_available' => false,
    //             ]);

    //         $count++;
    //     }

    //     return $count;
    // }

    protected function cancelAppointmentsForLeave(
        int $employeeId,
        Carbon $startDate,
        Carbon $endDate,
        string $reason
    ): int {
        return Appointment::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('appointment_datetime', [$startDate, $endDate])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => "Indisponibilité du médecin : {$reason}",
            ]);
    }

    protected function restoreCancelledAppointmentsForLeavePeriod(
        int $employeeId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeLeaveId = null
    ): int {
        $appointments = Appointment::where('employee_id', $employeeId)
            ->where('status', 'cancelled')
            ->whereBetween('appointment_datetime', [$startDate, $endDate])
            ->whereNotNull('cancellation_reason')
            ->where('cancellation_reason', 'like', 'Indisponibilité du médecin :%')
            ->get();

        $restoredCount = 0;

        foreach ($appointments as $appointment) {
            // Vérifier qu’aucun autre congé ne couvre encore ce rendez-vous
            $hasLeaveConflict = EmployeeLeave::where('employee_id', $employeeId)
                ->when($excludeLeaveId, function ($query) use ($excludeLeaveId) {
                    $query->where('id', '!=', $excludeLeaveId);
                })
                ->where('status', '!=', 'rejected')
                ->where('start_date', '<=', $appointment->appointment_datetime)
                ->where('end_date', '>=', $appointment->appointment_datetime)
                ->exists();

            if ($hasLeaveConflict) {
                continue;
            }

            // Vérifier qu’un autre congé/pause/disponibilité ne bloque pas encore
            $slot = AppointmentSlot::firstOrCreate([
                'employee_id' => $employeeId,
                'date' => $appointment->appointment_date->format('Y-m-d'),
                'time' => Carbon::parse($appointment->appointment_time)->format('H:i:s'),
            ], [
                'is_available' => false,
            ]);

            // Si le slot est déjà libre, on le rebloque pour le rendez-vous restauré
            $slot->update([
                'is_available' => false,
            ]);

            $appointment->update([
                'status' => 'pending',
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);

            $restoredCount++;
        }

        return $restoredCount;
    }

    protected function hasConflict(int $employeeId, $startDate, $endDate, ?int $excludeId = null): bool
    {
        $query = EmployeeLeave::where('employee_id', $employeeId)
            ->where('status', '!=', 'rejected')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}