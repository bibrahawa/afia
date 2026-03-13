<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PatientInsurance;
use App\Models\Transaction;

class InvoiceService
{
    public function __construct(
        private InsuranceCalculationService $insuranceCalculationService
    ) {
    }

    public function createOrUpdateInvoice(
        Transaction $transaction,
        array $calculation,
        array $items
    ): Invoice {
        $invoice = $transaction->invoice ?: new Invoice([
            'transaction_id' => $transaction->id
        ]);

        $firstInsurance = PatientInsurance::where('patient_id', $transaction->patient_id)
            ->where('status', 'active')
            ->with('insuranceCompany')
            ->first();

        $invoice->fill([
            'insurance_company_id' => $firstInsurance?->insurance_company_id,
            'patient_insurance_id' => $firstInsurance?->id,
            'total_amount' => $calculation['total_amount'],
            'patient_amount' => $calculation['patient_amount'],
            'insurance_amount' => $calculation['insurance_coverage'],
            'insurance_status' => $calculation['insurance_coverage'] > 0 ? 'pending' : null,
            'patient_amount_status' => 'pending',
        ]);

        $invoice->save();

        InvoiceItem::where('invoice_id', $invoice->id)->delete();

        $details = $calculation['details'] ?: $items;

        foreach ($details as $index => $detail) {
            $source = array_merge($items[$index] ?? [], $detail);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'coverage_type_type' => $source['acte_type'] ?? null,
                'coverage_type_id' => $source['acte_id'] ?? null,
                'description' => $source['item_description'] ?? $source['description'] ?? '',
                'unit_price' => (float) ($source['unit_price'] ?? 0),
                'quantity' => (int) ($source['quantity'] ?? 1),
                'total_amount' => (float) ($source['item_amount'] ?? $source['total'] ?? 0),
                'insurance_covered_amount' => (float) ($source['insurance_amount'] ?? 0),
                'patient_amount' => (float) ($source['patient_amount'] ?? 0),
                'coverage_percentage_applied' => !empty($source['insurances_applied'])
                    ? $this->insuranceCalculationService->getAverageCoveragePercentage($source['insurances_applied'])
                    : 0,
            ]);
        }

        return $invoice;
    }
}