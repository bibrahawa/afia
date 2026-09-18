<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\PatientInsurance;
use App\Http\Controllers\Controller;
use App\Services\InsuranceCalculationService;


class Api_InsuranceCalculationController extends Controller
{
    protected $insuranceService;

    public function __construct(InsuranceCalculationService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
    }

    /**
     * Calculer la couverture d'assurance via API
     */
    public function calculateCoverage(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'montant_original' => 'required|numeric|min:0',
            'insurance_ids' => 'array',
            'insurance_ids.*' => 'exists_etablissement:patient_insurances,id',
            'service_type' => 'string|nullable',
            'service_id' => 'numeric|nullable'
        ]);

        try {
            // Créer l'item de service pour le calcul
            $items = [[
                'type' => $request->service_type ?: 'App\\Models\\Service',
                'service_id' => $request->service_id ?: 1,
                'description' => 'Consultation médicale',
                'unit_price' => $request->montant_original,
                'quantity' => 1,
                'total' => $request->montant_original
            ]];

            // Si des assurances spécifiques sont demandées, les filtrer
            if (!empty($request->insurance_ids)) {
                // Temporairement limiter les assurances actives à celles demandées
                $originalMethod = 'getActivePatientInsurances';
                // Ici vous pourriez modifier temporairement la méthode ou créer une version spécialisée
            }

            $calculation = $this->insuranceService->calculateInsuranceCoverage(
                $request->patient_id, 
                $items
            );

            return response()->json([
                'success' => true,
                'calculation' => $calculation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de la couverture',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}