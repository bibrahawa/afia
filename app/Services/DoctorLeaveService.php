<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\EmployeeLeave;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * SIMPLIFIÉ — voir DoctorAvailabilityService pour le contexte général :
 * plus de bookkeeping sur `appointment_slots`, DisponibiliteService lit
 * directement EmployeeLeave pour savoir qu'une période est bloquée. La
 * logique métier réelle (annuler/restaurer les rendez-vous concernés par
 * un congé) reste inchangée, elle n'a jamais dépendu des slots pour son
 * SENS — seulement pour sa mécanique interne, maintenant retirée.
 */
class DoctorLeaveService
{
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

            $this->cancelAppointmentsForLeave($employeeId, $startDate, $endDate, $data['type']);

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

            $restoredAppointments = $this->restoreCancelledAppointmentsForLeavePeriod($employeeId, $oldStart, $oldEnd, $leave->id);

            $leave->update([
                'type' => $data['type'],
                'start_date' => $newStart,
                'end_date' => $newEnd,
                'reason' => $data['reason'] ?? null,
            ]);

            $cancelledAppointments = $this->cancelAppointmentsForLeave($employeeId, $newStart, $newEnd, $data['type']);

            return [
                'restored_appointments' => $restoredAppointments,
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
            $restoredAppointments = $this->restoreCancelledAppointmentsForLeavePeriod(
                $employeeId, $leave->start_date->copy(), $leave->end_date->copy(), $leave->id
            );

            $leave->delete();

            return ['restored_appointments' => $restoredAppointments];
        });
    }

    protected function cancelAppointmentsForLeave(int $employeeId, Carbon $startDate, Carbon $endDate, string $reason): int
    {
        return Appointment::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('appointment_datetime', [$startDate, $endDate])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => "Indisponibilité du médecin : {$reason}",
            ]);
    }

    protected function restoreCancelledAppointmentsForLeavePeriod(int $employeeId, Carbon $startDate, Carbon $endDate, ?int $excludeLeaveId = null): int
    {
        $appointments = Appointment::where('employee_id', $employeeId)
            ->where('status', 'cancelled')
            ->whereBetween('appointment_datetime', [$startDate, $endDate])
            ->where('cancellation_reason', 'like', 'Indisponibilité du médecin :%')
            ->get();

        $restored = 0;

        foreach ($appointments as $appointment) {
            $conflitAvecAutreConge = EmployeeLeave::where('employee_id', $employeeId)
                ->when($excludeLeaveId, fn ($q) => $q->where('id', '!=', $excludeLeaveId))
                ->where('status', '!=', 'rejected')
                ->where('start_date', '<=', $appointment->appointment_datetime)
                ->where('end_date', '>=', $appointment->appointment_datetime)
                ->exists();

            if ($conflitAvecAutreConge) {
                continue;
            }

            $appointment->update(['status' => 'pending', 'cancelled_at' => null, 'cancellation_reason' => null]);
            $restored++;
        }

        return $restored;
    }

    protected function hasConflict(int $employeeId, $startDate, $endDate, ?int $excludeId = null): bool
    {
        $query = EmployeeLeave::where('employee_id', $employeeId)
            ->where('status', '!=', 'rejected')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
