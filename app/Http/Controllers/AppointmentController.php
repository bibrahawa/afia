<?php

// Controller: AppointmentController.php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Employee;
use App\Services\SmsService;
use App\Models\AppointmentSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendAppointmentReminderJob;
use App\Jobs\ProcessDoctorUnavailabilityJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

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

    public function getDepartments()
    {
        $departments = Department::get();
        return response()->json($departments);
    }

    public function getProfessionals(Request $request, $id)
    {

        $departmentId = $id;

        $professionals = Employee::where('department_id', $departmentId)->where('type', 'Docteur')->get();

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
            'employee_id' => 'required|exists:employees,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'reason' => 'required|in:consultation,controle,urgence,suivi,prevention,bilan,vaccination,autre',
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
