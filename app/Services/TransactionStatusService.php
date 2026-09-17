<?php

namespace App\Services;

use App\Models\Transaction;
use App\Support\Facturation\SoldeTransaction;

/**
 * Statuts d'une pièce, recalculés à partir de SoldeTransaction (règle unique).
 *
 * transactions.status
 *   paid     : part patient ET part assurance réglées
 *   approved : part patient réglée, assurance en attente (valeur historique)
 *   partial  : quelque chose a été encaissé, la part patient n'est pas soldée
 *   pending  : rien encaissé
 *
 * invoices.insurance_status : null (pas de part assurance) / pending / partial / paid
 * invoices.patient_amount_status : pending / paid
 *
 * RÉVISION lot 1 :
 *  - les bordereaux de règlement assurance comptent (avant, ce service
 *    remettait « pending » juste après un règlement par bordereau) ;
 *  - les paiements annulés ne comptent plus ;
 *  - montant_payer est resynchronisé (patient + assurance), pour les écrans
 *    qui l'affichent encore.
 */
class TransactionStatusService
{
    public function refresh(Transaction $transaction): Transaction
    {
        $transaction->loadMissing('invoice');
        $invoice = $transaction->invoice;
        $solde = SoldeTransaction::pour($transaction);

        if ($invoice) {
            $invoice->patient_amount_status = $solde->patientSolde() ? 'paid' : 'pending';

            $invoice->insurance_status = match (true) {
                $solde->partAssurance <= 0 => null,
                $solde->assuranceSoldee() => 'paid',
                $solde->regleAssurance > 0 => 'partial',
                // Une réclamation déjà soumise / approuvée garde son statut.
                in_array($invoice->insurance_status, ['submitted', 'approved', 'rejected'], true) => $invoice->insurance_status,
                default => 'pending',
            };

            $invoice->save();
        }

        $transaction->status = match (true) {
            $solde->patientSolde() && $solde->assuranceSoldee() => 'paid',
            $solde->patientSolde() => 'approved',
            $solde->totalEncaisse() > 0 => 'partial',
            default => 'pending',
        };
        $transaction->montant_payer = $solde->totalEncaisse();
        $transaction->save();

        return $transaction->fresh(['invoice']);
    }
}
