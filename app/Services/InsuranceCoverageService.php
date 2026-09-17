<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\PatientInsurance;

class InsuranceCoverageService
{
    public function calculateInsuranceCoverage(int $patientId, array $items): array
    {
        $activeInsurances = $this->getActivePatientInsurances($patientId);

        if ($activeInsurances->isEmpty()) {
            $total = array_sum(array_column($items, 'total'));

            return [
                'total_amount' => $total,
                'insurance_coverage' => 0,
                'patient_amount' => $total,
                'insurances_used' => [],
                'details' => [],
            ];
        }

        $totalAmount = 0;
        $totalInsuranceCoverage = 0;
        $insurancesUsed = [];
        $itemDetails = [];

        foreach ($items as $item) {
            $itemCoverage = $this->calculateItemCoverage($item, $activeInsurances);

            $totalInsuranceCoverage += $itemCoverage['insurance_amount'];
            $totalAmount += $itemCoverage['item_amount'];
            $itemDetails[] = $itemCoverage;

            foreach ($itemCoverage['insurances_applied'] as $insurance) {
                if (!isset($insurancesUsed[$insurance['insurance_id']])) {
                    $insurancesUsed[$insurance['insurance_id']] = [
                        'insurance_id' => $insurance['insurance_id'],
                        'insurance_company' => $insurance['insurance_company'],
                        'policy_number' => $insurance['policy_number'],
                        'total_covered' => 0,
                    ];
                }

                $insurancesUsed[$insurance['insurance_id']]['total_covered'] += $insurance['amount_covered'];
            }
        }

        return [
            'total_amount' => $totalAmount,
            'insurance_coverage' => $totalInsuranceCoverage,
            'patient_amount' => $totalAmount - $totalInsuranceCoverage,
            'insurances_used' => array_values($insurancesUsed),
            'details' => $itemDetails,
        ];
    }

    public function createInsuranceClaims(Invoice $invoice, array $insurancesUsed, int $patientId): void
    {
        foreach ($insurancesUsed as $insuranceData) {
            InsuranceClaim::create([
                'claim_number' => $this->generateClaimNumber($invoice->etablissement_id),
                'invoice_id' => $invoice->id,
                'insurance_company_id' => $insuranceData['insurance_id'],
                'patient_id' => $patientId,
                'claimed_amount' => $insuranceData['total_covered'],
                'status' => 'draft',
            ]);
        }
    }

    public function getAverageCoveragePercentage(array $insurancesApplied): ?float
    {
        if (empty($insurancesApplied)) {
            return null;
        }

        $total = array_sum(array_column($insurancesApplied, 'coverage_percentage'));

        return $total / count($insurancesApplied);
    }

    private function getActivePatientInsurances(int $patientId)
    {
        return PatientInsurance::where('patient_id', $patientId)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->with('insuranceCompany')
            ->get();
    }

    private function calculateItemCoverage(array $item, $activeInsurances): array
    {
        $itemAmount = (float) $item['total'];
        $totalCovered = 0;
        $remainingAmount = $itemAmount;
        $insurancesApplied = [];

        foreach ($activeInsurances->sortBy('id') as $patientInsurance) {
            if ($remainingAmount <= 0) {
                break;
            }

            $coverage = $patientInsurance->insuranceCompany->getCoverageForService(
                $item['acte_type'],
                $item['acte_id'],
                $patientInsurance->insurance_company_id
            );

            if (!$coverage) {
                continue;
            }

            $coveragePercentage = (float) $patientInsurance->coverage_percentage;
            $maxAmount = $coverage->coverage_amount_limit;

            $remainingAmount = (float) $coverage->acte_price * (int) $item['quantity'];
            $itemAmount = $remainingAmount;

            if ($coveragePercentage <= 0) {
                continue;
            }

            $coveredAmount = ($remainingAmount * $coveragePercentage) / 100;

            if ($maxAmount && $coveredAmount > $maxAmount) {
                $coveredAmount = $maxAmount;
            }

            $remainingLimit = $patientInsurance->getRemainingLimit();
            if ($remainingLimit !== null && $coveredAmount > $remainingLimit) {
                $coveredAmount = $remainingLimit;
            }

            if ($coveredAmount > 0) {
                $totalCovered += $coveredAmount;
                $remainingAmount -= $coveredAmount;

                $insurancesApplied[] = [
                    'insurance_id' => $patientInsurance->insuranceCompany->id,
                    'insurance_company' => $patientInsurance->insuranceCompany->name,
                    'policy_number' => $patientInsurance->policy_number,
                    'coverage_percentage' => $coveragePercentage,
                    'amount_covered' => $coveredAmount,
                    'remaining_after' => $remainingAmount,
                ];
            }
        }

        return [
            'item_description' => $item['description'],
            'item_amount' => $itemAmount,
            'insurance_amount' => $totalCovered,
            'patient_amount' => $remainingAmount,
            'insurances_applied' => $insurancesApplied,
        ];
    }

    /** CLM-2026000123 — numérotation atomique propre à l'établissement (fini le count()+1 à doublons). */
    private function generateClaimNumber(?int $etablissementId): string
    {
        $etablissementId ??= \App\Support\EtablissementContext::id();

        if (! $etablissementId) {
            throw new \LogicException('Réclamation d\'assurance sans établissement : impossible de la numéroter.');
        }

        return app(\App\Services\NumerotationDocumentService::class)->numero($etablissementId, 'CLM', 'reclamation-assurance');
    }
}