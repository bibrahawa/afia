<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithAuthenticatedEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentQueryService;
use App\Services\AppointmentStatusService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DoctorAppointmentController extends Controller
{
    use InteractsWithAuthenticatedEmployee;

    public function index(Request $request, AppointmentQueryService $queryService)
    {
        $employeeId = $this->authenticatedEmployeeId();
        $appointments = $queryService->paginatedForDoctor($request, $employeeId);
        $stats = $queryService->statsForDoctor($employeeId);

        if ($request->ajax()) {
            return view('appointments.partials.list', compact('appointments'))->render();
        }

        return view('appointments.appointment', compact('appointments', 'stats'));
    }

    public function confirm(Appointment $appointment, AppointmentStatusService $statusService): RedirectResponse
    {
        // RÉACTIVÉ — cette vérification était commentée : n'importe quel
        // médecin connecté pouvait confirmer le rendez-vous d'un autre.
        abort_if($appointment->employee_id !== $this->authenticatedEmployeeId(), 403);

        try {
            $statusService->confirm($appointment);

            return back()->with('success', 'Rendez-vous confirmé avec succès.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur confirmation rendez-vous', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la confirmation du rendez-vous.');
        }
    }

    public function complete(Appointment $appointment, AppointmentStatusService $statusService): RedirectResponse
    {
        abort_if($appointment->employee_id !== $this->authenticatedEmployeeId(), 403);

        try {
            $statusService->complete($appointment);

            return back()->with('success', 'Rendez-vous marqué comme terminé avec succès.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur completion rendez-vous', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la mise à jour du rendez-vous.');
        }
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment, AppointmentStatusService $statusService): RedirectResponse
    {
        abort_if($appointment->employee_id !== $this->authenticatedEmployeeId(), 403);

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
}
