<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithAuthenticatedEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvailabilityRequest;
use App\Http\Requests\UpdateAvailabilityRequest;
use App\Models\EmployeeAvailability;
use App\Services\DoctorAvailabilityService;
use DomainException;
use Illuminate\Support\Facades\Log;

class DoctorAvailabilityController extends Controller
{
    use InteractsWithAuthenticatedEmployee;

    public function index()
    {
        $availabilities = EmployeeAvailability::where('employee_id', $this->authenticatedEmployeeId())
            ->orderBy('day_of_week')
            ->get();

        return view('appointments.disponibilite', compact('availabilities'));
    }

    public function store(StoreAvailabilityRequest $request, DoctorAvailabilityService $service)
    {
        try {
            $service->create($this->authenticatedEmployeeId(), $request->validated());

            return back()->with('success', 'Disponibilité créée avec succès.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur création disponibilité', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la création de la disponibilité.');
        }
    }

    public function update(UpdateAvailabilityRequest $request, EmployeeAvailability $availability, DoctorAvailabilityService $service)
    {
        try {
            $cancelledCount = $service->update($availability, $this->authenticatedEmployeeId(), $request->validated());

            $message = 'Disponibilité mise à jour avec succès.';
            if ($cancelledCount > 0) {
                $message .= " {$cancelledCount} rendez-vous annulé(s) car hors du nouvel horaire.";
            }

            return back()->with('success', $message);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour disponibilité', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la mise à jour de la disponibilité.');
        }
    }

    public function destroy(EmployeeAvailability $availability, DoctorAvailabilityService $service)
    {
        try {
            $service->delete($availability, $this->authenticatedEmployeeId());

            return back()->with('success', 'Disponibilité supprimée avec succès.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur suppression disponibilité', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la suppression de la disponibilité.');
        }
    }
}
