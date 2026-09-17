<?php

namespace App\Services\Facturation;

use App\Exceptions\Facturation\OperationFacturationImpossible;
use App\Models\ActivityLog;
use App\Models\Paiement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PatientAccountService;
use App\Services\TransactionStatusService;
use Illuminate\Support\Facades\DB;

/**
 * Annule un encaissement erroné sans effacer sa trace.
 *
 * C'est la porte de sortie de la règle de figement (FigementFacture) : une
 * facture déjà encaissée ne peut pas descendre sous ce qui a été payé ; pour
 * corriger, on annule le paiement fautif, on corrige, puis on ré-encaisse.
 *
 * Effets : paiement marqué annulé (qui, quand, pourquoi), compte du patient
 * re-crédité du montant, statuts recalculés, entrée au journal d'activité.
 */
class AnnulationPaiementService
{
    public function __construct(
        private PatientAccountService $comptes,
        private TransactionStatusService $statuts,
    ) {
    }

    public function annuler(Paiement $paiement, string $motif, ?User $auteur = null): Paiement
    {
        $motif = trim($motif);

        if (mb_strlen($motif) < 5) {
            throw new OperationFacturationImpossible("Indiquez le motif de l'annulation (5 caractères minimum).");
        }

        return DB::transaction(function () use ($paiement, $motif, $auteur) {
            // Même verrou que l'encaissement : pas d'annulation pendant qu'une
            // autre caisse encaisse la même facture.
            $transaction = Transaction::whereKey($paiement->transaction_id)->lockForUpdate()->first();

            $paiement = Paiement::avecAnnules()->whereKey($paiement->id)->lockForUpdate()->firstOrFail();

            if ($paiement->estAnnule()) {
                throw new OperationFacturationImpossible('Ce paiement est déjà annulé.');
            }

            $paiement->forceFill([
                'annule_le' => now(),
                'annule_par' => $auteur?->id,
                'motif_annulation' => mb_substr($motif, 0, 255),
            ])->save();

            if ($transaction) {
                // L'argent n'est plus considéré comme reçu : le patient le redoit.
                $this->comptes->ajusterPourTransaction($transaction, (float) $paiement->montant);
                $this->statuts->refresh($transaction);
            }

            // Encaissement issu d'un règlement assurance : la réclamation redevient due.
            \App\Models\InsuranceSettlementItem::withoutGlobalScope('etablissement')
                ->where('paiement_id', $paiement->id)
                ->with('reclamation')
                ->get()
                ->each(fn ($item) => $item->reclamation?->rafraichirStatut());

            ActivityLog::create([
                'etablissement_id' => $paiement->etablissement_id,
                'causer_type' => $auteur ? User::class : null,
                'causer_id' => $auteur?->id,
                'subject_type' => Paiement::class,
                'subject_id' => $paiement->id,
                'action' => 'paiement.annule',
                'description' => "Paiement {$paiement->paiement_no} annulé : {$motif}",
                'proprietes' => [
                    'montant' => (float) $paiement->montant,
                    'type' => $paiement->type,
                    'transaction_id' => $paiement->transaction_id,
                ],
            ]);

            return $paiement;
        });
    }
}
