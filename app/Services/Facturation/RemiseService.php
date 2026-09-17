<?php

namespace App\Services\Facturation;

use App\Exceptions\Facturation\OperationFacturationImpossible;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Services\InvoiceService;
use App\Services\TransactionStatusService;
use App\Support\Facturation\TypesFacturables;
use Illuminate\Support\Facades\DB;

/**
 * Remises ligne par ligne sur une facture.
 *
 * Remplace BillingService::applyDiscounts(), qui :
 *  - recalculait le total de la facture en ne sommant QUE les lignes remisées
 *    (les autres lignes disparaissaient du total) ;
 *  - re-débitait le compte et re-soustrayait la remise à chaque appel ;
 *  - pouvait rendre la part patient négative.
 *
 * Règles : la remise saisie est le montant FINAL de la ligne (pas un ajout),
 * elle ne réduit que la part patient, et elle ne peut pas dépasser la part
 * patient de la ligne. Une remise à 0 retire la remise existante.
 */
class RemiseService
{
    public function __construct(
        private InvoiceService $factures,
        private TransactionStatusService $statuts,
        private FigementFacture $figement,
    ) {
    }

    /**
     * @param array<string, array<int|string, float|int|string|null>> $remises
     *        ['Service' => [12 => 20000], 'Medicament' => [5 => 0], ...]
     *        Type : libellé historique du formulaire, alias ou nom de classe.
     */
    public function appliquer(Transaction $transaction, array $remises): Transaction
    {
        return DB::transaction(function () use ($transaction, $remises) {
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->with('invoice')->firstOrFail();
            $invoice = $transaction->invoice;

            if (! $invoice) {
                throw new OperationFacturationImpossible('Aucune facture liée à cette pièce : remise impossible.');
            }

            $lignes = InvoiceItem::where('invoice_id', $invoice->id)->get();

            foreach ($remises as $type => $parActe) {
                $classe = TypesFacturables::depuisSaisie((string) $type);

                if (! $classe || ! is_array($parActe)) {
                    continue;
                }

                foreach ($parActe as $acteId => $montant) {
                    if ($montant === null || $montant === '') {
                        continue;
                    }

                    $ligne = $lignes->first(fn (InvoiceItem $l) => TypesFacturables::est($l->coverage_type_type, $classe)
                        && (int) $l->coverage_type_id === (int) $acteId);

                    if (! $ligne) {
                        continue;
                    }

                    $montant = round((float) $montant, 2);

                    if ($montant < 0) {
                        throw new OperationFacturationImpossible('Une remise ne peut pas être négative.');
                    }

                    if ($montant - $ligne->partPatientAvantRemise() >= 0.01) {
                        throw new OperationFacturationImpossible(sprintf(
                            'Remise trop élevée sur « %s » : %s GNF maximum (part patient de la ligne).',
                            $ligne->description,
                            number_format($ligne->partPatientAvantRemise(), 0, ',', ' ')
                        ));
                    }

                    $ligne->appliquerRemise($montant);
                }
            }

            $this->factures->synchroniserTotaux($invoice, $transaction);
            $this->figement->verifierEncaissementsCouverts($transaction);
            $this->statuts->refresh($transaction);

            return $transaction->fresh(['invoice.items', 'paiements']);
        });
    }
}
