<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\SmsService;
use App\Models\AppointmentSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendAppointmentReminderJob;
use App\Jobs\ProcessDoctorUnavailabilityJob;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class AppointmentController extends Controller
{

    /**
     * Export des rendez-vous en PDF
     */
    public function exportPdf(Request $request)
    {
        
        if (!$request->date_filter) {
            return redirect()->back()->with('error', 'Le filtre de date est requis pour l\'export PDF.');
        }
        
        // Construction de la requête de base
        
        $query = Appointment::with(['patient.user'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc');
        
        // Filtrage par date
        $dateFilter = $request->input('date_filter', '');
        $dateFilterLabel = 'Tous les rendez-vous';
        
        switch ($dateFilter) {
            case 'today':
                $query->whereDate('appointment_date', Carbon::today());
                $dateFilterLabel = 'Aujourd\'hui - ' . Carbon::today()->locale('fr')->isoFormat('DD MMM YYYY');
                break;
                
            case 'tomorrow':
                $query->whereDate('appointment_date', Carbon::tomorrow());
                $dateFilterLabel = 'Demain - ' . Carbon::tomorrow()->locale('fr')->isoFormat('DD MMM YYYY');
                break;
                
            case 'day_after_tomorrow':
                $query->whereDate('appointment_date', Carbon::today()->addDays(2));
                $dateFilterLabel = 'Après-demain - ' . Carbon::today()->addDays(2)->locale('fr')->isoFormat('DD MMM YYYY');
                break;
                
            case 'this_week':
                $query->whereBetween('appointment_date', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
                $dateFilterLabel = 'Cette semaine - ' . 
                    Carbon::now()->startOfWeek()->locale('fr')->isoFormat('DD') . ' - ' . 
                    Carbon::now()->endOfWeek()->locale('fr')->isoFormat('DD MMM YYYY');
                break;
                
            case 'next_week':
                $query->whereBetween('appointment_date', [
                    Carbon::now()->addWeek()->startOfWeek(),
                    Carbon::now()->addWeek()->endOfWeek()
                ]);
                $dateFilterLabel = 'Semaine prochaine - ' . 
                    Carbon::now()->addWeek()->startOfWeek()->locale('fr')->isoFormat('DD') . ' - ' . 
                    Carbon::now()->addWeek()->endOfWeek()->locale('fr')->isoFormat('DD MMM YYYY');
                break;
                
            case 'this_month':
                $query->whereMonth('appointment_date', Carbon::now()->month)
                    ->whereYear('appointment_date', Carbon::now()->year);
                $dateFilterLabel = 'Ce mois - ' . Carbon::now()->locale('fr')->isoFormat('MMMM YYYY');
                break;
        }
        
        // Filtrage par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('patient', function($patientQuery) use ($search) {
                    $patientQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhere('appointment_date', 'like', "%{$search}%");
            });
        }
        
        // Récupérer tous les rendez-vous (sans pagination pour le PDF)
        $appointments = $query->get();
        
        // Statistiques
        $stats = [
            'total' => $appointments->count(),
            'pending' => $appointments->where('status', 'pending')->count(),
            'confirmed' => $appointments->where('status', 'confirmed')->count(),
            'completed' => $appointments->where('status', 'completed')->count(),
        ];
        
        // Données à passer à la vue PDF
        $data = [
            'appointments' => $appointments,
            'stats' => $stats,
            // 'medecin' => $medecin,
            'dateFilterLabel' => $dateFilterLabel,
            'generatedAt' => Carbon::now()->locale('fr')->isoFormat('DD MMMM YYYY à HH:mm'),
            'statusFilter' => $request->status,
            'searchQuery' => $request->search,
        ];
        
        // Générer le PDF
        $pdf = Pdf::loadView('appointments.pdf', $data);
        
        // Configuration du PDF
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('margin-top', 0);
        
        // Nom du fichier
        $filename = 'rendez-vous_' . Carbon::now()->format('Y-m-d_His') . '.pdf';
        
        // Télécharger le PDF
        return $pdf->download($filename);
    }

    public function index()
    {
        $departments = Department::where('is_active', true)
            ->with('activeProfessionals')
            ->orderBy('name')
            ->get();

        return view('appointments.index', compact('departments'));
    }

    public function getDepartments()
    {
        $departments = Department::get();
        return response()->json($departments);
    }

    // Dans votre AppointmentController.php
    public function checkPatient(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);
        
        $phone = preg_replace('/\D/', '', $request->phone); // Nettoyer le numéro

        $patient = User::where('phone', $phone)->first();

        if ($patient) {
            return response()->json([
                'exists' => true,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'phone' => $patient->phone
                ]
            ]);
        }
        
        return response()->json([
            'exists' => false
        ]);
    }

    public function getProfessionals(Request $request, $id)
    {

        $departmentId = $id;

        $professionals = Employee::where('department_id', $departmentId)
            ->where('type', 'Medecin')
            ->get();

        return response()->json($professionals);
    }

    /**
     * NOUVELLE FONCTION: Obtenir les dates disponibles pour un professionnel sur une période
     */
    public function getAvailableDates(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $employeeId = $request->get('employee_id');
        $startDate = Carbon::parse($request->get('start_date'));
        $endDate = Carbon::parse($request->get('end_date'));

        $employee = Employee::findOrFail($employeeId);
        
        // Récupérer toutes les dates avec des créneaux disponibles
        $availableDates = AppointmentSlot::where('employee_id', $employeeId)
            ->where('is_available', true)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('date', '>=', now()->format('Y-m-d'))
            ->distinct()
            ->pluck('date')
            ->map(function($date) {
                // Forcer le format Y-m-d
                if ($date instanceof \Carbon\Carbon) {
                    return $date->format('Y-m-d');
                }
                return substr($date, 0, 10); // Prendre uniquement les 10 premiers caractères
            })
            ->values()
            ->toArray();

        return response()->json([
            'available_dates' => $availableDates
        ]);
    }

    public function getAvailableSlots(Request $request)
    {
        $professionalId = $request->get('employee_id');
        $date = $request->get('date');

        $professional = Employee::findOrFail($professionalId);
        $slots = $professional->getAvailableSlots($date);

        return response()->json($slots);
    }

    /**
     * NOUVELLE FONCTION: Obtenir les créneaux disponibles par professionnel
     */
    public function getAvailableSlotsByProfessional($id)
    {
        $professional = Employee::findOrFail($id);
        
        // Récupérer les créneaux disponibles pour les 30 prochains jours
        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(30)->format('Y-m-d');
        
        $slots = AppointmentSlot::where('employee_id', $id)
            ->where('is_available', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->groupBy('date');

        return response()->json([
            'professional' => $professional,
            'slots' => $slots
        ]);
    }

    /**
     * NOUVELLE FONCTION: Obtenir les créneaux disponibles par professionnel et date
     */
    public function getAvailableSlotsByProfessionalAndDate($id, $date)
    {
        $professional = Employee::findOrFail($id);
        
        // Valider la date
        try {
            $dateCarbon = Carbon::parse($date);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Date invalide'], 400);
        }

        if ($dateCarbon->isPast()) {
            return response()->json(['error' => 'La date ne peut pas être dans le passé'], 400);
        }

        $slots = AppointmentSlot::where('employee_id', $id)
            ->where('date', $date)
            ->where('is_available', true)
            ->orderBy('time')
            ->get();

        return response()->json([
            'professional' => $professional,
            'date' => $date,
            'slots' => $slots
        ]);
    }

    /**
     * NOUVELLE FONCTION: Vérifier la disponibilité d'un créneau spécifique
     */
    public function getAvailableSlotsByProfessionalDateAndTime($id, $date, $time)
    {
        $professional = Employee::findOrFail($id);
        
        // Valider la date
        try {
            $dateCarbon = Carbon::parse($date);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Date invalide'], 400);
        }

        if ($dateCarbon->isPast()) {
            return response()->json(['error' => 'La date ne peut pas être dans le passé'], 400);
        }

        $slot = AppointmentSlot::where('employee_id', $id)
            ->where('date', $date)
            ->where('time', $time)
            ->where('is_available', true)
            ->first();

        if (!$slot) {
            return response()->json([
                'available' => false,
                'message' => 'Ce créneau n\'est pas disponible'
            ], 404);
        }

        return response()->json([
            'available' => true,
            'professional' => $professional,
            'slot' => $slot
        ]);
    }

    /**
     * NOUVELLE FONCTION: Obtenir les créneaux disponibles avec durée personnalisée
     */
    public function getAvailableSlotsByProfessionalDateTimeAndDuration($id, $date, $time, $duration)
    {
        $professional = Employee::findOrFail($id);
        
        // Valider la durée (en minutes)
        if (!is_numeric($duration) || $duration <= 0 || $duration > 240) {
            return response()->json(['error' => 'Durée invalide (max 240 minutes)'], 400);
        }

        // Valider la date
        try {
            $dateCarbon = Carbon::parse($date);
            $timeCarbon = Carbon::parse($date . ' ' . $time);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Date ou heure invalide'], 400);
        }

        if ($dateCarbon->isPast()) {
            return response()->json(['error' => 'La date ne peut pas être dans le passé'], 400);
        }

        // Calculer l'heure de fin
        $endTime = $timeCarbon->copy()->addMinutes($duration)->format('H:i');

        // Vérifier si tous les créneaux nécessaires sont disponibles
        $requiredSlots = ceil($duration / 30); // Supposant des créneaux de 30 min
        $availableSlots = AppointmentSlot::where('employee_id', $id)
            ->where('date', $date)
            ->where('time', '>=', $time)
            ->where('time', '<', $endTime)
            ->where('is_available', true)
            ->orderBy('time')
            ->get();

        $isAvailable = $availableSlots->count() >= $requiredSlots;

        return response()->json([
            'available' => $isAvailable,
            'professional' => $professional,
            'date' => $date,
            'start_time' => $time,
            'end_time' => $endTime,
            'duration' => $duration,
            'required_slots' => $requiredSlots,
            'available_slots' => $availableSlots->count(),
            'slots' => $availableSlots
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        // Vérifier la disponibilité
        $employee = Employee::findOrFail($request->employee_id);
        if (!$employee->isAvailableOn($request->appointment_date, $request->appointment_time)) {
            return response()->json(['error' => 'Ce créneau n\'est plus disponible'], 400);
        }

        $appointment = null;

        DB::transaction(function () use ($request, &$appointment) {
            // Créer le rendez-vous
            $appointment = Appointment::create([
                'employee_id' => $request->employee_id,
                'patient_id' => $request->patient_id ?? Auth::id(),
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'reason' => $request->reason,
                'description' => $request->description,
                'status' => 'pending'
            ]);

            // Marquer le slot comme indisponible
            AppointmentSlot::where('employee_id', $request->employee_id)
                ->where('date', $request->appointment_date)
                ->where('time', $request->appointment_time)
                ->update(['is_available' => false]);
        });

        $this->confirmAppointment($appointment);

        return response()->json(['message' => 'Rendez-vous créé avec succès']);
    }

    public function myAppointments()
    {
        $appointments = Appointment::where('patient_id', Auth::id())
            ->with(['professional.department'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        return view('appointments.my-appointments', compact('appointments'));
    }

    public function cancel($id)
    {
        $appointment = Appointment::find($id);
        // if ($appointment->patient_id !== Auth::id()) {
        //     abort(403);
        // }
        // if (!$appointment->canBeCancelled()) {
        //     return response()->json(['error' => 'Ce rendez-vous ne peut pas être annulé'], 400);
        // }

        DB::transaction(function () use ($appointment) {
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            // Libérer le slot
            AppointmentSlot::where('employee_id', $appointment->employee_id)
                ->where('date', $appointment->appointment_date)
                ->where('time', $appointment->appointment_time)
                ->update(['is_available' => true]);
        });

        $appointment->delete();

        return redirect()->back()->with(['success' => 'Rendez-vous annulé avec succès']);
    }

    public function cancelAppointment(Request $request, Appointment $appointment)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$appointment->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rendez-vous ne peut plus être annulé'
            ], 400);
        }

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason
        ]);

        // Envoyer SMS d'annulation
        SendAppointmentReminderJob::dispatchSync($appointment, 'cancellation');

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous annulé avec succès'
        ]);
    }

    public function confirmAppointment(Appointment $appointment)
    {
        if ($appointment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce rendez-vous ne peut plus être confirmé'
            ], 400);
        }

        $appointment->update([
            'status' => 'confirmed',
            'patient_confirmed' => true,
            'confirmed_at' => now()
        ]);

        // Envoyer SMS de confirmation
        // SendAppointmentReminderJob::dispatchSync($appointment, 'confirmation');
        
        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous confirmé avec succès',
            'appointment' => $appointment->load(['patient', 'employee'])
        ]);
    }

    public function setDoctorUnavailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'unavailable_from' => 'required|date|after:now',
            'unavailable_to' => 'required|date|after:unavailable_from',
            'reason' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Lancer le job de traitement d'indisponibilité
        ProcessDoctorUnavailabilityJob::dispatch(
            $request->employee_id,
            Carbon::parse($request->unavailable_from),
            Carbon::parse($request->unavailable_to),
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Indisponibilité enregistrée. Les patients concernés vont être notifiés.'
        ]);
    }

    public function rescheduleAppointment(Request $request, Appointment $appointment)
    {
        $validator = Validator::make($request->all(), [
            'new_appointment_date' => 'required|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $oldDate = $appointment->appointment_date;
        
        $appointment->update([
            'appointment_date' => $request->new_appointment_date,
            'status' => 'pending', // Redevient en attente de confirmation
            'patient_confirmed' => false,
            'reminder_sent_at' => null
        ]);

        // Envoyer SMS de report
        SendAppointmentReminderJob::dispatchSync($appointment, 'rescheduling');

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous reporté avec succès',
            'old_date' => $oldDate->format('d/m/Y H:i'),
            'new_date' => $appointment->appointment_date->format('d/m/Y H:i')
        ]);
    }
}