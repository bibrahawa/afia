<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PatientInsurance;

class Api_InsuranceValidationMiddleware
{
    public function handle($request, Closure $next)
    {
        if ($request->has('use_insurance') && $request->use_insurance) {
            // Valider que les assurances sélectionnées appartiennent au patient
            $patientId = $request->patient_id;
            $selectedInsurances = $request->selected_insurances ?? [];

            foreach ($selectedInsurances as $insurance) {
                $patientInsurance = PatientInsurance::where('id', $insurance['id'])
                    ->where('patient_id', $patientId)
                    ->where('status', 'active')
                    ->first();

                if (!$patientInsurance || !$patientInsurance->isActive()) {
                    return redirect()->back()->with('error', 'Assurance non valide ou inactive.');
                }
            }
        }

        return $next($request);
    }
}