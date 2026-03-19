<?php

namespace App\Services;

use App\Models\AppointmentSlot;
use App\Models\EmployeeAvailability;
use App\Models\EmployeeLeave;
use Carbon\Carbon;

class AppointmentSlotService
{
    public function dayToEnglish(string $day): string
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

    public function generateSlotsForAvailability(int $employeeId, array $availabilityData, int $weeks = 16): void
    {
        $dayOfWeek = $this->dayToEnglish($availabilityData['day_of_week']);
        $today = Carbon::now();
        $targetDayNumber = Carbon::parse('next ' . $dayOfWeek)->dayOfWeek;

        $startDate = ($today->dayOfWeek === $targetDayNumber)
            ? $today->copy()
            : $today->next($dayOfWeek);

        for ($week = 0; $week < $weeks; $week++) {
            $date = $startDate->copy()->addWeeks($week);

            $isLeaveDay = EmployeeLeave::where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();

            if ($isLeaveDay) {
                continue;
            }

            $start = Carbon::createFromFormat('H:i', $availabilityData['start_time']);
            $end = Carbon::createFromFormat('H:i', $availabilityData['end_time']);
            $duration = (int) $availabilityData['slot_duration'];

            if ($date->isToday()) {
                while ($start < now()) {
                    $start->addMinutes($duration);
                }
            }

            while ($start < $end) {
                AppointmentSlot::firstOrCreate([
                    'employee_id' => $employeeId,
                    'date' => $date->format('Y-m-d'),
                    'time' => $start->format('H:i:s'),
                ], [
                    'is_available' => true,
                ]);

                $start->addMinutes($duration);
            }
        }
    }

    public function deleteFutureAvailableSlotsForDay(int $employeeId, string $dayOfWeek, int $weeks = 16): void
    {
        $dayOfWeekEn = $this->dayToEnglish($dayOfWeek);
        $today = Carbon::now();
        $targetDayNumber = Carbon::parse('next ' . $dayOfWeekEn)->dayOfWeek;

        $startDate = ($today->dayOfWeek === $targetDayNumber)
            ? $today->copy()
            : $today->next($dayOfWeekEn);

        for ($week = 0; $week < $weeks; $week++) {
            $date = $startDate->copy()->addWeeks($week);

            AppointmentSlot::where('employee_id', $employeeId)
                ->whereDate('date', $date)
                ->where('is_available', true)
                ->delete();
        }
    }

    public function restoreSlotsInPeriod(int $employeeId, $startDate, $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $restoredCount = 0;
        $currentDate = $start->copy()->startOfDay();

        while ($currentDate <= $end->copy()->endOfDay()) {
            $dayName = ucfirst($currentDate->locale('fr')->isoFormat('dddd'));

            $availability = EmployeeAvailability::where('employee_id', $employeeId)
                ->where('day_of_week', $dayName)
                ->where('is_active', true)
                ->first();

            if ($availability) {
                $slotTime = Carbon::createFromFormat('H:i', $availability->start_time->format('H:i'));
                $endTime = Carbon::createFromFormat('H:i', $availability->end_time->format('H:i'));
                $duration = (int) $availability->slot_duration;

                if ($currentDate->isToday()) {
                    $slotTime = $slotTime->max(now());
                }

                while ($slotTime < $endTime) {
                    $slot = AppointmentSlot::firstOrCreate([
                        'employee_id' => $employeeId,
                        'date' => $currentDate->format('Y-m-d'),
                        'time' => $slotTime->format('H:i:s'),
                    ], [
                        'is_available' => true,
                    ]);

                    if ($slot->wasRecentlyCreated) {
                        $restoredCount++;
                    }

                    $slotTime->addMinutes($duration);
                }
            }

            $currentDate->addDay();
        }

        return $restoredCount;
    }

    public function deleteSlotsInPeriod(int $employeeId, $startDate, $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $deletedCount = 0;
        $currentDate = $start->copy()->startOfDay();

        while ($currentDate <= $end->copy()->endOfDay()) {
            $dayName = ucfirst($currentDate->locale('fr')->isoFormat('dddd'));

            $availability = EmployeeAvailability::where('employee_id', $employeeId)
                ->where('day_of_week', $dayName)
                ->where('is_active', true)
                ->first();

            if ($availability) {
                $slotTime = Carbon::createFromFormat('H:i', $availability->start_time->format('H:i'));
                $endTime = Carbon::createFromFormat('H:i', $availability->end_time->format('H:i'));
                $duration = (int) $availability->slot_duration;

                while ($slotTime < $endTime) {
                    $deleted = AppointmentSlot::where('employee_id', $employeeId)
                        ->where('date', $currentDate->format('Y-m-d'))
                        ->where('time', $slotTime->format('H:i:s'))
                        ->where('is_available', true)
                        ->delete();

                    $deletedCount += $deleted;
                    $slotTime->addMinutes($duration);
                }
            }

            $currentDate->addDay();
        }

        return $deletedCount;
    }
}