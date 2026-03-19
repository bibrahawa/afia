<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\EmployeeAvailability;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class DoctorAvailabilityService
{
    public function __construct(
        protected AppointmentSlotService $slotService
    ) {
    }

    public function create(int $employeeId, array $data): EmployeeAvailability
    {
        $exists = EmployeeAvailability::where('employee_id', $employeeId)
            ->where('day_of_week', $data['day_of_week'])
            ->exists();

        if ($exists) {
            throw new DomainException('Une disponibilité existe déjà pour ce jour.');
        }

        return DB::transaction(function () use ($employeeId, $data) {
            $availability = EmployeeAvailability::create([
                'employee_id' => $employeeId,
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'slot_duration' => $data['slot_duration'],
                'is_active' => true,
            ]);

            $this->slotService->generateSlotsForAvailability($employeeId, $data);

            return $availability;
        });
    }

    public function update(EmployeeAvailability $availability, int $employeeId, array $data): int
    {
        if ($availability->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        return DB::transaction(function () use ($availability, $employeeId, $data) {
            $cancelledCount = $this->cancelAppointmentsOutsideNewSchedule(
                $employeeId,
                $this->slotService->dayToEnglish($data['day_of_week']),
                $data['start_time'],
                $data['end_time']
            );

            $this->slotService->deleteFutureAvailableSlotsForDay($employeeId, $data['day_of_week']);
            $availability->update($data);
            $this->slotService->generateSlotsForAvailability($employeeId, $data);

            return $cancelledCount;
        });
    }

    public function delete(EmployeeAvailability $availability, int $employeeId): void
    {
        if ($availability->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        DB::transaction(function () use ($availability, $employeeId) {
            $this->slotService->deleteFutureAvailableSlotsForDay($employeeId, $availability->day_of_week);
            $availability->delete();
        });
    }

    protected function cancelAppointmentsOutsideNewSchedule(
        int $employeeId,
        string $dayOfWeekEn,
        string $newStartTime,
        string $newEndTime
    ): int {
        $appointments = Appointment::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('appointment_date', '>=', Carbon::today())
            ->whereRaw('DAYNAME(appointment_date) = ?', [$dayOfWeekEn])
            ->where(function ($query) use ($newStartTime, $newEndTime) {
                $query->where('appointment_time', '<', $newStartTime)
                    ->orWhere('appointment_time', '>=', $newEndTime);
            })
            ->get();

        foreach ($appointments as $appointment) {
            $appointment->update(['status' => 'cancelled']);

            AppointmentSlot::where('employee_id', $employeeId)
                ->where('date', $appointment->appointment_date)
                ->where('time', $appointment->appointment_time)
                ->update(['is_available' => true]);
        }

        return $appointments->count();
    }
}