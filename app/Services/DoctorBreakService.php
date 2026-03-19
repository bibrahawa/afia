<?php

namespace App\Services;

use App\Models\AppointmentSlot;
use App\Models\EmployeeAvailability;
use App\Models\EmployeeBreak;
use App\Models\EmployeeLeave;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class DoctorBreakService
{
    public function create(int $employeeId, array $data): EmployeeBreak
    {
        if ($this->hasOverlap($employeeId, $data['day_of_week'], $data['start_time'], $data['end_time'])) {
            throw new DomainException('Une pause existe déjà dans cette plage horaire pour ce jour.');
        }

        return DB::transaction(function () use ($employeeId, $data) {
            $break = EmployeeBreak::create([
                'employee_id' => $employeeId,
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'label' => $data['label'] ?? 'Pause',
                'is_active' => true,
            ]);

            $deleted = $this->deleteSlotsForBreak($break);

            $break->deleted_slots_count = $deleted;

            return $break;
        });
    }

    public function update(EmployeeBreak $break, int $employeeId, array $data): array
    {
        if ($break->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        if ($this->hasOverlap($employeeId, $data['day_of_week'], $data['start_time'], $data['end_time'], $break->id)) {
            throw new DomainException('Une pause existe déjà dans cette plage horaire.');
        }

        return DB::transaction(function () use ($break, $data) {
            $restored = 0;
            if ($break->is_active) {
                $restored = $this->restoreSlotsForBreak($break);
            }

            $break->update([
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'label' => $data['label'] ?? 'Pause',
            ]);

            $deleted = 0;
            if ($break->is_active) {
                $deleted = $this->deleteSlotsForBreak($break);
            }

            return [
                'restored_slots' => $restored,
                'deleted_slots' => $deleted,
            ];
        });
    }

    public function delete(EmployeeBreak $break, int $employeeId): int
    {
        if ($break->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        return DB::transaction(function () use ($break) {
            $restored = 0;

            if ($break->is_active) {
                $restored = $this->restoreSlotsForBreak($break);
            }

            $break->delete();

            return $restored;
        });
    }

    public function toggle(EmployeeBreak $break, int $employeeId, bool $isActive): string
    {
        if ($break->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        return DB::transaction(function () use ($break, $isActive) {
            if ($isActive) {
                $deleted = $this->deleteSlotsForBreak($break);
                $break->update(['is_active' => true]);

                return "Pause activée. {$deleted} créneau(x) supprimé(s).";
            }

            $restored = $this->restoreSlotsForBreak($break);
            $break->update(['is_active' => false]);

            return "Pause désactivée. {$restored} créneau(x) restauré(s).";
        });
    }

    protected function hasOverlap(int $employeeId, string $dayOfWeek, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $query = EmployeeBreak::where('employee_id', $employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })->orWhere(function ($q) use ($endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    protected function deleteSlotsForBreak(EmployeeBreak $break): int
    {
        $dateRange = AppointmentSlot::where('employee_id', $break->employee_id)
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();

        if (!$dateRange || !$dateRange->min_date) {
            return 0;
        }

        $dayOfWeekEn = $this->dayToEnglish($break->day_of_week);

        return AppointmentSlot::where('employee_id', $break->employee_id)
            ->whereBetween('date', [$dateRange->min_date, $dateRange->max_date])
            ->whereRaw('DAYNAME(date) = ?', [$dayOfWeekEn])
            ->where('time', '>=', $break->start_time->format('H:i:s'))
            ->where('time', '<', $break->end_time->format('H:i:s'))
            ->where('is_available', true)
            ->delete();
    }

    protected function restoreSlotsForBreak(EmployeeBreak $break): int
    {
        $availability = EmployeeAvailability::where('employee_id', $break->employee_id)
            ->where('day_of_week', $break->day_of_week)
            ->where('is_active', true)
            ->first();

        if (!$availability) {
            return 0;
        }

        $dateRange = AppointmentSlot::where('employee_id', $break->employee_id)
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();

        if (!$dateRange || !$dateRange->min_date) {
            return 0;
        }

        $currentDate = Carbon::parse($dateRange->min_date);
        $endDate = Carbon::parse($dateRange->max_date);
        $dayOfWeekEn = $this->dayToEnglish($break->day_of_week);
        $restored = 0;

        while ($currentDate <= $endDate) {
            if ($currentDate->format('l') === $dayOfWeekEn) {
                $isLeaveDay = EmployeeLeave::where('employee_id', $break->employee_id)
                    ->where('status', '!=', 'rejected')
                    ->whereDate('start_date', '<=', $currentDate)
                    ->whereDate('end_date', '>=', $currentDate)
                    ->exists();

                if (!$isLeaveDay) {
                    $slotTime = Carbon::createFromFormat('H:i', $break->start_time->format('H:i'));
                    $endTime = Carbon::createFromFormat('H:i', $break->end_time->format('H:i'));
                    $duration = (int) $availability->slot_duration;

                    if ($currentDate->isToday()) {
                        while ($slotTime < now()) {
                            $slotTime->addMinutes($duration);
                        }
                    }

                    while ($slotTime < $endTime) {
                        $slot = AppointmentSlot::firstOrCreate([
                            'employee_id' => $break->employee_id,
                            'date' => $currentDate->format('Y-m-d'),
                            'time' => $slotTime->format('H:i:s'),
                        ], [
                            'is_available' => true,
                        ]);

                        if ($slot->wasRecentlyCreated) {
                            $restored++;
                        } elseif (!$slot->is_available) {
                            // on ne touche pas aux slots occupés par un rendez-vous
                        }

                        $slotTime->addMinutes($duration);
                    }
                }
            }

            $currentDate->addDay();
        }

        return $restored;
    }

    protected function dayToEnglish(string $day): string
    {
        return [
            'Lundi' => 'Monday',
            'Mardi' => 'Tuesday',
            'Mercredi' => 'Wednesday',
            'Jeudi' => 'Thursday',
            'Vendredi' => 'Friday',
            'Samedi' => 'Saturday',
            'Dimanche' => 'Sunday',
        ][$day] ?? 'Monday';
    }
}