<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\PatientInsurance;
use App\Models\Patient;
use App\Http\Controllers\Controller;

class Api_PatientInsuranceController extends Controller
{
    /**
     * Récupérer les assurances d'un patient (API)
     */
    public function getPatientInsurances(Patient $patient)
    {
        try {
            $insurances = PatientInsurance::where('patient_id', $patient->id)
                ->with(['insuranceCompany' => function($query) {
                    $query->select('id', 'name', 'code', 'default_coverage_percentage', 'status');
                }])
                ->get()
                ->map(function($insurance) {
                    return [
                        'id' => $insurance->id,
                        'policy_number' => $insurance->policy_number,
                        'status' => $insurance->status,
                        'start_date' => $insurance->start_date->format('Y-m-d'),
                        'end_date' => $insurance->end_date ? $insurance->end_date->format('Y-m-d') : null,
                        'annual_limit' => $insurance->annual_limit,
                        'used_amount' => $insurance->used_amount,
                        'remaining_limit' => $insurance->getRemainingLimit(),
                        'is_active' => $insurance->isActive(),
                        'insurance_company' => [
                            'id' => $insurance->insuranceCompany->id,
                            'name' => $insurance->insuranceCompany->name,
                            'code' => $insurance->insuranceCompany->code,
                            'default_coverage_percentage' => $insurance->insuranceCompany->default_coverage_percentage,
                            'status' => $insurance->insuranceCompany->status
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'insurances' => $insurances
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des assurances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DÉSACTIVÉ (lot 2b) — ces méthodes n'étaient reliées à aucune route et
     * écrivaient directement dans patient_insurances, sans passer par le
     * référentiel assurance (contrat, formule, adhésion, bénéficiaire).
     * Toute création ou modification passe par Assurance\ReferentielAssuranceService.
     */
    public function store(Request $request)
    {
        abort(410, 'Utilisez le module Assurance (contrats et adhésions).');
    }

    public function update(Request $request, PatientInsurance $patientInsurance)
    {
        abort(410, 'Utilisez le module Assurance (contrats et adhésions).');
    }
}