<?php

namespace App\Services;

use App\Models\PatientInsurance;
use Illuminate\Support\Collection;

class InsuranceCalculationService
{
    public function calculateInsuranceCoverage(int $patientId, array $items): array
    {
        $activeInsurances = $this->getActivePatientInsurances($patientId);

        if ($activeInsurances->isEmpty()) {
            $totalAmount = (float) array_sum(array_map(
                fn ($item) => (float) ($item['total'] ?? 0),
                $items
            ));

            return [
                'total_amount' => $totalAmount,
                'insurance_coverage' => 0,
                'patient_amount' => $totalAmount,
                'insurances_used' => [],
                'details' => [],
            ];
        }

        $totalAmount = 0.0;
        $totalInsuranceCoverage = 0.0;
        $insurancesUsed = [];
        $itemDetails = [];

        foreach ($items as $item) {
            $itemCoverage = $this->calculateItemCoverage($item, $activeInsurances);

            $totalInsuranceCoverage += (float) $itemCoverage['insurance_amount'];
            $totalAmount += (float) $itemCoverage['item_amount'];
            $itemDetails[] = $itemCoverage;

            foreach ($itemCoverage['insurances_applied'] as $insurance) {
                $patientInsuranceId = (int) $insurance['insurance_id'];

                if (!isset($insurancesUsed[$patientInsuranceId])) {
                    $insurancesUsed[$patientInsuranceId] = [
                        'insurance_id' => $patientInsuranceId, // patient_insurances.id
                        'insurance_company_id' => (int) $insurance['insurance_company_id'],
                        'insurance_company' => $insurance['insurance_company'],
                        'policy_number' => $insurance['policy_number'],
                        'total_covered' => 0.0,
                    ];
                }

                $insurancesUsed[$patientInsuranceId]['total_covered'] += (float) $insurance['amount_covered'];
            }
        }

        return [
            'total_amount' => round($totalAmount, 2),
            'insurance_coverage' => round($totalInsuranceCoverage, 2),
            'patient_amount' => round($totalAmount - $totalInsuranceCoverage, 2),
            'insurances_used' => array_values($insurancesUsed),
            'details' => $itemDetails,
        ];
    }

    public function getActivePatientInsurances(int $patientId): Collection
    {
        return PatientInsurance::query()
            ->where('patient_id', $patientId)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->with('insuranceCompany')
            ->get();
    }

    public function getAverageCoveragePercentage(array $insurancesApplied): float
    {
        if (empty($insurancesApplied)) {
            return 0;
        }

        $totalPercentage = array_sum(array_map(
            fn ($insurance) => (float) ($insurance['coverage_percentage'] ?? 0),
            $insurancesApplied
        ));

        return round($totalPercentage / count($insurancesApplied), 2);
    }

    private function calculateItemCoverage(array $item, Collection $activeInsurances): array
    {
        $quantity = (int) ($item['quantity'] ?? 1);
        $baseItemAmount = (float) ($item['total'] ?? 0);

        $itemAmount = $baseItemAmount;
        $totalCovered = 0.0;
        $remainingAmount = $baseItemAmount;
        $insurancesApplied = [];

        $sortedInsurances = $activeInsurances->sortBy('id');

        foreach ($sortedInsurances as $patientInsurance) {
            if ($remainingAmount <= 0) {
                break;
            }

            $coverage = $this->getCoverageForItem($item, $patientInsurance);

            if ($coverage) {
                $coveragePercentage = (float) $patientInsurance->coverage_percentage;
                $maxAmount = $coverage->coverage_amount_limit ? (float) $coverage->coverage_amount_limit : null;

                $itemAmount = (float) $coverage->acte_price * $quantity;
                $remainingAmount = $itemAmount - $totalCovered;
            } else {
                $coveragePercentage = 0.0;
                $maxAmount = null;
            }

            if ($coveragePercentage <= 0) {
                continue;
            }

            $coveredAmount = ($remainingAmount * $coveragePercentage) / 100;

            if ($maxAmount !== null && $coveredAmount > $maxAmount) {
                $coveredAmount = $maxAmount;
            }

            $remainingLimit = $patientInsurance->getRemainingLimit();
            if ($remainingLimit !== null && $coveredAmount > $remainingLimit) {
                $coveredAmount = (float) $remainingLimit;
            }

            if ($coveredAmount > 0) {
                $totalCovered += $coveredAmount;
                $remainingAmount -= $coveredAmount;

                $insurancesApplied[] = [
                    'insurance_id' => (int) $patientInsurance->id, // patient_insurances.id
                    'insurance_company_id' => (int) $patientInsurance->insurance_company_id,
                    'insurance_company' => $patientInsurance->insuranceCompany->name,
                    'policy_number' => $patientInsurance->policy_number,
                    'coverage_percentage' => $coveragePercentage,
                    'amount_covered' => round($coveredAmount, 2),
                    'remaining_after' => round($remainingAmount, 2),
                ];
            }
        }

        return [
            'item_description' => $item['description'] ?? '',
            'item_amount' => round($itemAmount, 2),
            'insurance_amount' => round($totalCovered, 2),
            'patient_amount' => round(max(0, $remainingAmount), 2),
            'insurances_applied' => $insurancesApplied,
        ];
    }

    private function getCoverageForItem(array $item, PatientInsurance $patientInsurance)
    {
        return $patientInsurance->insuranceCompany->getCoverageForService(
            $item['acte_type'],
            $item['acte_id'],
            $patientInsurance->insurance_company_id
        );
    }
}