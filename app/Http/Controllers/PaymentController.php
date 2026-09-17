<?php

namespace App\Http\Controllers;

use App\Models\Hospitalisation;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\Transaction;
use App\Services\BillingService;
use App\Services\BillingItemBuilderService;
use App\Services\InsuranceCalculationService;
use App\Services\ConsultationService;
use App\Services\PaymentService;
use App\Support\Facturation\SoldeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{

    public function __construct(
        private InsuranceCalculationService $insuranceService,
        private BillingService $billingService,
        private PaymentService $paymentService,
        private BillingItemBuilderService $itemBuilder,
        private ConsultationService $consultationService

    ) {
    }

    public function factureNonPayer()
    {
        $transactionsDu = Transaction::whereIn('status', ['pending', 'partial'])
            ->with('patient', 'invoice')
            ->whereRaw('total > 0')
            ->orderBy('created_at', 'DESC')
            ->get();

        return view('payments.unpaid', compact('transactionsDu'));
    }

    public function calculateCoverage(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists_etablissement:transactions,id',
            'patient_id' => 'required|exists:patients,id',
        ]);

        $transaction = Transaction::with([
                                        'patient',
                                        'transactionable'
                                    ])->findOrFail($request->transaction_id);

        $payload = $this->itemBuilder->buildFromTransaction($transaction);

        // Aperçu : la consommation déjà enregistrée par CETTE facture est exclue.
        $calculation = $this->insuranceService->calculateInsuranceCoverage(
            $transaction->patient_id,
            $payload['items'],
            $transaction->created_at,
            $transaction->invoice?->id
        );

        return response()->json([
            'success' => true,
            'calculation' => $calculation,
            'items' => $payload['items'],
        ]);
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'transaction_id' => 'required|exists_etablissement:transactions,id',
            'montant' => 'required|numeric|min:0.01',
            'source' => 'required|string',
            'description' => 'nullable|string',
            'part_insurance' => 'nullable|numeric|min:0',
            'part_patient' => 'nullable|numeric|min:0',
            'selected_insurances' => 'nullable|array',
            'actes' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {

            $transaction = Transaction::with('patient', 'invoice')->findOrFail($request->transaction_id);

            $selectedInsurances = $request->input('selected_insurances', []);
            $assurance_1 = $selectedInsurances[0] ?? false;
            $assurance_2 = $selectedInsurances[1] ?? false;

            // $insuranceCompanyIds = collect($request->selected_insurances ?? [])
            //                         ->pluck('id')
            //                         ->filter()
            //                         ->values()
            //                         ->all();

            if ($request->boolean('use_insurance') && ($assurance_1 || $assurance_2) && (float) $request->part_insurance > 0) {
                $transaction = $this->billingService->recalculate($transaction);
            }

            if (!empty($request->actes) && !$this->isCustomArrayEmpty($request->actes)) {
                $transaction = $this->billingService->applyDiscounts($transaction, $request->actes);
            }

            $transaction = Transaction::with('patient', 'invoice')->findOrFail($request->transaction_id);
            $payeAvant = SoldeTransaction::pour($transaction)->payePatient;

            $transaction = $this->paymentService->payPatientTransaction(
                transaction: $transaction,
                amount: (float) $request->montant,
                paymentMethod: $request->source,
                description: $request->description
            );

            DB::commit();

            // La caisse doit savoir ce qui a VRAIMENT été encaissé : un montant
            // supérieur au reste dû n'est pas encaissé au-delà (monnaie à rendre).
            $solde = SoldeTransaction::pour($transaction);
            $encaisse = round($solde->payePatient - $payeAvant, 2);
            $message = 'Paiement enregistré : ' . number_format($encaisse, 0, ',', ' ') . ' GNF.';

            if ((float) $request->montant - $encaisse >= 1) {
                $message .= ' Montant saisi supérieur au reste dû : ' . number_format((float) $request->montant - $encaisse, 0, ',', ' ') . ' GNF à rendre au patient.';
            }
            if ($solde->resteDuPatient() > 0) {
                $message .= ' Reste à payer : ' . number_format($solde->resteDuPatient(), 0, ',', ' ') . ' GNF.';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    public function paiementHospitalisation(Hospitalisation $hospitalisation)
    {
        DB::beginTransaction();

        try {
            $dateDebut = \Carbon\Carbon::parse($hospitalisation->date_entree);
            $dateFin = $hospitalisation->date_sortie_effective ?? now();
            $nombreJours = ceil($dateDebut->diffInDays($dateFin) ?: 1);

            if ($hospitalisation->statut !== 'Terminé') {
                $hospitalisation->date_sortie_effective = now();
                $hospitalisation->nombre_jours = $nombreJours;
                $hospitalisation->statut = 'Terminé';
                $hospitalisation->save();

                $hospitalisation->chambre->update(['statut' => 'Libre']);
            }

            $this->billingService->createFromHospitalisation($hospitalisation);

            DB::commit();

            return redirect()->back()->with('success', 'Facturation d’hospitalisation traitée avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    public function processPaymentPatientOrInsurance(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'montant_patient' => 'nullable|numeric|min:0',
            'montant_assurance' => 'nullable|numeric|min:0',
            'source_patient' => 'nullable|string',
            'description_patient' => 'nullable|string',
            'description_assurance' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $patient = Patient::findOrFail($request->patient_id);

            if ((float) $request->montant_patient > 0) {
                $this->paymentService->payPatientForPatient(
                    patient: $patient,
                    amount: (float) $request->montant_patient,
                    paymentMethod: $request->source_patient ?? 'CASH',
                    description: $request->description_patient
                );
            }

            if ((float) $request->montant_assurance > 0) {
                $transactions = $patient->transactions()
                    ->with('invoice')
                    ->whereIn('status', ['pending', 'partial', 'approved'])
                    ->get();

                $remaining = (float) $request->montant_assurance;

                foreach ($transactions as $transaction) {
                    if ($remaining <= 0) {
                        break;
                    }

                    if (!$transaction->invoice) {
                        continue;
                    }

                    // CORRIGÉ lot 1 : `Paiement` n'était pas importé dans ce contrôleur
                    // (erreur fatale au premier encaissement assurance) ; le reste dû
                    // vient désormais de la règle commune.
                    $due = SoldeTransaction::pour($transaction)->resteDuAssurance();
                    if ($due <= 0) {
                        continue;
                    }

                    $amountToApply = min($remaining, $due);

                    $this->paymentService->payInsuranceTransaction(
                        transaction: $transaction,
                        amount: $amountToApply,
                        description: $request->description_assurance
                    );

                    $remaining -= $amountToApply;
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Paiement traité avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    private function isCustomArrayEmpty(?array $data): bool
    {
        if (empty($data)) {
            return true;
        }

        foreach ($data as $items) {
            foreach ($items as $val) {
                if ((int) $val > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getTransactionActes(Transaction $transaction)
    {
        $transaction->load([
            'patient',
            'transactionable'
        ]);

        $actes = $this->consultationService->getActes($transaction);

        return response()->json([
            'success' => true,
            'actes' => $actes
        ]);
    }
}