<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\PatientInsurance;
use Illuminate\Support\Facades\DB;

class InsuranceConsumptionService
{
    public function applyConsumptionsAndClaims(Invoice $invoice, array $insurancesUsed, int $patientId): void
    {
        if (empty($insurancesUsed)) {
            return;
        }

        foreach ($insurancesUsed as $insuranceUsed) {
            $patientInsurance = PatientInsurance::find($insuranceUsed['insurance_id']);

            if (!$patientInsurance) {
                continue;
            }

            $patientInsurance->increment('used_amount', (float) $insuranceUsed['total_covered']);
        }

        $this->createInsuranceClaims($invoice, $insurancesUsed, $patientId);
    }

    public function createInsuranceClaims(Invoice $invoice, array $insurancesUsed, int $patientId): void
    {
        foreach ($insurancesUsed as $insuranceData) {
            InsuranceClaim::create([
                'claim_number' => $this->generateClaimNumber($invoice->etablissement_id),
                'invoice_id' => $invoice->id,
                'insurance_company_id' => $insuranceData['insurance_company_id'] ?? null,
                'patient_insurance_id' => $insuranceData['insurance_id'] ?? null,
                'patient_id' => $patientId,
                'claimed_amount' => (float) $insuranceData['total_covered'],
                'status' => 'draft',
            ]);
        }
    }

    public function rollbackConsumption(Invoice $invoice): void
    {
        $claims = InsuranceClaim::where('invoice_id', $invoice->id)->get();

        foreach ($claims as $claim) {
            if (!$claim->patient_insurance_id) {
                continue;
            }

            $patientInsurance = PatientInsurance::find($claim->patient_insurance_id);

            if (!$patientInsurance) {
                continue;
            }

            $newUsedAmount = max(0, (float) $patientInsurance->used_amount - (float) $claim->claimed_amount);

            $patientInsurance->update([
                'used_amount' => $newUsedAmount,
            ]);
        }

        InsuranceClaim::where('invoice_id', $invoice->id)->delete();
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