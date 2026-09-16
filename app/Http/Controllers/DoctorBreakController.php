<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithAuthenticatedEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBreakRequest;
use App\Http\Requests\UpdateBreakRequest;
use App\Models\EmployeeBreak;
use App\Services\DoctorBreakService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DoctorBreakController extends Controller
{
    use InteractsWithAuthenticatedEmployee;

    public function index()
    {
        $breaks = EmployeeBreak::where('employee_id', $this->authenticatedEmployeeId())
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('appointments.breaks', compact('breaks'));
    }

    public function store(StoreBreakRequest $request, DoctorBreakService $service)
    {
        try {
            $service->create($this->authenticatedEmployeeId(), $request->validated());

            return back()->with('success', 'Pause créée avec succès.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur création pause', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la création de la pause.');
        }
    }

    public function update(UpdateBreakRequest $request, EmployeeBreak $break, DoctorBreakService $service)
    {
        try {
            $service->update($break, $this->authenticatedEmployeeId(), $request->validated());

            return back()->with('success', 'Pause mise à jour avec succès.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour pause', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la mise à jour de la pause.');
        }
    }

    public function destroy(EmployeeBreak $break, DoctorBreakService $service)
    {
        try {
            $service->delete($break, $this->authenticatedEmployeeId());

            return back()->with('success', 'Pause supprimée.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur suppression pause', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la suppression de la pause.');
        }
    }

    public function toggle(Request $request, EmployeeBreak $break, DoctorBreakService $service)
    {
        try {
            $message = $service->toggle(
                $break,
                $this->authenticatedEmployeeId(),
                filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
            );

            return response()->json(['success' => true, 'message' => $message]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur toggle pause', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour de la pause.'], 500);
        }
    }
}
