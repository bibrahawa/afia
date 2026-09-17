<?php

namespace App\Services\Assurance;

use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Models\Assurance\ReclamationLigne;
use App\Models\InsuranceClaim;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Services\Facturation\FigementFacture;
use App\Services\InvoiceService;
use App\Services\TransactionStatusService;
use Illuminate\Support\Facades\DB;

/**
 * Cycle d'une réclamation : détail par acte, réponse de l'assureur (montant
 * accepté ligne par ligne, motif de rejet), et sort de l'écart — transféré
 * au patient (ici) ou passé en perte lors du règlement (ReglementAssuranceService).
 */
class ReclamationService
{
    public function __construct(
        private InvoiceService $factures,
        private TransactionStatusService $statuts,
        private FigementFacture $figement,
    ) {
    }

    /**
     * Détail par acte, à partir de la répartition calculée par le moteur et
     * enregistrée sur chaque ligne de facture.
     */
    public function creerLignes(InsuranceClaim $reclamation): void
    {
        $lignesFacture = InvoiceItem::where('invoice_id', $reclamation->invoice_id)->get();
        $total = 0.0;

        foreach ($lignesFacture as $ligne) {
            foreach ((array) $ligne->repartition_assurance as $part) {
                if ((int) ($part['insurance_id'] ?? 0) !== (int) $reclamation->patient_insurance_id || (float) ($part['montant'] ?? 0) <= 0) {
                    continue;
                }

                ReclamationLigne::create([
                    'insurance_claim_id' => $reclamation->id,
                    'invoice_item_id' => $ligne->id,
                    'description' => $ligne->description,
                    'famille_acte' => $ligne->famille_acte,
                    'quantite' => (int) $ligne->quantity,
                    'montant_acte' => $ligne->montantBrut(),
                    'taux' => $part['taux'] ?? null,
                    'montant_reclame' => (float) $part['montant'],
                ]);
                $total += (float) $part['montant'];
            }
        }

        // Sécurité : sans répartition exploitable, une ligne globale.
        if (abs($total - (float) $reclamation->claimed_amount) >= 0.01) {
            $reclamation->lignes()->delete();
            ReclamationLigne::create([
                'insurance_claim_id' => $reclamation->id,
                'description' => 'Prise en charge de la facture',
                'montant_acte' => (float) $reclamation->claimed_amount,
                'montant_reclame' => (float) $reclamation->claimed_amount,
            ]);
        }
    }

    /**
     * @param array<int, array{montant_accepte: float|string|null, motif_rejet?: ?string}> $reponses par id de ligne
     */
    public function enregistrerReponse(InsuranceClaim $reclamation, array $reponses, ?string $commentaire = null): InsuranceClaim
    {
        if ($reclamation->status === 'draft') {
            throw new OperationAssuranceImpossible('Cette réclamation n\'a pas encore été envoyée à l\'assureur.');
        }

        if ($reclamation->settlementItems()->exists() || (float) $reclamation->montant_transfere_patient > 0) {
            throw new OperationAssuranceImpossible('Un règlement ou un transfert au patient a déjà été enregistré : la réponse de l\'assureur ne peut plus être modifiée.');
        }

        return DB::transaction(function () use ($reclamation, $reponses, $commentaire) {
            $accepte = 0.0;

            foreach ($reclamation->lignes as $ligne) {
                $saisie = $reponses[$ligne->id] ?? null;
                $montant = ($saisie['montant_accepte'] ?? '') === '' || $saisie === null
                    ? (float) $ligne->montant_reclame
                    : round((float) $saisie['montant_accepte'], 2);

                if ($montant < 0 || $montant - (float) $ligne->montant_reclame >= 0.01) {
                    throw new OperationAssuranceImpossible("« {$ligne->description} » : le montant accepté doit être compris entre 0 et le montant réclamé.");
                }

                $motif = trim((string) ($saisie['motif_rejet'] ?? ''));
                if ($montant < (float) $ligne->montant_reclame && $motif === '') {
                    throw new OperationAssuranceImpossible("« {$ligne->description} » : indiquez le motif du rejet communiqué par l'assureur.");
                }

                $ligne->update(['montant_accepte' => $montant, 'motif_rejet' => $motif !== '' ? $motif : null]);
                $accepte += $montant;
            }

            $reclamation->forceFill([
                'approved_amount' => round($accepte, 2),
                'approval_date' => today(),
                'rejection_reason' => $commentaire,
            ]);
            $reclamation->rafraichirStatut();

            return $reclamation->fresh(['lignes']);
        });
    }

    /**
     * Remet à la charge du patient la part que l'assureur refuse.
     * Le total de la facture ne change pas : la part assurance baisse, la part
     * patient augmente d'autant, ligne par ligne.
     */
    public function transfererEcartAuPatient(InsuranceClaim $reclamation): InsuranceClaim
    {
        if ($reclamation->approved_amount === null) {
            throw new OperationAssuranceImpossible('Enregistrez d\'abord la réponse de l\'assureur.');
        }

        if ((float) $reclamation->montant_transfere_patient > 0) {
            throw new OperationAssuranceImpossible('L\'écart de cette réclamation a déjà été transféré au patient.');
        }

        $ecart = $reclamation->ecartEnAttente();
        if ($ecart < 0.01) {
            throw new OperationAssuranceImpossible('Aucun écart à transférer : l\'assureur a accepté la totalité, ou l\'écart est déjà soldé.');
        }

        $lignes = $reclamation->lignes()->get();
        if ($lignes->contains(fn (ReclamationLigne $l) => $l->ecart() > 0 && ! $l->invoice_item_id)) {
            throw new OperationAssuranceImpossible('Réclamation antérieure au détail par acte : l\'écart ne peut pas être réparti sur les lignes de la facture.');
        }

        if (abs($lignes->sum(fn (ReclamationLigne $l) => $l->ecart()) - $ecart) >= 0.01) {
            throw new OperationAssuranceImpossible('Une partie de l\'écart a déjà été soldée lors d\'un règlement : le transfert au patient n\'est plus possible.');
        }

        return DB::transaction(function () use ($reclamation, $lignes, $ecart) {
            $invoice = $reclamation->invoice;
            $transaction = Transaction::whereKey($invoice->transaction_id)->lockForUpdate()->firstOrFail();

            foreach ($lignes as $ligne) {
                $diff = $ligne->ecart();
                if ($diff < 0.01) {
                    continue;
                }

                $item = InvoiceItem::findOrFail($ligne->invoice_item_id);
                $repartition = collect((array) $item->repartition_assurance)->map(function ($part) use ($reclamation, $diff) {
                    if ((int) ($part['insurance_id'] ?? 0) === (int) $reclamation->patient_insurance_id) {
                        $part['montant'] = round((float) $part['montant'] - $diff, 2);
                        $part['motif'] = 'refusé par l\'assureur, à la charge du patient';
                    }

                    return $part;
                })->all();

                $item->forceFill([
                    'insurance_covered_amount' => round((float) $item->insurance_covered_amount - $diff, 2),
                    'patient_amount' => round((float) $item->patient_amount + $diff, 2),
                    'repartition_assurance' => $repartition,
                ])->save();
            }

            $this->factures->synchroniserTotaux($invoice->fresh(), $transaction);
            $this->figement->verifierEncaissementsCouverts($transaction);

            $reclamation->forceFill(['montant_transfere_patient' => $ecart]);
            $reclamation->rafraichirStatut();
            $this->statuts->refresh($transaction);

            return $reclamation->fresh();
        });
    }
}
