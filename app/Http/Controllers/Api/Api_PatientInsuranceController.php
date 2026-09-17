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
     * Créer une nouvelle assurance patient
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'insurance_company_id' => 'required|exists_etablissement:insurance_companies,id',
            'policy_number' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'annual_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        // Vérifier que la police n'existe pas déjà pour ce patient et cette compagnie
        $existingPolicy = PatientInsurance::where('patient_id', $request->patient_id)
            ->where('insurance_company_id', $request->insurance_company_id)
            ->where('policy_number', $request->policy_number)
            ->where('status', 'active')
            ->first();

        if ($existingPolicy) {
            return redirect()->back()->with('error', 'Cette police d\'assurance existe déjà pour ce patient.');
        }

        PatientInsurance::create($request->all());

        return redirect()->back()->with('success', 'Assurance patient créée avec succès.');
    }

    /**
     * Mettre à jour une assurance patient
     */
    public function update(Request $request, PatientInsurance $patientInsurance)
    {
        $request->validate([
            'policy_number' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:active,suspended,expired',
            'annual_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $patientInsurance->update($request->all());

        return redirect()->back()->with('success', 'Assurance patient mise à jour avec succès.');
    }
}