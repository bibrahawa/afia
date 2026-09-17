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
        $query = Appointment::with(['patient', 'employee', 'motifRdv'])
            ->where('employee_id', $employeeId);

        $this->applyFilters($query, $request);

        return $query->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(20)
            ->appends($request->only(['search', 'status', 'date_filter']));
    }

    /**
     * Vue "réception" : tous les rendez-vous de la clinique (tous médecins
     * confondus). `employee_id` reste un filtre OPTIONNEL.
     *
     * Depuis le 21/09/2026, `appointments` porte sa propre colonne
     * etablissement_id et le global scope BelongsToEtablissement filtre
     * directement : le détour par Employee::pluck('id') n'a plus lieu d'être.
     */
    public function paginatedForReception(Request $request): LengthAwarePaginator
    {
        $query = Appointment::with(['patient', 'employee', 'motifRdv']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $query->where('appointment_date', Carbon::parse($request->input('date', Carbon::today()->toDateString()))->toDateString());

        $this->applyFilters($query, $request);

        return $query->orderBy('appointment_time')
            ->paginate(30)
            ->appends($request->only(['search', 'status', 'employee_id', 'date']));
    }

    public function statsForReception(string $date): array
    {
        $parStatut = Appointment::where('appointment_date', Carbon::parse($date)->toDateString())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $parStatut->sum(),
            'pending' => (int) ($parStatut['pending'] ?? 0),
            'confirmed' => (int) ($parStatut['confirmed'] ?? 0),
            'completed' => (int) ($parStatut['completed'] ?? 0),
            'cancelled' => (int) ($parStatut['cancelled'] ?? 0),
        ];
    }

    public function statsForDoctor(int $employeeId): array
    {
        $base = Appointment::where('employee_id', $employeeId);

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
                        // 'user.phone' n'a plus de sens : le téléphone
                        // patient vit maintenant sur ComptePatient (Pilier
                        // B), plus sur l'ancien lien User::phone.
                        ->orWhereHas('comptesPatients', function ($compteQuery) use ($search) {
                            $compteQuery->where('telephone', 'LIKE', "%{$search}%");
                        });
                })
                // 'reason' n'existe plus (remplacé par motif_rdv_id) —
                // chercher désormais dans le nom du motif lié.
                ->orWhereHas('motifRdv', function ($motifQuery) use ($search) {
                    $motifQuery->where('nom', 'LIKE', "%{$search}%");
                })
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
