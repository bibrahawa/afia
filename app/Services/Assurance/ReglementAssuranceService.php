<?php

namespace App\Services\Assurance;

use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceSettlement;
use App\Models\InsuranceSettlementItem;
use App\Models\Transaction;
use App\Services\PatientAccountService;
use App\Services\PaymentService;
use App\Services\TransactionStatusService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CHEMIN UNIQUE de règlement par un organisme payeur (lot 2c).
 *
 * Remplace les deux chemins parallèles qui existaient :
 *  - PaymentService::payInsuranceForCompany (paiements « remboursement » sans trace de règlement) ;
 *  - InsuranceSettlementService::settle (règlement facture par facture, sans débit du compte
 *    patient et limité à l'assureur « principal » de la facture).
 *
 * Un règlement = un virement / chèque reçu d'un organisme, imputé RÉCLAMATION
 * par réclamation :
 *  - la part payée crée un encaissement (paiement « remboursement ») et débite
 *    le compte de la pièce ;
 *  - l'écart éventuel (refus de l'assureur, remise négociée) est soldé sans
 *    encaissement et débite aussi le compte (la créance n'est plus due) ;
 *  - statuts de la réclamation, de la facture et de la transaction recalculés.
 */
class ReglementAssuranceService
{
    public function __construct(
        private PatientAccountService $comptes,
        private TransactionStatusService $statuts,
    ) {
    }

    /**
     * @param array<int, array{paye?: float|string|null, ecart?: float|string|null}> $imputations
     *        par id de réclamation ; vide = imputation automatique du montant reçu,
     *        des réclamations envoyées les plus anciennes aux plus récentes.
     * @param array{payment_method?: ?string, payment_reference?: ?string, payment_date?: ?string, notes?: ?string, period_start?: ?string, period_end?: ?string} $infos
     */
    public function regler(InsuranceCompany $organisme, float $montantRecu, array $imputations = [], array $infos = []): InsuranceSettlement
    {
        $montantRecu = round($montantRecu, 2);

        if ($montantRecu < 0) {
            throw new OperationAssuranceImpossible('Le montant reçu ne peut pas être négatif.');
        }

        return DB::transaction(function () use ($organisme, $montantRecu, $imputations, $infos) {
            $lignes = $imputations
                ? $this->imputationsSaisies($organisme, $imputations, $montantRecu)
                : $this->imputationsAutomatiques($organisme, $montantRecu);

            if ($lignes->isEmpty()) {
                throw new OperationAssuranceImpossible("Aucune réclamation ouverte à régler pour {$organisme->name}.");
            }

            $totalPaye = round($lignes->sum('paye'), 2);
            $totalEcart = round($lignes->sum('ecart'), 2);
            $totalDu = round($lignes->sum(fn ($l) => $l['reclamation']->resteDu()), 2);

            $reglement = InsuranceSettlement::create([
                'insurance_company_id' => $organisme->id,
                'period_start' => $infos['period_start'] ?? null,
                'period_end' => $infos['period_end'] ?? null,
                'gross_amount' => $totalDu,
                'discount_amount' => $totalEcart,
                'paid_amount' => $totalPaye,
                'net_amount' => round($totalPaye + $totalEcart, 2),
                'remaining_amount' => round($totalDu - $totalPaye - $totalEcart, 2),
                'payment_method' => ! empty($infos['payment_method']) ? strtoupper($infos['payment_method']) : null,
                'payment_reference' => $infos['payment_reference'] ?? null,
                'payment_date' => $infos['payment_date'] ?? today()->toDateString(),
                'status' => round($totalDu - $totalPaye - $totalEcart, 2) > 0 ? 'partial' : 'paid',
                'notes' => $infos['notes'] ?? null,
            ]);

            foreach ($lignes as $ligne) {
                $this->imputer($reglement, $ligne['reclamation'], $ligne['paye'], $ligne['ecart'], $infos);
            }

            return $reglement->fresh(['items']);
        });
    }

    /**
     * Caisse : règlement de la part assurance d'UNE pièce. Réparti sur ses
     * réclamations (payeur par payeur), un règlement par organisme.
     */
    public function reglerTransaction(Transaction $transaction, float $montant, ?string $source = null, ?string $description = null): Collection
    {
        return DB::transaction(function () use ($transaction, $montant, $source, $description) {
            $reste = round($montant, 2);
            $parOrganisme = [];

            $reclamations = InsuranceClaim::whereHas('invoice', fn ($q) => $q->where('transaction_id', $transaction->id))
                ->orderBy('id')->lockForUpdate()->get();

            foreach ($reclamations as $reclamation) {
                if ($reste < 0.01) {
                    break;
                }
                $du = $reclamation->resteDu();
                if ($du < 0.01 || $reclamation->status === 'rejected') {
                    continue;
                }
                $applique = min($reste, $du);
                $parOrganisme[$reclamation->insurance_company_id][$reclamation->id] = ['paye' => $applique, 'ecart' => 0];
                $reste = round($reste - $applique, 2);
            }

            if (! $parOrganisme) {
                throw new \InvalidArgumentException('La part assurance est déjà réglée.');
            }

            return collect($parOrganisme)->map(fn ($imputations, $organismeId) => $this->regler(
                InsuranceCompany::findOrFail($organismeId),
                round(array_sum(array_column($imputations, 'paye')), 2),
                $imputations,
                ['payment_method' => $source, 'notes' => $description]
            ))->values();
        });
    }

    /** Réclamations d'un organisme qui restent à solder, les plus anciennes d'abord (envoyées avant brouillons). */
    public function reclamationsOuvertes(InsuranceCompany $organisme): Collection
    {
        return InsuranceClaim::query()
            ->where('insurance_company_id', $organisme->id)
            // Les réclamations rejetées restent visibles tant que la part refusée n'est
            // ni transférée au patient ni passée en perte.
            ->where('status', '!=', 'paid')
            // Lot R2 — préfiltre sur le reste dû stocké : on ne charge plus toutes les
            // réclamations non réglées de l'organisme. Le calcul en direct ci-dessous reste
            // l'arbitre ; une réclamation jamais calculée (montants_calcules_le nul) passe.
            ->where(fn ($q) => $q->where('reste_du_calcule', '>=', 0.01)->orWhereNull('montants_calcules_le'))
            ->with(['invoice.transaction.patient', 'bordereau', 'patientInsurance'])
            ->orderByRaw("CASE WHEN status = 'draft' THEN 1 ELSE 0 END")
            ->orderBy('submission_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (InsuranceClaim $c) => $c->resteDu() >= 0.01)
            ->values();
    }

    private function imputationsAutomatiques(InsuranceCompany $organisme, float $montantRecu): Collection
    {
        $reste = $montantRecu;
        $lignes = collect();

        foreach ($this->reclamationsOuvertes($organisme) as $reclamation) {
            if ($reste < 0.01) {
                break;
            }
            // Si l'assureur a répondu, on ne paie automatiquement que l'accepté.
            $plafond = $reclamation->approved_amount !== null
                ? max(0.0, min($reclamation->resteDu(), (float) $reclamation->approved_amount - $reclamation->montantPaye()))
                : $reclamation->resteDu();
            if ($plafond < 0.01) {
                continue;
            }
            $paye = round(min($reste, $plafond), 2);
            $lignes->push(['reclamation' => $reclamation, 'paye' => $paye, 'ecart' => 0.0]);
            $reste = round($reste - $paye, 2);
        }

        if ($reste >= 0.01) {
            throw new OperationAssuranceImpossible(sprintf(
                'Le montant reçu dépasse de %s GNF les réclamations ouvertes de %s. Vérifiez le montant ou imputez-le manuellement.',
                number_format($reste, 0, ',', ' '),
                $organisme->name
            ));
        }

        return $lignes;
    }

    private function imputationsSaisies(InsuranceCompany $organisme, array $imputations, float $montantRecu): Collection
    {
        $lignes = collect();

        foreach ($imputations as $reclamationId => $valeurs) {
            $paye = round((float) ($valeurs['paye'] ?? 0), 2);
            $ecart = round((float) ($valeurs['ecart'] ?? 0), 2);

            if ($paye < 0.01 && $ecart < 0.01) {
                continue;
            }
            if ($paye < 0 || $ecart < 0) {
                throw new OperationAssuranceImpossible('Les montants imputés ne peuvent pas être négatifs.');
            }

            $reclamation = InsuranceClaim::whereKey($reclamationId)->lockForUpdate()->first();

            if (! $reclamation || (int) $reclamation->insurance_company_id !== (int) $organisme->id) {
                throw new OperationAssuranceImpossible('Une réclamation imputée n\'appartient pas à cet organisme.');
            }

            if ($paye + $ecart - $reclamation->resteDu() >= 0.01) {
                throw new OperationAssuranceImpossible(sprintf(
                    'Réclamation %s : %s GNF imputés pour un reste dû de %s GNF.',
                    $reclamation->claim_number,
                    number_format($paye + $ecart, 0, ',', ' '),
                    number_format($reclamation->resteDu(), 0, ',', ' ')
                ));
            }

            $lignes->push(['reclamation' => $reclamation, 'paye' => $paye, 'ecart' => $ecart]);
        }

        if (abs(round($lignes->sum('paye'), 2) - $montantRecu) >= 0.01) {
            throw new OperationAssuranceImpossible(sprintf(
                'Le total imputé (%s GNF) doit être égal au montant reçu (%s GNF).',
                number_format($lignes->sum('paye'), 0, ',', ' '),
                number_format($montantRecu, 0, ',', ' ')
            ));
        }

        return $lignes;
    }

    private function imputer(InsuranceSettlement $reglement, InsuranceClaim $reclamation, float $paye, float $ecart, array $infos): void
    {
        $invoice = $reclamation->invoice;
        $transaction = Transaction::whereKey($invoice->transaction_id)->lockForUpdate()->firstOrFail();
        $avant = $reclamation->resteDu();
        $paiement = null;

        if ($paye >= 0.01) {
            $paiement = app(PaymentService::class)->encaisserPartAssurance(
                $transaction,
                $paye,
                $infos['payment_method'] ?? null,
                trim('Règlement ' . $reglement->settlement_no . ' — ' . $reclamation->claim_number
                    . (! empty($infos['payment_reference']) ? ' — réf. ' . $infos['payment_reference'] : ''))
            );
        }

        InsuranceSettlementItem::create([
            'insurance_settlement_id' => $reglement->id,
            'invoice_id' => $invoice->id,
            'insurance_claim_id' => $reclamation->id,
            'paiement_id' => $paiement?->id,
            'invoice_amount' => $reclamation->montantDu(),
            'already_settled_amount' => $reclamation->montantRegle(),
            'remaining_before' => $avant,
            'applied_discount_amount' => $ecart,
            'applied_paid_amount' => $paye,
            'settled_amount' => round($paye + $ecart, 2),
            'remaining_after' => round($avant - $paye - $ecart, 2),
        ]);

        if ($ecart >= 0.01) {
            // La créance soldée sans encaissement n'est plus due : le compte suit.
            $this->comptes->ajusterPourTransaction($transaction, -$ecart);
        }

        $reclamation->forceFill(['payment_date' => $infos['payment_date'] ?? today()->toDateString()]);
        $reclamation->rafraichirStatut();
        $this->statuts->refresh($transaction);
    }

    /**
     * Ancienneté du reste dû par tranches de 30 jours : l'outil de relance.
     * Le compte part de la date d'envoi du bordereau, à défaut de la facture.
     */
    public function anciennete(\App\Models\InsuranceCompany $organisme): array
    {
        $tranches = ['0-30' => 0.0, '30-60' => 0.0, '60-90' => 0.0, '90+' => 0.0];

        foreach ($this->reclamationsOuvertes($organisme) as $reclamation) {
            $reste = $reclamation->resteDu();

            if ($reste < 1) {
                continue;
            }

            $reference = $reclamation->submission_date ?? $reclamation->created_at;
            $jours = $reference ? $reference->diffInDays(now()) : 0;

            $cle = match (true) {
                $jours <= 30 => '0-30',
                $jours <= 60 => '30-60',
                $jours <= 90 => '60-90',
                default => '90+',
            };

            $tranches[$cle] += $reste;
        }

        return $tranches;
    }
}
