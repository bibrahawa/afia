<?php

// Controller: AppointmentController.php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\AppointmentSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index()
    {
        $departments = Department::where('is_active', true)
            ->with('activeProfessionals')
            ->orderBy('name')
            ->get();

        return view('appointments.index', compact('departments'));
    }

    public function getProfessionals(Request $request)
    {
        $departmentId = $request->get('department_id');

        $professionals = Employee::where('department_id', $departmentId)
            ->where('is_active', true)
            ->get();

        return response()->json($professionals);
    }

    public function getAvailableSlots(Request $request)
    {
        $professionalId = $request->get('employee_id');
        $date = $request->get('date');

        $professional = Employee::findOrFail($professionalId);
        $slots = $professional->getAvailableSlots($date);

        return response()->json($slots);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:professionals,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'reason' => 'required|in:consultation,controle,urgence,suivi,prevention,bilan,vaccination,autre',
            'description' => 'nullable|string|max:1000'
        ]);

        // Vérifier la disponibilité
        $professional = Employee::findOrFail($request->employee_id);
        if (!$professional->isAvailableOn($request->appointment_date, $request->appointment_time)) {
            return response()->json(['error' => 'Ce créneau n\'est plus disponible'], 400);
        }

        DB::transaction(function () use ($request) {
            // Créer le rendez-vous
            $appointment = Appointment::create([
                'employee_id' => $request->employee_id,
                'patient_id' => Auth::id(),
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

    public function cancel(Appointment $appointment)
    {
        if ($appointment->patient_id !== Auth::id()) {
            abort(403);
        }

        if (!$appointment->canBeCancelled()) {
            return response()->json(['error' => 'Ce rendez-vous ne peut pas être annulé'], 400);
        }

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

        return response()->json(['message' => 'Rendez-vous annulé avec succès']);
    }
}

// Controller: ProfessionalController.php
namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\ProfessionalAvailability;
use App\Models\ProfessionalLeave;
use App\Models\AppointmentSlot;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfessionalController extends Controller
{
    public function dashboard()
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if (!$professional) {
            abort(403, 'Accès non autorisé');
        }

        $todayAppointments = Appointment::where('employee_id', $professional->id)
            ->today()
            ->with('patient')
            ->orderBy('appointment_time')
            ->get();

        $upcomingAppointments = Appointment::where('employee_id', $professional->id)
            ->upcoming()
            ->where('appointment_date', '>', now()->toDateString())
            ->take(10)
            ->with('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        return view('professional.dashboard', compact('professional', 'todayAppointments', 'upcomingAppointments'));
    }

    public function availability()
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        $availabilities = ProfessionalAvailability::where('employee_id', $professional->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('professional.availability', compact('professional', 'availabilities'));
    }

    public function storeAvailability(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:120'
        ]);

        $professional = Employee::where('email', Auth::user()->email)->first();

        $availability = ProfessionalAvailability::create([
            'employee_id' => $professional->id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'slot_duration' => $request->slot_duration,
            'is_active' => true
        ]);

        // Générer les slots pour les 3 prochains mois
        $this->generateSlotsForAvailability($availability);

        return response()->json(['message' => 'Disponibilité ajoutée avec succès']);
    }

    public function updateAvailability(Request $request, ProfessionalAvailability $availability)
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if ($availability->employee_id !== $professional->id) {
            abort(403);
        }

        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:120',
            'is_active' => 'boolean'
        ]);

        $availability->update($request->all());

        // Régénérer les slots si nécessaire
        if ($request->has('start_time') || $request->has('end_time') || $request->has('slot_duration')) {
            $this->regenerateSlotsForAvailability($availability);
        }

        return response()->json(['message' => 'Disponibilité mise à jour avec succès']);
    }

    public function deleteAvailability(ProfessionalAvailability $availability)
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if ($availability->employee_id !== $professional->id) {
            abort(403);
        }

        $availability->delete();

        return response()->json(['message' => 'Disponibilité supprimée avec succès']);
    }

    public function leaves()
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        $leaves = ProfessionalLeave::where('employee_id', $professional->id)
            ->orderBy('start_date', 'desc')
            ->get();

        return view('professional.leaves', compact('professional', 'leaves'));
    }

    public function storeLeave(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
            'type' => 'required|in:vacation,sick,conference,other'
        ]);

        $professional = Employee::where('email', Auth::user()->email)->first();

        $leave = ProfessionalLeave::create([
            'employee_id' => $professional->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'type' => $request->type
        ]);

        // Marquer les slots comme indisponibles pendant la période de congé
        $this->markSlotsUnavailableForLeave($leave);

        return response()->json(['message' => 'Congé ajouté avec succès']);
    }

    public function appointments()
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        $appointments = Appointment::where('employee_id', $professional->id)
            ->with('patient')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate(20);

        return view('professional.appointments', compact('professional', 'appointments'));
    }

    public function confirmAppointment(Appointment $appointment)
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if ($appointment->employee_id !== $professional->id) {
            abort(403);
        }

        $appointment->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);

        return response()->json(['message' => 'Rendez-vous confirmé avec succès']);
    }

    public function completeAppointment(Appointment $appointment, Request $request)
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if ($appointment->employee_id !== $professional->id) {
            abort(403);
        }

        $request->validate([
            'notes' => 'nullable|string|max:2000'
        ]);

        $appointment->update([
            'status' => 'completed',
            'notes' => $request->notes
        ]);

        return response()->json(['message' => 'Rendez-vous marqué comme terminé']);
    }

    private function generateSlotsForAvailability(ProfessionalAvailability $availability)
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonths(3);

        $dayOfWeekMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0
        ];

        $targetDayOfWeek = $dayOfWeekMap[$availability->day_of_week];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek === $targetDayOfWeek) {
                $this->generateSlotsForDate($availability, $date);
            }
        }
    }

    private function generateSlotsForDate(ProfessionalAvailability $availability, Carbon $date)
    {
        $startTime = Carbon::parse($availability->start_time);
        $endTime = Carbon::parse($availability->end_time);
        $slotDuration = $availability->slot_duration;

        $currentTime = $startTime->copy();

        while ($currentTime->lt($endTime)) {
            AppointmentSlot::updateOrCreate([
                'employee_id' => $availability->employee_id,
                'date' => $date->toDateString(),
                'time' => $currentTime->toTimeString()
            ], [
                'is_available' => true
            ]);

            $currentTime->addMinutes($slotDuration);
        }
    }

    private function regenerateSlotsForAvailability(ProfessionalAvailability $availability)
    {
        // Supprimer les anciens slots futurs pour ce jour
        AppointmentSlot::where('employee_id', $availability->employee_id)
            ->where('date', '>=', Carbon::today())
            ->whereRaw('DAYOFWEEK(date) = ?', [
                $this->getDayOfWeekNumber($availability->day_of_week)
            ])
            ->delete();

        // Régénérer les slots
        $this->generateSlotsForAvailability($availability);
    }

    private function markSlotsUnavailableForLeave(ProfessionalLeave $leave)
    {
        AppointmentSlot::where('employee_id', $leave->employee_id)
            ->whereBetween('date', [$leave->start_date, $leave->end_date])
            ->update([
                'is_available' => false,
                'reason_unavailable' => 'Congé: ' . $leave->reason
            ]);
    }

    private function getDayOfWeekNumber($dayName)
    {
        $days = [
            'sunday' => 1,
            'monday' => 2,
            'tuesday' => 3,
            'wednesday' => 4,
            'thursday' => 5,
            'friday' => 6,
            'saturday' => 7
        ];

        return $days[$dayName] ?? 1;
    }
}
