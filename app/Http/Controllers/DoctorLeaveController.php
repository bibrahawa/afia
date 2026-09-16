<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithAuthenticatedEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\Models\EmployeeBreak;
use App\Models\EmployeeLeave;
use App\Services\DoctorLeaveService;
use DomainException;
use Illuminate\Support\Facades\Log;

class DoctorLeaveController extends Controller
{
    use InteractsWithAuthenticatedEmployee;

    public function index()
    {
        $employeeId = $this->authenticatedEmployeeId();

        $leaves = EmployeeLeave::where('employee_id', $employeeId)->orderBy('start_date', 'desc')->get();
        $breaks = EmployeeBreak::where('employee_id', $employeeId)->orderBy('day_of_week')->orderBy('start_time')->get();

        return view('appointments.leaves', compact('leaves', 'breaks'));
    }

    public function store(StoreLeaveRequest $request, DoctorLeaveService $service)
    {
        try {
            $service->create($this->authenticatedEmployeeId(), $request->validated());

            return back()->with('success', 'Congé créé avec succès. Les rendez-vous concernés ont été annulés.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur création congé', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la création du congé.');
        }
    }

    public function update(UpdateLeaveRequest $request, EmployeeLeave $leave, DoctorLeaveService $service)
    {
        try {
            $result = $service->update($leave, $this->authenticatedEmployeeId(), $request->validated());

            return back()->with(
                'success',
                "Congé mis à jour. {$result['restored_appointments']} rendez-vous réactivés, {$result['cancelled_appointments']} annulés."
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour congé', ['leave_id' => $leave->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la mise à jour du congé.');
        }
    }

    public function destroy(EmployeeLeave $leave, DoctorLeaveService $service)
    {
        try {
            $result = $service->delete($leave, $this->authenticatedEmployeeId());

            return back()->with('success', "Congé supprimé. {$result['restored_appointments']} rendez-vous réactivés.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur suppression congé', ['leave_id' => $leave->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la suppression du congé.');
        }
    }
}
