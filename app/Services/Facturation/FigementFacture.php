<?php

namespace App\Services\Facturation;

use App\Exceptions\Facturation\OperationFacturationImpossible;
use App\Models\InsuranceClaim;
use App\Models\InsuranceSettlementItem;
use App\Models\Transaction;
use App\Support\Facturation\SoldeTransaction;

/**
 * Règles de figement d'une facture — ce qui a été encaissé ou déclaré à
 * l'assureur ne peut plus être contredit par une modification.
 *
 * Ce qui RESTE possible après un encaissement : ajouter des actes (examens,
 * médicaments prescrits en cours de consultation), changer la durée d'une
 * hospitalisation, accorder une remise… tant que la facture ne descend pas
 * sous ce qui a déjà été payé.
 *
 * Ce qui est REFUSÉ, avec un message qui dit quoi faire :
 *  1. Faire passer la part patient sous ce que le patient a déjà versé
 *     (avant : trop-perçu silencieux, jamais rendu).
 *  2. Faire passer la part assurance sous ce que l'assureur a déjà réglé.
 *  3. Recalculer une facture dont la réclamation est déjà soumise à
 *     l'assureur ou incluse dans un bordereau de règlement (avant : la
 *     réclamation était supprimée puis recréée, l'assureur avait une autre
 *     version que la clinique).
 *
 * Correction d'une erreur : annuler le paiement concerné
 * (AnnulationPaiementService), corriger, ré-encaisser.
 */
class FigementFacture
{
    /** À appeler AVANT de régénérer la facture (recalcul des actes / de l'assurance). */
    public function verifierRegenerable(Transaction $transaction): void
    {
        $invoice = $transaction->invoice()->first();

        if (! $invoice) {
            return;
        }

        $declaree = InsuranceClaim::withoutGlobalScope('etablissement')
            ->where('invoice_id', $invoice->id)
            ->where('status', '!=', 'draft')
            ->exists();

        if ($declaree) {
            throw new OperationFacturationImpossible(
                "La réclamation de cette facture a déjà été transmise à l'assureur : les actes et la prise en charge ne peuvent plus être modifiés."
            );
        }

        $reglee = InsuranceSettlementItem::withoutGlobalScope('etablissement')->where('invoice_id', $invoice->id)->exists();

        if ($reglee) {
            throw new OperationFacturationImpossible(
                "Cette facture figure déjà dans un bordereau de règlement de l'assureur : elle ne peut plus être modifiée."
            );
        }
    }

    /** À appeler APRÈS modification, dans la même transaction SQL (une exception annule tout). */
    public function verifierEncaissementsCouverts(Transaction $transaction): void
    {
        $solde = SoldeTransaction::pour($transaction->fresh('invoice'));

        if ($solde->payePatient - $solde->partPatient >= 0.01) {
            throw new OperationFacturationImpossible(sprintf(
                "Modification impossible : la part patient passerait à %s GNF alors que le patient a déjà versé %s GNF. "
                . "Annulez d'abord le paiement concerné, puis ré-encaissez le bon montant.",
                $this->gnf($solde->partPatient),
                $this->gnf($solde->payePatient)
            ));
        }

        if ($solde->regleAssurance - $solde->partAssurance >= 0.01) {
            throw new OperationFacturationImpossible(sprintf(
                "Modification impossible : la part assurance passerait à %s GNF alors que l'assureur a déjà réglé %s GNF.",
                $this->gnf($solde->partAssurance),
                $this->gnf($solde->regleAssurance)
            ));
        }
    }

    private function gnf(float $montant): string
    {
        return number_format($montant, 0, ',', ' ');
    }
}
