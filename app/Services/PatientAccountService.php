<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Transaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Support\Facturation\TypesFacturables;
use InvalidArgumentException;

/**
 * Compte patient : SOURCE UNIQUE de toute écriture sur accounts.balance.
 *
 * Convention (celle de BillingService et PaymentService) :
 *   solde = Σ transactions.total − Σ paiements.montant (patient ET assurance)
 * c'est-à-dire ce qui reste dû sur les pièces du patient, dans CET établissement.
 *
 * CORRECTIONS
 * - Écritures atomiques (UPDATE balance = balance ± x) : deux encaissements
 *   simultanés ne s'écrasent plus (lecture-modification-écriture).
 * - Un encaissement débite le compte de SA transaction (transactions.account_id),
 *   et non « un » compte du patient pris au hasard.
 * - Un compte par patient et par établissement (index unique), créé sans course.
 */
class PatientAccountService
{
    public function getOrCreate(Patient $patient): Account
    {
        $existant = Account::whereIn('owner_type', TypesFacturables::variantes(Patient::class))->where('owner_id', $patient->id)->first();
        if ($existant) {
            return $existant;
        }

        try {
            return Account::create(['owner_type' => TypesFacturables::alias(Patient::class), 'owner_id' => $patient->id, 'balance' => 0]);
        } catch (QueryException $e) {
            // Création concurrente : l'index unique a refusé le doublon, on relit.
            return Account::whereIn('owner_type', TypesFacturables::variantes(Patient::class))->where('owner_id', $patient->id)->firstOrFail();
        }
    }

    public function credit(Patient $patient, float $amount): Account
    {
        $this->verifierMontant($amount);
        $account = $this->getOrCreate($patient);
        $this->ajuster($account, $amount);

        return $account->refresh();
    }

    public function debit(Patient $patient, float $amount): Account
    {
        $this->verifierMontant($amount);
        $account = $this->getOrCreate($patient);
        $this->ajuster($account, -$amount);

        return $account->refresh();
    }

    public function adjust(Patient $patient, float $amount, string $direction): Account
    {
        return match ($direction) {
            'credit' => $this->credit($patient, $amount),
            'debit' => $this->debit($patient, $amount),
            default => throw new InvalidArgumentException('Direction invalide.'),
        };
    }

    /** Encaissement (patient ou assurance) sur une transaction : débite le compte de cette transaction. */
    public function debiterPourTransaction(Transaction $transaction, float $montant): void
    {
        $this->verifierMontant($montant);

        $account = $transaction->account_id ? Account::find($transaction->account_id) : null;
        $account ??= $transaction->patient ? $this->getOrCreate($transaction->patient) : null;

        if (! $account) {
            throw new InvalidArgumentException("Transaction #{$transaction->id} sans compte ni patient : encaissement impossible.");
        }

        if (! $transaction->account_id) {
            $transaction->forceFill(['account_id' => $account->id])->save();
        }

        $this->ajuster($account, -$montant);
    }

    /**
     * Variation du montant d'une pièce (remise, recalcul) ou annulation d'un
     * encaissement : ajuste le compte de CETTE transaction, de façon atomique.
     * delta > 0 : le patient doit davantage ; delta < 0 : il doit moins.
     */
    public function ajusterPourTransaction(Transaction $transaction, float $delta): void
    {
        if (abs($delta) < 0.01) {
            return;
        }

        $account = $transaction->account_id ? Account::find($transaction->account_id) : null;
        $account ??= $transaction->patient ? $this->getOrCreate($transaction->patient) : null;

        if (! $account) {
            throw new InvalidArgumentException("Transaction #{$transaction->id} sans compte ni patient : ajustement impossible.");
        }

        if (! $transaction->account_id) {
            $transaction->forceFill(['account_id' => $account->id])->save();
        }

        $this->ajuster($account, $delta);
    }

    /**
     * Suppression d'une pièce : retire du compte ce qui y restait dû (total − déjà payé).
     * Remplace l'ancien « balance -= sub_total », faux dès qu'un paiement avait eu lieu.
     */
    public function retirerTransaction(Transaction $transaction): void
    {
        if (! $transaction->account_id) {
            return;
        }

        $paye = (float) Paiement::where('transaction_id', $transaction->id)->sum('montant');
        $resteDu = (float) $transaction->total - $paye;

        if (abs($resteDu) >= 0.01) {
            $this->ajuster(Account::findOrFail($transaction->account_id), -$resteDu);
        }
    }

    /** Solde qu'aurait le compte si toutes les écritures avaient été justes. */
    public function soldeAttendu(Account $account): float
    {
        $transactions = Transaction::withoutGlobalScopes()->where('account_id', $account->id);

        $total = (float) (clone $transactions)->sum('total');
        // withoutGlobalScope('etablissement') et NON withoutGlobalScopes() : le scope
        // « valides » doit rester actif, un paiement annulé ne réduit pas le solde.
        $paye = (float) Paiement::withoutGlobalScope('etablissement')->whereIn('transaction_id', (clone $transactions)->select('id'))->sum('montant');

        return round($total - $paye, 2);
    }

    /** Remet le solde à sa valeur attendue ; renvoie l'écart corrigé. */
    public function recalculer(Account $account): float
    {
        return DB::transaction(function () use ($account) {
            $verrouille = Account::withoutGlobalScopes()->whereKey($account->id)->lockForUpdate()->firstOrFail();
            $attendu = $this->soldeAttendu($verrouille);
            $ecart = round($attendu - (float) $verrouille->balance, 2);

            if (abs($ecart) >= 0.01) {
                Account::withoutGlobalScopes()->whereKey($verrouille->id)->update(['balance' => $attendu]);
            }

            return $ecart;
        });
    }

    private function ajuster(Account $account, float $delta): void
    {
        // Écriture atomique : pas de perte si deux caisses encaissent en même temps.
        Account::withoutGlobalScopes()->whereKey($account->id)->update([
            'balance' => DB::raw('balance + ' . number_format($delta, 2, '.', '')),
            'updated_at' => now(),
        ]);
    }

    private function verifierMontant(float $montant): void
    {
        if ($montant < 0) {
            throw new InvalidArgumentException('Le montant ne peut pas être négatif.');
        }
    }
}
