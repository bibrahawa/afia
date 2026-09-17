<?php

namespace App\Services\Labo;

use App\Models\Transaction;
use App\Enums\Labo\ModeFacturation;
use App\Enums\Labo\StatutExamen;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboDemande;
use App\Services\BillingItemBuilderService;
use App\Services\BillingService;
use App\Services\InsuranceCalculationService;
use App\Services\InsuranceConsumptionService;
use App\Services\InvoiceService;
use App\Services\PatientAccountService;
use App\Services\TransactionStatusService;
use Illuminate\Support\Facades\DB;

/**
 * Branche le labo sur la facturation EXISTANTE (Transaction → Invoice →
 * InvoiceItem → Paiement, assurances comprises) au lieu d'une caisse
 * parallèle : une seule comptabilité, un seul encours patient, et les
 * redevances plateforme (Pilier C) pourront compter les actes labo comme
 * les autres.
 *
 * Même séquence que BillingService::createFromConsultation().
 */
class FacturationLaboService
{
    public function __construct(
        private BillingItemBuilderService $itemBuilder,
        private InsuranceCalculationService $insuranceCalculation,
        private PatientAccountService $patientAccount,
        private TransactionStatusService $transactionStatus,
        private InvoiceService $invoices,
        private InsuranceConsumptionService $insuranceConsumption,
        private BillingService $billing,
    ) {
    }

    public function facturer(LaboDemande $demande): Transaction
    {
        if ($demande->mode_facturation !== ModeFacturation::LABO) {
            throw new OperationLaboImpossible('Cette demande n\'est pas facturée par le laboratoire (' . $demande->mode_facturation->libelle() . ').');
        }

        if ($demande->transaction()->exists()) {
            throw new OperationLaboImpossible('Cette demande est déjà facturée.');
        }

        return DB::transaction(function () use ($demande) {
            $demande->loadMissing('patient');

            $payload = $this->itemBuilder->buildFromLaboDemande($demande);
            if (! $payload['items']) {
                throw new OperationLaboImpossible('Aucun examen facturable.');
            }

            $calcul = $this->insuranceCalculation->calculateInsuranceCoverage($demande->patient_id, $payload['items']);
            $compte = $this->patientAccount->credit($demande->patient, (float) $calcul['total_amount']);

            $transaction = $demande->transaction()->create([
                'user_id' => auth()->id(),
                'account_id' => $compte->id,
                'patient_id' => $demande->patient_id,
                'description' => 'Analyses de laboratoire — ' . $demande->numero,
                'tax_amount' => 0,
                'discount' => 0,
                'sub_total' => $calcul['total_amount'],
                'total' => $calcul['total_amount'],
                'status' => 'pending',
            ]);

            $invoice = $this->invoices->createOrUpdateInvoice($transaction, $calcul, $payload['items']);
            $this->insuranceConsumption->applyConsumptionsAndClaims($invoice, $calcul['insurances_used'] ?? [], $transaction->patient_id);
            $this->transactionStatus->refresh($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    /** Après annulation d'un examen : la facture existante suit (moteur existant). */
    public function recalculerSiFacturee(LaboDemande $demande): void
    {
        $transaction = $demande->transaction()->first();

        if (! $transaction || $demande->mode_facturation !== ModeFacturation::LABO) {
            return;
        }

        $restants = $demande->examens()->where('statut', '!=', StatutExamen::ANNULE->value)->count();
        if ($restants === 0) {
            return; // annulation complète gérée par DemandeService::annuler()
        }

        $this->billing->recalculate($transaction);
    }
}
