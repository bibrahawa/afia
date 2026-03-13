<?php

namespace App\Http\Controllers;

use App\Models\Hospitalisation;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\Transaction;
use App\Services\BillingService;
use App\Services\BillingItemBuilderService;
use App\Services\InsuranceCalculationService;
use App\Services\ConsultationService;
use App\Services\PaymentService;
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

    public function showPaymentPage($patientId)
    {
        $patientsDu = Patient::select([
                'patients.id',
                'patients.first_name',
                'patients.middle_name',
                'patients.last_name',
                'patients.district',
                'patients.location',
                \DB::raw('SUM(transactions.total - transactions.montant_payer) as montant_du')
            ])
            ->join('transactions', 'patients.id', '=', 'transactions.patient_id')
            ->whereIn('transactions.status', ['pending', 'partial', 'approved'])
            ->groupBy('patients.id', 'patients.first_name', 'patients.middle_name', 'patients.last_name', 'patients.district', 'patients.location')
            ->having('montant_du', '>', 0)
            ->get();

        $assurances = InsuranceCompany::where('status', 'active')->get();

        $patientInsurances = PatientInsurance::where('patient_id', $patientId)
            ->where('status', 'active')
            ->with('insuranceCompany')
            ->get();

        return view('payments.index', compact('patientsDu', 'assurances', 'patientInsurances'));
    }

    public function calculateCoverage(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'patient_id' => 'required|exists:patients,id',
        ]);

        $transaction = Transaction::with([
                                        'patient',
                                        'transactionable'
                                    ])->findOrFail($request->transaction_id);

        $payload = $this->itemBuilder->buildFromTransaction($transaction);

        $calculation = $this->insuranceService->calculateInsuranceCoverage(
            $request->patient_id,
            $payload['items']
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
            'transaction_id' => 'required|exists:transactions,id',
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

            $this->paymentService->payPatientTransaction(
                transaction: $transaction,
                amount: (float) $request->montant,
                paymentMethod: $request->source,
                description: $request->description
            );

            DB::commit();

            return redirect()->back()->with('success', 'Paiement traité avec succès');
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

                    $invoice = $transaction->invoice;
                    if (!$invoice || (float) $invoice->insurance_amount <= 0) {
                        continue;
                    }

                    $alreadyPaid = Paiement::where('transaction_id', $transaction->id)
                        ->where('type', 'remboursement')
                        ->sum('montant');

                    $due = max(0, (float) $invoice->insurance_amount - $alreadyPaid);
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