<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Hospitalisation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillingService
{
    public function __construct(
        private BillingItemBuilderService $itemBuilder,
        private InsuranceCalculationService $insuranceCalculationService,
        private PatientAccountService $patientAccountService,
        private TransactionStatusService $transactionStatusService,
        private InvoiceService $invoiceService,
        private InsuranceConsumptionService $insuranceConsumptionService,
        private \App\Services\Facturation\FigementFacture $figement,
        private \App\Services\Facturation\RemiseService $remises,
    ) {
    }

    public function createFromConsultation(Consultation $consultation): Transaction
    {
        return DB::transaction(function () use ($consultation) {
            $consultation->loadMissing('patient');

            $payload = $this->itemBuilder->buildFromConsultation($consultation);
            $calculation = $this->insuranceCalculationService->calculateInsuranceCoverage(
                $consultation->patient_id,
                $payload['items']
            );

            $account = $this->patientAccountService->credit(
                $consultation->patient,
                (float) $calculation['total_amount']
            );

            $transaction = $consultation->transaction()->create([
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'patient_id' => $consultation->patient_id,
                'description' => $consultation->motif,
                'tax_amount' => 0,
                'discount' => 0,
                'sub_total' => $calculation['total_amount'],
                'total' => $calculation['total_amount'],
                'status' => 'pending',
            ]);

            $invoice = $this->invoiceService->createOrUpdateInvoice($transaction, $calculation, $payload['items']);

            $this->insuranceConsumptionService->applyConsumptionsAndClaims(
                $invoice,
                $calculation['insurances_used'] ?? [],
                $transaction->patient_id
            );

            $consultation->update(['est_facturee' => true]);

            $this->transactionStatusService->refresh($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    public function createFromHospitalisation(Hospitalisation $hospitalisation): Transaction
    {
        return DB::transaction(function () use ($hospitalisation) {
            $hospitalisation->loadMissing('patient', 'chambre');

            $payload = $this->itemBuilder->buildFromHospitalisation($hospitalisation);
            // Droits évalués à l'ADMISSION : un bon ou un contrat valable à l'entrée
            // couvre tout le séjour, même s'il expire avant la sortie.
            $calculation = $this->insuranceCalculationService->calculateInsuranceCoverage(
                $hospitalisation->patient_id,
                $payload['items'],
                $this->dateSoin($hospitalisation)
            );

            $account = $this->patientAccountService->credit(
                $hospitalisation->patient,
                (float) $calculation['total_amount']
            );

            $transaction = $hospitalisation->transaction()->create([
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'patient_id' => $hospitalisation->patient_id,
                'description' => $hospitalisation->observation ?? 'Frais d’hospitalisation',
                'tax_amount' => 0,
                'discount' => 0,
                'sub_total' => $calculation['total_amount'],
                'total' => $calculation['total_amount'],
                'status' => 'pending',
            ]);

            $invoice = $this->invoiceService->createOrUpdateInvoice($transaction, $calculation, $payload['items']);

            $this->insuranceConsumptionService->applyConsumptionsAndClaims(
                $invoice,
                $calculation['insurances_used'] ?? [],
                $transaction->patient_id
            );

            $this->transactionStatusService->refresh($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    /**
     * Régénère la facture d'une pièce (actes modifiés, assurance changée,
     * durée d'hospitalisation, examen de labo annulé…).
     *
     * RÉVISION lot 1 :
     *  - verrou sur la transaction (pas de recalcul pendant un encaissement) ;
     *  - refus si la réclamation est déjà transmise à l'assureur ou réglée par
     *    bordereau, et refus si la facture passerait sous ce qui a déjà été
     *    encaissé (FigementFacture) — tout est annulé dans ce cas ;
     *  - les remises accordées sont conservées ; totaux et compte patient
     *    suivent les lignes (InvoiceService::synchroniserTotaux).
     */
    public function recalculate(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = Transaction::whereKey($transaction->id)
                ->lockForUpdate()
                ->with(['patient', 'invoice.items'])
                ->firstOrFail();

            if (!$transaction->invoice) {
                throw new InvalidArgumentException('Transaction sans facture.');
            }

            $this->figement->verifierRegenerable($transaction);

            // rollback ancienne consommation assurance
            $this->insuranceConsumptionService->rollbackConsumption($transaction->invoice);

            $payload = $this->itemBuilder->buildFromTransaction($transaction);

            // Droits et plafonds évalués à la date des soins (admission pour une
            // hospitalisation, date de la pièce sinon), pas au jour du recalcul.
            $calculation = $this->insuranceCalculationService->calculateInsuranceCoverage(
                $transaction->patient_id,
                $payload['items'],
                $this->dateSoin($transaction->transactionable) ?? $transaction->created_at,
                $transaction->invoice->id
            );

            $invoice = $this->invoiceService->createOrUpdateInvoice(
                $transaction,
                $calculation,
                $payload['items']
            );

            // appliquer la nouvelle consommation assurance
            $this->insuranceConsumptionService->applyConsumptionsAndClaims(
                $invoice,
                $calculation['insurances_used'] ?? [],
                $transaction->patient_id
            );

            // Une facture ne descend jamais sous ce qui a déjà été encaissé.
            $this->figement->verifierEncaissementsCouverts($transaction);

            $this->transactionStatusService->refresh($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    /**
     * @deprecated Utiliser Facturation\RemiseService::appliquer() — conservé pour les appels existants.
     */
    public function applyDiscounts(Transaction $transaction, array $discounts): Transaction
    {
        return $this->remises->appliquer($transaction, $discounts);
    }

    /** Date de référence des droits : admission pour une hospitalisation, date de création sinon. */
    public function dateSoin($piece): ?\Carbon\Carbon
    {
        if ($piece instanceof Hospitalisation && $piece->date_entree) {
            return \Carbon\Carbon::parse($piece->date_entree)->setTimeFrom(now());
        }

        return $piece?->created_at;
    }
}
