<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentExportController extends Controller
{
    public function exportPdf(Request $request)
    {
        if (!$request->date_filter) {
            return back()->with('error', 'Le filtre de date est requis pour l’export PDF.');
        }

        $query = Appointment::with(['patient.user'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time');

        $dateFilter = $request->input('date_filter');
        $dateFilterLabel = 'Tous les rendez-vous';

        switch ($dateFilter) {
            case 'today':
                $query->whereDate('appointment_date', Carbon::today());
                $dateFilterLabel = "Aujourd'hui - " . Carbon::today()->locale('fr')->isoFormat('DD MMM YYYY');
                break;
            case 'tomorrow':
                $query->whereDate('appointment_date', Carbon::tomorrow());
                $dateFilterLabel = "Demain - " . Carbon::tomorrow()->locale('fr')->isoFormat('DD MMM YYYY');
                break;
            case 'day_after_tomorrow':
                $query->whereDate('appointment_date', Carbon::today()->addDays(2));
                $dateFilterLabel = "Après-demain - " . Carbon::today()->addDays(2)->locale('fr')->isoFormat('DD MMM YYYY');
                break;
            case 'this_week':
                $query->whereBetween('appointment_date', [now()->startOfWeek(), now()->endOfWeek()]);
                $dateFilterLabel = 'Cette semaine';
                break;
            case 'next_week':
                $query->whereBetween('appointment_date', [now()->addWeek()->startOfWeek(), now()->addWeek()->endOfWeek()]);
                $dateFilterLabel = 'Semaine prochaine';
                break;
            case 'this_month':
                $query->whereMonth('appointment_date', now()->month)
                    ->whereYear('appointment_date', now()->year);
                $dateFilterLabel = 'Ce mois';
                break;
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($patientQuery) use ($search) {
                    $patientQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $appointments = $query->get();

        $stats = [
            'total' => $appointments->count(),
            'pending' => $appointments->where('status', 'pending')->count(),
            'confirmed' => $appointments->where('status', 'confirmed')->count(),
            'completed' => $appointments->where('status', 'completed')->count(),
        ];

        $pdf = Pdf::loadView('appointments.pdf', [
            'appointments' => $appointments,
            'stats' => $stats,
            'dateFilterLabel' => $dateFilterLabel,
            'generatedAt' => now()->locale('fr')->isoFormat('DD MMMM YYYY à HH:mm'),
            'statusFilter' => $request->status,
            'searchQuery' => $request->search,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('rendez-vous_' . now()->format('Y-m-d_His') . '.pdf');
    }
}