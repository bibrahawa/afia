<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AppointmentQueryService
{
    public function paginatedForDoctor(Request $request, int $employeeId): LengthAwarePaginator
    {
        $query = Appointment::with(['patient.user', 'employee']);
                            // ->where('employee_id', $employeeId);

        $this->applyFilters($query, $request);

        return $query->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(20)
            ->appends($request->only(['search', 'status', 'date_filter']));
    }

    public function statsForDoctor(int $employeeId): array
    {
        $base = Appointment::query();
        // ->where('employee_id', $employeeId);

        return [
            'today' => (clone $base)->whereDate('appointment_date', Carbon::today())->count(),
            'tomorrow' => (clone $base)->whereDate('appointment_date', Carbon::tomorrow())->count(),
            'day_after_tomorrow' => (clone $base)->whereDate('appointment_date', Carbon::today()->addDays(2))->count(),
            'this_week' => (clone $base)->whereBetween('appointment_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(),
            'next_week' => (clone $base)->whereBetween('appointment_date', [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])->count(),
            'this_month' => (clone $base)->whereMonth('appointment_date', now()->month)->whereYear('appointment_date', now()->year)->count(),
            'total' => (clone $base)->count(),
        ];
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($patientQuery) use ($search) {
                    $patientQuery->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('phone', 'LIKE', "%{$search}%");
                        });
                })
                ->orWhere('reason', 'LIKE', "%{$search}%")
                ->orWhere('notes', 'LIKE', "%{$search}%")
                ->orWhere('appointment_date', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('appointment_date', Carbon::today());
                    break;
                case 'tomorrow':
                    $query->whereDate('appointment_date', Carbon::tomorrow());
                    break;
                case 'day_after_tomorrow':
                    $query->whereDate('appointment_date', Carbon::today()->addDays(2));
                    break;
                case 'this_week':
                    $query->whereBetween('appointment_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'next_week':
                    $query->whereBetween('appointment_date', [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('appointment_date', now()->month)->whereYear('appointment_date', now()->year);
                    break;
            }
        }
    }
}