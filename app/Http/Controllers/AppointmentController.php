<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\AppointmentSlot;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentBookingService;
use App\Services\AppointmentStatusService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function makeAppointment()
    {
        return view('appointments.make_appointment');
    }


    public function index()
    {
        $departments = Department::where('is_active', true)
            ->with('activeProfessionals')
            ->orderBy('name')
            ->get();

        return view('appointments.index', compact('departments'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)
            ->with('activeProfessionals')
            ->orderBy('name')
            ->get();

        return view('appointments.create', compact('departments'));
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['patient.user', 'employee']);

        return view('appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment)
    {
        $departments = Department::where('is_active', true)
            ->with('activeProfessionals')
            ->orderBy('name')
            ->get();

        return view('appointments.edit', compact('appointment', 'departments'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment->update($validated);

        return redirect()->route('appointment.show', $appointment)->with('success', 'Rendez-vous mis à jour.');
    }

    public function store(StoreAppointmentRequest $request, AppointmentBookingService $bookingService)
    {
        try {
            
            $appointment = $bookingService->book($request->validated());

            return response()->json([
                'message' => 'Rendez-vous créé avec succès.',
                'appointment' => [
                    'id' => $appointment->id,
                    'date' => $appointment->appointment_date->format('d/m/Y'),
                    'time' => $appointment->appointment_time->format('H:i'),
                    'doctor' => 'Dr. ' . $appointment->employee->first_name . ' ' . $appointment->employee->last_name,
                    'reason' => $appointment->reason,
                    'status' => $appointment->status,
                ]
            ], 201);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création rendez-vous', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la création du rendez-vous.'], 500);
        }
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment, AppointmentStatusService $statusService)
    {
        try {
            $statusService->cancel($appointment, $request->input('reason'));

            return back()->with('success', 'Rendez-vous annulé avec succès.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur annulation rendez-vous', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de l’annulation du rendez-vous.');
        }
    }

    public function getDepartments()
    {
        return response()->json(Department::all());
    }

    public function checkPatient(Request $request)
    {
        $phone = preg_replace('/\D/', '', $request->phone ?? '');

        validator(
            ['phone' => $phone],
            ['phone' => ['required', 'regex:/^[0-9]{9}$/']],
            ['phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.']
        )->validate();

        $user = User::where('phone', $phone)->first();
        $patient = $user ? Patient::where('user_id', $user->id)->first() : null;

        // Cas 1 : un compte existe mais n'est pas un patient
        if ($user && !$patient) {
            return response()->json([
                'exists' => true,
                'is_patient' => false,
                'message' => 'Un compte existe avec ce numéro de téléphone, mais il n’est pas associé à un dossier patient.'
            ], 422);
        }

        // Cas 2 : aucun compte / aucun patient
        if (!$user || !$patient) {
            return response()->json([
                'exists' => false,
                'is_patient' => false
            ]);
        }

        // Cas 3 : patient trouvé
        return response()->json([
            'exists' => true,
            'is_patient' => true,
            'patient' => [
                'id' => $patient->id,
                'name' => trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')),
                'phone' => $user->phone,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
            ]
        ]);
    }

    public function getProfessionals(Request $request, $id)
    {
        $professionals = Employee::where('department_id', $id)
            ->where('type', 'Medecin')
            ->with('availabilities')
            ->get()
            ->map(function ($professional) {
                $professional->working_day = $professional->availabilities
                    ->pluck('day_of_week')
                    ->unique()
                    ->join(', ') ?: 'Disponibilité à confirmer';

                return $professional;
            });

        return response()->json($professionals);
    }

    public function getAvailableDates(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $availableDates = AppointmentSlot::where('employee_id', $request->employee_id)
            ->where('is_available', true)
            ->whereBetween('date', [
                Carbon::parse($request->start_date)->format('Y-m-d'),
                Carbon::parse($request->end_date)->format('Y-m-d'),
            ])
            ->where('date', '>=', now()->format('Y-m-d'))
            ->distinct()
            ->pluck('date')
            ->map(fn ($date) => substr((string) $date, 0, 10))
            ->values()
            ->toArray();

        return response()->json([
            'available_dates' => $availableDates
        ]);
    }

    public function getAvailableSlots(Request $request)
    {
        $professional = Employee::findOrFail($request->get('employee_id'));

        return response()->json($professional->getAvailableSlots($request->get('date')));
    }

    public function getAvailableSlotsByProfessional($id)
    {
        $professional = Employee::findOrFail($id);

        $slots = AppointmentSlot::where('employee_id', $id)
            ->where('is_available', true)
            ->whereBetween('date', [now()->format('Y-m-d'), now()->addDays(30)->format('Y-m-d')])
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->groupBy('date');

        return response()->json([
            'professional' => $professional,
            'slots' => $slots
        ]);
    }

    public function getAvailableSlotsByProfessionalAndDate($id, $date)
    {
        $dateCarbon = Carbon::parse($date);

        if ($dateCarbon->isPast()) {
            return response()->json(['error' => 'La date ne peut pas être dans le passé'], 400);
        }

        $professional = Employee::findOrFail($id);

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
}