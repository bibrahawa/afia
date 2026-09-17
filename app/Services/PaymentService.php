<?php

namespace App\Services;

use App\Models\InsuranceCompany;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Transaction;
use App\Support\Facturation\SoldeTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Encaissements (part patient, part assurance).
 *
 * RÉVISION lot 1 :
 *  - VERROU : chaque encaissement verrouille la ligne de la transaction
 *    (SELECT … FOR UPDATE) avant de calculer le reste dû. Deux caisses qui
 *    encaissent la même facture en même temps sont sérialisées : la seconde
 *    voit le reste dû mis à jour au lieu d'encaisser une deuxième fois.
 *  - Reste dû calculé par SoldeTransaction, part patient et part assurance
 *    séparées (fini le « montant_payer » qui mélangeait les deux).
 *  - montant_payer est maintenu par TransactionStatusService (plus
 *    d'incrément manuel qui dérivait).
 *
 * Les montants saisis au-delà du reste dû ne sont PAS encaissés : le résultat
 * indique ce qui a réellement été appliqué, à l'écran d'en informer la caisse.
 */
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
        $this->verifierMontant($amount);

        return DB::transaction(function () use ($transaction, $amount, $paymentMethod, $description) {
            $transaction = $this->verrouiller($transaction);
            $resteDu = SoldeTransaction::pour($transaction)->resteDuPatient();

            if ($resteDu <= 0) {
                throw new InvalidArgumentException('La part patient est déjà réglée.');
            }

            $this->encaisser($transaction, Paiement::TYPE_PATIENT, min($amount, $resteDu), strtoupper($paymentMethod), $description ?? 'Paiement part patient');

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    public function payInsuranceTransaction(
        Transaction $transaction,
        float $amount,
        ?string $description = null
    ): Transaction {
        $this->verifierMontant($amount);

        return DB::transaction(function () use ($transaction, $amount, $description) {
            $transaction = $this->verrouiller($transaction);

            if (! $transaction->invoice) {
                throw new InvalidArgumentException('Aucune facture liée à cette transaction.');
            }

            $resteDu = SoldeTransaction::pour($transaction)->resteDuAssurance();

            if ($resteDu <= 0) {
                throw new InvalidArgumentException('La part assurance est déjà réglée.');
            }

            $this->encaisser($transaction, Paiement::TYPE_ASSURANCE, min($amount, $resteDu), null, $description ?? 'Paiement assurance');

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    public function payPatientForPatient(
        Patient $patient,
        float $amount,
        string $paymentMethod,
        ?string $description = null
    ): array {
        $this->verifierMontant($amount);

        return DB::transaction(function () use ($patient, $amount, $paymentMethod, $description) {
            $remaining = $amount;
            $updatedTransactions = [];

            $ids = $patient->transactions()
                ->whereIn('status', ['pending', 'partial', 'approved'])
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids as $id) {
                if ($remaining < 0.01) {
                    break;
                }

                $transaction = $this->verrouiller($id);
                $resteDu = SoldeTransaction::pour($transaction)->resteDuPatient();

                if ($resteDu <= 0) {
                    continue;
                }

                $applique = min($remaining, $resteDu);
                $this->encaisser($transaction, Paiement::TYPE_PATIENT, $applique, strtoupper($paymentMethod), $description ?? 'Paiement part patient');

                $updatedTransactions[] = $transaction->fresh(['invoice', 'paiements']);
                $remaining = round($remaining - $applique, 2);
            }

            return [
                'paid_amount' => round($amount - $remaining, 2),
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
        $this->verifierMontant($amount);

        return DB::transaction(function () use ($company, $amount, $description) {
            $remaining = $amount;
            $updatedTransactions = [];

            $ids = Transaction::query()
                ->whereHas('invoice', function ($q) use ($company) {
                    $q->where('insurance_company_id', $company->id)
                        ->whereIn('insurance_status', ['pending', 'submitted', 'approved', 'partial'])
                        ->where('patient_amount_status', 'paid');
                })
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids as $id) {
                if ($remaining < 0.01) {
                    break;
                }

                $transaction = $this->verrouiller($id);
                $resteDu = SoldeTransaction::pour($transaction)->resteDuAssurance();

                if ($resteDu <= 0) {
                    continue;
                }

                $applique = min($remaining, $resteDu);
                $this->encaisser($transaction, Paiement::TYPE_ASSURANCE, $applique, null, $description ?? 'Paiement assurance');

                $updatedTransactions[] = $transaction->fresh(['invoice', 'paiements']);
                $remaining = round($remaining - $applique, 2);
            }

            return [
                'paid_amount' => round($amount - $remaining, 2),
                'remaining_amount' => $remaining,
                'transactions' => $updatedTransactions,
            ];
        });
    }

    /**
     * Verrouille la ligne de la transaction jusqu'à la fin de la transaction SQL.
     * À appeler DANS un DB::transaction().
     */
    private function verrouiller(Transaction|int $transaction): Transaction
    {
        $id = $transaction instanceof Transaction ? $transaction->id : $transaction;

        return Transaction::whereKey($id)->lockForUpdate()->with(['invoice', 'patient'])->firstOrFail();
    }

    private function encaisser(Transaction $transaction, string $type, float $montant, ?string $source, ?string $description): Paiement
    {
        $montant = round($montant, 2);

        $paiement = Paiement::create([
            'user_id' => auth()->id(),
            'patient_id' => $transaction->patient_id,
            'transaction_id' => $transaction->id,
            'source' => $source,
            'type' => $type,
            'description' => $description,
            'montant' => $montant,
        ]);

        // Débite le compte de CETTE transaction, de façon atomique.
        $this->patientAccountService->debiterPourTransaction($transaction, $montant);

        $this->transactionStatusService->refresh($transaction);

        return $paiement;
    }

    private function verifierMontant(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant doit être supérieur à zéro.');
        }
    }
}
