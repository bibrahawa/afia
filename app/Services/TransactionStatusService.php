<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Transaction;

class TransactionStatusService
{
    public function refresh(Transaction $transaction): Transaction
    {
        $transaction->loadMissing('invoice');

        $invoice = $transaction->invoice;

        if (!$invoice) {
            $transaction->status = 'pending';
            $transaction->save();

            return $transaction->fresh(['invoice']);
        }

        $patientPaid = (float) Paiement::where('transaction_id', $transaction->id)
            ->where('type', 'paiement')
            ->sum('montant');

        $insurancePaid = (float) Paiement::where('transaction_id', $transaction->id)
            ->where('type', 'remboursement')
            ->sum('montant');

        $patientDue = (float) $invoice->patient_amount;
        $insuranceDue = (float) $invoice->insurance_amount;

        // Statut patient
        if ($patientDue <= 0) {
            $invoice->patient_amount_status = 'paid';
        } else {
            $invoice->patient_amount_status = $patientPaid >= $patientDue ? 'paid' : 'pending';
        }

        // Statut assurance
        if ($insuranceDue <= 0) {
            $invoice->insurance_status = null;
        } elseif ($insurancePaid >= $insuranceDue) {
            $invoice->insurance_status = 'paid';
        } elseif ($insurancePaid > 0) {
            $invoice->insurance_status = 'approved';
        } else {
            $invoice->insurance_status = 'pending';
        }

        $invoice->save();

        $patientOk = $patientDue <= 0 || $patientPaid >= $patientDue;
        $insuranceOk = $insuranceDue <= 0 || $insurancePaid >= $insuranceDue;

        if ($patientOk && $insuranceOk) {
            $transaction->status = 'paid';
        } elseif ($patientOk && !$insuranceOk) {
            $transaction->status = 'approved';
        } elseif ($patientPaid > 0 || $insurancePaid > 0) {
            $transaction->status = 'partial';
        } else {
            $transaction->status = 'pending';
        }

        $transaction->save();

        return $transaction->fresh(['invoice']);
    }
}