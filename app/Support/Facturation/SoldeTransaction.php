<?php

namespace App\Support\Facturation;

use App\Models\Paiement;
use App\Models\Transaction;

/**
 * Où en est le règlement d'une pièce, part patient et part assurance
 * SÉPARÉMENT.
 *
 * POURQUOI : `transactions.montant_payer` additionne ce qu'ont versé le
 * patient ET l'assurance. Les reçus s'en servaient comme « payé par le
 * patient » : dès que l'assureur réglait, le reste à payer du patient
 * devenait faux. Cette classe est la SEULE règle de calcul ; PaymentService,
 * TransactionStatusService, les reçus et les contrôleurs l'utilisent tous.
 *
 * Sources :
 *  - part patient due     : invoices.patient_amount (ou transactions.total pour
 *                           les anciennes pièces sans facture) ;
 *  - part assurance due   : invoices.insurance_amount ;
 *  - payé par le patient  : paiements non annulés de type « paiement » ;
 *  - réglé par l'assureur : paiements non annulés de type « remboursement »
 *                           + écarts soldés lors des règlements (ReglementsSansPaiement).
 */
final class SoldeTransaction
{
    private function __construct(
        public readonly float $partPatient,
        public readonly float $partAssurance,
        public readonly float $payePatient,
        public readonly float $regleAssurance,
    ) {
    }

    public static function pour(Transaction $transaction): self
    {
        $invoice = $transaction->relationLoaded('invoice') ? $transaction->invoice : $transaction->invoice()->first();

        $partPatient = $invoice ? (float) $invoice->patient_amount : (float) $transaction->total;
        $partAssurance = $invoice ? (float) $invoice->insurance_amount : 0.0;

        $paiements = Paiement::withoutGlobalScope('etablissement')
            ->where('transaction_id', $transaction->id)
            ->selectRaw('type, SUM(montant) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        // Règlements assurance soldés SANS ligne de paiement : écarts (refus, remise
        // négociée) et parts payées des règlements antérieurs au lot 2c. Les parts
        // payées depuis le lot 2c ont leur paiement « remboursement » (compté ci-dessus).
        $bordereaux = $invoice ? ReglementsSansPaiement::pourFactures([$invoice->id]) : 0.0;

        return new self(
            partPatient: round($partPatient, 2),
            partAssurance: round($partAssurance, 2),
            payePatient: round((float) ($paiements[Paiement::TYPE_PATIENT] ?? 0), 2),
            regleAssurance: round((float) ($paiements[Paiement::TYPE_ASSURANCE] ?? 0) + $bordereaux, 2),
        );
    }

    public function resteDuPatient(): float
    {
        return max(0.0, round($this->partPatient - $this->payePatient, 2));
    }

    public function resteDuAssurance(): float
    {
        return max(0.0, round($this->partAssurance - $this->regleAssurance, 2));
    }

    public function tropPercuPatient(): float
    {
        return max(0.0, round($this->payePatient - $this->partPatient, 2));
    }

    public function patientSolde(): bool
    {
        return $this->resteDuPatient() < 0.01;
    }

    public function assuranceSoldee(): bool
    {
        return $this->resteDuAssurance() < 0.01;
    }

    public function totalEncaisse(): float
    {
        return round($this->payePatient + $this->regleAssurance, 2);
    }

    /** Statut de la part patient, au format de la page reçu. */
    public function statutPatient(): string
    {
        return match (true) {
            $this->patientSolde() => 'paid',
            $this->payePatient > 0 => 'partial',
            default => 'unpaid',
        };
    }
}
