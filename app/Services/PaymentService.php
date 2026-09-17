<?php

namespace App\Services;

use App\Models\InsuranceCompany;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{


    public function __construct(
        private PatientAccountService $patientAccountService,
        private TransactionStatusService $transactionStatusService
    ) {
    }

    public function payPatientTransaction(
        Transaction $transaction,
        float $amount,
        string $paymentMethod,
        ?string $description = null
    ): Transaction {
        return DB::transaction(function () use ($transaction, $amount, $paymentMethod, $description) {
            if ($amount <= 0) {
                throw new InvalidArgumentException('Le montant doit être supérieur à zéro.');
            }

            $transaction->loadMissing('patient.account', 'invoice');

            $invoice = $transaction->invoice;
            $patientDue = $this->resteDuPatient($transaction);

            if ($patientDue <= 0) {
                // CORRIGÉ : continuait et créait un paiement de 0 GNF.
                throw new InvalidArgumentException('La part patient est déjà réglée.');
            }

            $amountToApply = min($amount, $patientDue);

            $this->createPayment(
                transaction: $transaction,
                patientId: $transaction->patient_id,
                payerType: 'PATIENT',
                paymentMethod: strtoupper($paymentMethod),
                montant: $amountToApply,
                description: $description ?? 'Paiement part patient'
            );

            $transaction->montant_payer += $amountToApply;
            $transaction->save();

            // Débite le compte de CETTE transaction, de façon atomique.
            $this->patientAccountService->debiterPourTransaction($transaction, $amountToApply);

            $this->refreshStatuses($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    public function payInsuranceTransaction(
        Transaction $transaction,
        float $amount,
        ?string $description = null
    ): Transaction {
        return DB::transaction(function () use ($transaction, $amount, $description) {
            if ($amount <= 0) {
                throw new InvalidArgumentException('Le montant doit être supérieur à zéro.');
            }

            $transaction->loadMissing('patient.account', 'invoice');

            $invoice = $transaction->invoice;
            if (!$invoice) {
                throw new InvalidArgumentException('Aucune facture liée à cette transaction.');
            }

            $alreadyPaid = $this->getPaidAmountForTransaction($transaction, 'INSURANCE');
            $insuranceDue = max(0, (float) $invoice->insurance_amount - $alreadyPaid);

            if ($insuranceDue <= 0) {
                throw new InvalidArgumentException('La part assurance est déjà réglée.');
            }

            $amountToApply = min($amount, $insuranceDue);

            $this->createPayment(
                transaction: $transaction,
                patientId: $transaction->patient_id,
                payerType: 'INSURANCE',
                paymentMethod: null,
                montant: $amountToApply,
                description: $description ?? 'Paiement assurance'
            );

            $transaction->montant_payer += $amountToApply;
            $transaction->save();

            // Débite le compte de CETTE transaction, de façon atomique.
            $this->patientAccountService->debiterPourTransaction($transaction, $amountToApply);

            $this->refreshStatuses($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    public function payPatientForPatient(
        Patient $patient,
        float $amount,
        string $paymentMethod,
        ?string $description = null
    ): array {
        return DB::transaction(function () use ($patient, $amount, $paymentMethod, $description) {
            if ($amount <= 0) {
                throw new InvalidArgumentException('Le montant doit être supérieur à zéro.');
            }

            $remaining = $amount;

            $transactions = $patient->transactions()
                ->with(['invoice', 'patient.account'])
                ->whereIn('status', ['pending', 'partial', 'approved'])
                ->orderBy('created_at')
                ->get();

            $updatedTransactions = [];

            foreach ($transactions as $transaction) {
                if ($remaining <= 0) {
                    break;
                }

                // CORRIGÉ : les pièces anciennes sans facture étaient ignorées (paiement non appliqué).
                $patientDue = $this->resteDuPatient($transaction);

                if ($patientDue <= 0) {
                    continue;
                }

                $amountToApply = min($remaining, $patientDue);

                $this->createPayment(
                    transaction: $transaction,
                    patientId: $patient->id,
                    payerType: 'PATIENT',
                    paymentMethod: strtoupper($paymentMethod),
                    montant: $amountToApply,
                    description: $description ?? 'Paiement part patient'
                );

                $transaction->montant_payer += $amountToApply;
                $transaction->save();

                // Débite le compte de CETTE transaction, de façon atomique.
                $this->patientAccountService->debiterPourTransaction($transaction, $amountToApply);

                $this->refreshStatuses($transaction);

                $updatedTransactions[] = $transaction->fresh(['invoice', 'paiements']);
                $remaining -= $amountToApply;
            }

            return [
                'paid_amount' => $amount - $remaining,
                'remaining_amount' => $remaining,
                'transactions' => $updatedTransactions,
            ];
        });
    }

    public function payInsuranceForCompany(
        InsuranceCompany $company,
        float $amount,
        ?string $description = null
    ): array {
        return DB::transaction(function () use ($company, $amount, $description) {
            if ($amount <= 0) {
                throw new InvalidArgumentException('Le montant doit être supérieur à zéro.');
            }

            $remaining = $amount;

            $transactions = Transaction::query()
                ->whereHas('invoice', function ($q) use ($company) {
                    $q->where('insurance_company_id', $company->id)
                        ->whereIn('insurance_status', ['pending', 'approved'])
                        ->where('patient_amount_status', 'paid');
                })
                ->with(['invoice', 'patient.account'])
                ->orderBy('created_at')
                ->get();

            $updatedTransactions = [];

            foreach ($transactions as $transaction) {
                if ($remaining <= 0) {
                    break;
                }

                $invoice = $transaction->invoice;
                if (!$invoice) {
                    continue;
                }

                $alreadyPaid = $this->getPaidAmountForTransaction($transaction, 'INSURANCE');
                $insuranceDue = max(0, (float) $invoice->insurance_amount - $alreadyPaid);

                if ($insuranceDue <= 0) {
                    continue;
                }

                $amountToApply = min($remaining, $insuranceDue);

                $this->createPayment(
                    transaction: $transaction,
                    patientId: $transaction->patient_id,
                    payerType: 'INSURANCE',
                    paymentMethod: null,
                    montant: $amountToApply,
                    description: $description ?? 'Paiement assurance'
                );

                $transaction->montant_payer += $amountToApply;
                $transaction->save();

                // Débite le compte de CETTE transaction, de façon atomique.
                $this->patientAccountService->debiterPourTransaction($transaction, $amountToApply);

                $this->refreshStatuses($transaction);

                $updatedTransactions[] = $transaction->fresh(['invoice', 'paiements']);
                $remaining -= $amountToApply;
            }

            return [
                'paid_amount' => $amount - $remaining,
                'remaining_amount' => $remaining,
                'transactions' => $updatedTransactions,
            ];
        });
    }

    private function createPayment(
        Transaction $transaction,
        int $patientId,
        string $payerType,
        ?string $paymentMethod,
        float $montant,
        ?string $description
    ): Paiement {
        return Paiement::create([
            'user_id' => auth()->id(),
            'patient_id' => $patientId,
            'transaction_id' => $transaction->id,
            'source' => $paymentMethod,
            'type' => strtolower($payerType) === 'insurance' ? 'remboursement' : 'paiement',
            'description' => $description,
            'montant' => $montant,
        ]);
    }

    private function getPaidAmountForTransaction(Transaction $transaction, string $payerType): float
    {
        if (strtoupper($payerType) === 'PATIENT') {
            return (float) Paiement::where('transaction_id', $transaction->id)
                ->where('type', 'paiement')
                ->sum('montant');
        }

        return (float) Paiement::where('transaction_id', $transaction->id)
            ->where('type', 'remboursement')
            ->sum('montant');
    }

    /**
     * Part patient restant due. Pièce sans facture (anciennes consultations) :
     * pas de part assurance connue, tout le total est à la charge du patient.
     */
    private function resteDuPatient(Transaction $transaction): float
    {
        $partPatient = $transaction->invoice ? (float) $transaction->invoice->patient_amount : (float) $transaction->total;

        return max(0, round($partPatient - $this->getPaidAmountForTransaction($transaction, 'PATIENT'), 2));
    }

    private function refreshStatuses(Transaction $transaction): void
    {
        $this->transactionStatusService->refresh($transaction);
    }
}