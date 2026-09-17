<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Hospitalisation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillingService
{
    public function __construct(
        private BillingItemBuilderService $itemBuilder,
        private InsuranceCalculationService $insuranceCalculationService,
        private PatientAccountService $patientAccountService,
        private TransactionStatusService $transactionStatusService,
        private InvoiceService $invoiceService,
        private InsuranceConsumptionService $insuranceConsumptionService
    ) {
    }

    public function createFromConsultation(Consultation $consultation): Transaction
    {
        return DB::transaction(function () use ($consultation) {
            $consultation->loadMissing('patient');

            $payload = $this->itemBuilder->buildFromConsultation($consultation);
            $calculation = $this->insuranceCalculationService->calculateInsuranceCoverage(
                $consultation->patient_id,
                $payload['items']
            );

            $account = $this->patientAccountService->credit(
                $consultation->patient,
                (float) $calculation['total_amount']
            );

            $transaction = $consultation->transaction()->create([
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'patient_id' => $consultation->patient_id,
                'description' => $consultation->motif,
                'tax_amount' => 0,
                'discount' => 0,
                'sub_total' => $calculation['total_amount'],
                'total' => $calculation['total_amount'],
                'status' => 'pending',
            ]);

            $invoice = $this->invoiceService->createOrUpdateInvoice($transaction, $calculation, $payload['items']);

            $this->insuranceConsumptionService->applyConsumptionsAndClaims(
                $invoice,
                $calculation['insurances_used'] ?? [],
                $transaction->patient_id
            );

            $consultation->update(['est_facturee' => true]);

            $this->transactionStatusService->refresh($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    public function createFromHospitalisation(Hospitalisation $hospitalisation): Transaction
    {
        return DB::transaction(function () use ($hospitalisation) {
            $hospitalisation->loadMissing('patient', 'chambre');

            $payload = $this->itemBuilder->buildFromHospitalisation($hospitalisation);
            $calculation = $this->insuranceCalculationService->calculateInsuranceCoverage(
                $hospitalisation->patient_id,
                $payload['items']
            );

            $account = $this->patientAccountService->credit(
                $hospitalisation->patient,
                (float) $calculation['total_amount']
            );

            $transaction = $hospitalisation->transaction()->create([
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'patient_id' => $hospitalisation->patient_id,
                'description' => $hospitalisation->observation ?? 'Frais d’hospitalisation',
                'tax_amount' => 0,
                'discount' => 0,
                'sub_total' => $calculation['total_amount'],
                'total' => $calculation['total_amount'],
                'status' => 'pending',
            ]);

            $invoice = $this->invoiceService->createOrUpdateInvoice($transaction, $calculation, $payload['items']);

            $this->insuranceConsumptionService->applyConsumptionsAndClaims(
                $invoice,
                $calculation['insurances_used'] ?? [],
                $transaction->patient_id
            );

            $this->transactionStatusService->refresh($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    public function recalculate(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {

            $transaction->loadMissing('patient', 'invoice', 'invoice.items');

            if (!$transaction->invoice) {
                throw new InvalidArgumentException('Transaction sans facture.');
            }

            // rollback ancienne consommation assurance
            $this->insuranceConsumptionService->rollbackConsumption(
                $transaction->invoice
            );

            $payload = $this->itemBuilder->buildFromTransaction($transaction);

            $calculation = $this->insuranceCalculationService->calculateInsuranceCoverage(
                $transaction->patient_id,
                $payload['items']
            );

            $oldTotal = (float) $transaction->total;
            $newTotal = (float) $calculation['total_amount'];

            $diff = $newTotal - $oldTotal;

            if ($diff > 0) {
                $this->patientAccountService->credit(
                    $transaction->patient,
                    $diff
                );
            } elseif ($diff < 0) {
                $this->patientAccountService->debit(
                    $transaction->patient,
                    abs($diff)
                );
            }

            $transaction->update([
                'sub_total' => $newTotal,
                'total' => $newTotal,
            ]);

            $invoice = $this->invoiceService->createOrUpdateInvoice(
                $transaction,
                $calculation,
                $payload['items']
            );

            // appliquer la nouvelle consommation assurance
            $this->insuranceConsumptionService->applyConsumptionsAndClaims(
                $invoice,
                $calculation['insurances_used'] ?? [],
                $transaction->patient_id
            );

            $this->transactionStatusService->refresh($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
            
        });
    }

    public function applyDiscounts(Transaction $transaction, array $discounts): Transaction
    {
        return DB::transaction(function () use ($transaction, $discounts) {
            $transaction->loadMissing('invoice.items', 'patient');

            $invoice = $transaction->invoice;
            if (!$invoice) {
                throw new InvalidArgumentException('Aucune facture liée à cette transaction.');
            }

            $typeMap = [
                'Service' => 'App\\Models\\Service',
                'Test' => 'App\\Models\\Test',
                'Medicament' => 'App\\Models\\Medicament',
                'Package' => 'App\\Models\\Package',
                'Hospitalisation' => 'App\\Models\\Hospitalisation',
                'Chambre' => 'App\\Models\\Chambre',
                // AJOUT MODULE LABORATOIRE : remises ligne par ligne sur les examens
                'LaboExamen' => \App\Models\Labo\LaboExamen::class,
            ];

            $subTotal = 0;
            $insuranceTotal = 0;
            $patientTotal = 0;
            $discountTotal = 0;

            foreach ($discounts as $type => $items) {
                $modelClass = $typeMap[$type] ?? null;
                if (!$modelClass) {
                    continue;
                }

                foreach ($items as $id => $discount) {
                    $invoiceItem = $invoice->items
                        ->where('coverage_type_type', $modelClass)
                        ->where('coverage_type_id', (int) $id)
                        ->first();

                    if (!$invoiceItem) {
                        continue;
                    }

                    $invoiceItem->discount = (float) $discount;
                    $invoiceItem->save();
                    $invoiceItem->reCalculerApresReduction();

                    $discountTotal += (float) $invoiceItem->discount;
                    $subTotal += (float) $invoiceItem->total_amount;
                    $insuranceTotal += (float) $invoiceItem->insurance_covered_amount;
                    $patientTotal += (float) $invoiceItem->patient_amount;
                }
            }

            $invoice->update([
                'insurance_amount' => $insuranceTotal,
                'patient_amount' => $patientTotal,
                'total_amount' => $subTotal,
            ]);

            $transaction->update([
                'sub_total' => $subTotal + $discountTotal,
                'discount' => $discountTotal,
                'total' => $subTotal,
            ]);

            if ($discountTotal > 0) {
                $this->patientAccountService->debit($transaction->patient, $discountTotal);
            }

            $this->transactionStatusService->refresh($transaction);

            return $transaction->fresh(['invoice.items']);
        });
    }
}