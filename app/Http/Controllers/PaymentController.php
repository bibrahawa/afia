<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Service\InsuranceCalculationService;
use App\Service\ConsultationItemService;
use App\Service\TransactionService;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\InsuranceCompany;
use App\Models\PatientInsurance;
use App\Models\Transaction;
use DB;

class PaymentController extends Controller
{
    protected $insuranceService, $consultationItem, $transactionPay;

    public function __construct(InsuranceCalculationService $insuranceService, ConsultationItemService $consultationItem, TransactionService $transactionPay)
    {
        $this->insuranceService = $insuranceService;
        $this->consultationItem = $consultationItem;
        $this->transactionPay = $transactionPay;
    }

    /**
     * Afficher la page de paiement avec les assurances
     */
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
                ->whereIn('transactions.status', ['pending', 'partial'])
                ->groupBy('patients.id', 'patients.first_name','patients.middle_name', 'patients.last_name', 'patients.district', 'patients.location')
                ->having('montant_du', '>', 0)
                ->get();

        
        // Récupérer les compagnies d'assurance actives
        $assurances = InsuranceCompany::where('status', 'active')->get();
        
        // Récupérer les assurances du patient
        $patientInsurances = PatientInsurance::where('patient_id', $patientId)
            ->where('status', 'active')
            ->with('insuranceCompany')
            ->get();

        return view('payments.index', compact('patientsDu', 'assurances', 'patientInsurances'));
    }

    /**
     * Calculer la couverture d'assurance (AJAX)
     */
    public function calculateCoverage(Request $request)
    {

        $patientId = $request->patient_id;
        // $montantOriginal = $request->montant_original;
        // $insuranceIds = $request->insurance_ids ?? [];

        $patient = Patient::find($patientId);

        $consultations = $patient->transactions()
                                ->where('status', '!=', 'paid')
                                ->where('status', '!=', 'approved')
                                ->with('transactionable')
                                ->get()
                                ->pluck('transactionable')
                                ->filter();
        
        $result = $this->getConsultationItems($consultations);

        $calculation = $this->insuranceService->calculateInsuranceCoverage($patientId, $result['items']);

        return response()->json([
            'success' => true,
            'calculation' => $calculation
        ]);
    }

    /**
     * Calculer la couverture d'assurance pour une consultation
     */
    public function calculateCoverage_old(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'montant_original' => 'required|numeric|min:0',
            'insurance_ids' => 'required|array|min:1',
            'insurance_ids.*' => 'exists:patient_insurances,id'
        ]);

        try {
            DB::beginTransaction();

            $patient = Patient::findOrFail($request->patient_id);
            $montantOriginal = floatval($request->montant_original);
            
            // Récupérer les assurances sélectionnées
            $insurances = PatientInsurance::with('insuranceCompany')
                ->whereIn('id', $request->insurance_ids)
                ->where('patient_id', $patient->id)
                ->where('status', 'active')
                ->orderByRaw("FIELD(id, " . implode(',', $request->insurance_ids) . ")")
                ->get();

            $montantRestant = $montantOriginal;
            $couvertureTotal = 0;
            $assurancesUtilisees = [];

            $actes = $this->consultationItem->getActesFromPendingTransactions($patient);


            $actesDetail = [];

            // Calculer la couverture pour chaque acte
            foreach ($actes as $acte) {
                $montantActe = $acte->prix_unitaire * ($acte->quantite ?? 1);
                $montantActeRestant = $montantActe;
                $couvertureActe = 0;

                // Appliquer chaque assurance dans l'ordre
                foreach ($insurances as $insurance) {
                    if ($montantActeRestant <= 0) break;

                    $tauxCouverture = $insurance->insuranceCompany->default_coverage_percentage / 100;
                    $montantCouvert = $montantActeRestant * $tauxCouverture;

                    // Vérifier le plafond restant
                    if ($insurance->remaining_limit > 0 && $montantCouvert > $insurance->remaining_limit) {
                        $montantCouvert = $insurance->remaining_limit;
                    }

                    $couvertureActe += $montantCouvert;
                    $montantActeRestant -= $montantCouvert;

                    // Mettre à jour les données de l'assurance utilisée
                    $assuranceKey = $insurance->id;
                    if (!isset($assurancesUtilisees[$assuranceKey])) {
                        $assurancesUtilisees[$assuranceKey] = [
                            'insurance_company' => $insurance->insuranceCompany->name,
                            'policy_number' => $insurance->policy_number,
                            'total_covered' => 0
                        ];
                    }
                    $assurancesUtilisees[$assuranceKey]['total_covered'] += $montantCouvert;
                }

                $actesDetail[] = [
                    'id' => $acte->id,
                    'nom' => $acte->nom,
                    'type' => $acte->type,
                    'montant_original' => $montantActe,
                    'couverture_assurance' => $couvertureActe,
                    'montant_patient' => $montantActe - $couvertureActe
                ];

                $couvertureTotal += $couvertureActe;
            }

            $montantPatient = $montantOriginal - $couvertureTotal;

            $calculation = [
                'total_amount' => $montantOriginal,
                'insurance_coverage' => $couvertureTotal,
                'patient_amount' => $montantPatient,
                'insurances_used' => array_values($assurancesUtilisees),
                'actes_detail' => $actesDetail
            ];

            DB::commit();

            return response()->json([
                'success' => true,
                'calculation' => $calculation
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de la couverture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getConsultationItems($consultations)
    {
        $items = [];
        $totalAmount = 0;
        
        foreach ($consultations as $consultation) {
                
            // Services
            foreach ($consultation->services ?? [] as $service) {
                $partAssurance = 0;
                $partPatient = $service->amount;

                $items[] = [
                    'acte_type'   => 'App\\Models\\Service',
                    'acte_id'     => $service->id,
                    'description' => $service->name,
                    'unit_price'  => $service->amount,
                    'quantity'    => 1,
                    'total'       => $service->amount,
                ];

                $totalAmount += $service->amount;
            }

            // Packages
            foreach ($consultation->packages ?? [] as $package) {
                $partAssurance = 0;
                $partPatient = $package->amount;

                $items[] = [
                    'acte_type'   => 'App\\Models\\Package',
                    'acte_id'     => $package->id,
                    'description' => $package->name,
                    'unit_price'  => $package->amount,
                    'quantity'    => 1,
                    'total'       => $package->amount,
                ];

                $totalAmount += $package->amount;
            }

            // Tests
            foreach ($consultation->tests ?? [] as $test) {

                $partAssurance = 0;
                $partPatient = $test->amount;

                $items[] = [
                    'acte_type'   => 'App\\Models\\Test',
                    'acte_id'     => $test->id,
                    'description' => $test->name,
                    'unit_price'  => $test->amount,
                    'quantity'    => 1,
                    'total'       => $test->amount,
                ];

                $totalAmount += $test->amount;
            }

            // Médicaments
            foreach ($consultation->medicaments ?? [] as $medicament) {

                $partAssurance = 0;
                $partPatient = $medicament->amount;

                $items[] = [
                    'acte_type'   => 'App\\Models\\Medicament',
                    'acte_id'     => $medicament->id,
                    'description' => $medicament->nom,
                    'unit_price'  => $medicament->amount,
                    'quantity'    => 1,
                    'total'       => $medicament->amount,
                ];

                $totalAmount += $medicament->amount;
            }
        }

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];

    }

    private function getCoverageTypeModel($category)
    {
        return match ($category) {
            'services' => 'App\\Models\\Service',
            'packages' => 'App\\Models\\Package', // Ajustez selon votre namespace
            'examens' => 'App\\Models\\Test',
            'medicaments' => 'App\\Models\\Medicament',
            default => null
        };
    }

    /**
     * Traiter le paiement avec assurance
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'montant' => 'required|numeric|min:0',
            'source' => 'required|string',
            'description' => 'nullable|string',
            'has_insurance' => 'boolean',
            'assurance_1' => 'nullable|exists:insurance_companies,id',
            'police_1' => 'nullable|string',
            'assurance_2' => 'nullable|exists:insurance_companies,id',
            'police_2' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {

            $assurance_1 = $request->selected_insurances[0] ?? false;
            $assurance_2 = $request->selected_insurances[1] ?? false;

            $this->transactionPay->paiementTransaction($request->patient_id, $request->source, $request->montant, $request->description);
            
            // Si il y a des assurances, créer la facture avec couverture
            if ($request->use_insurance && ($assurance_1 || $assurance_2)) {

                $insuranceCompanyIds = array_filter([$assurance_1, $assurance_2]);
                // $transactions = Patient::find($request->patient_id)->transactions;

                $transactions = Patient::find($request->patient_id)->transactions()
                                ->where('status', '!=', 'paid')
                                ->where('status', '!=', 'approved')
                                ->with('transactionable')
                                ->get();

                // Alternative plus propre avec collection Laravel
                $invoices = $transactions->map(function($transaction) use ($request, $insuranceCompanyIds) {
                    // Vérifications
                    if (!$transaction->transactionable) {
                        return null;
                    }
                    
                    $consultation = [$transaction->transactionable];
                    $result = $this->getConsultationItems($consultation);

                    if (empty($result['items'])) {
                        return null;
                    }
                    
                    try {
                        return $this->insuranceService->createInvoiceWithInsurance(
                            $transaction,
                            $request->patient_id,
                            $result['items'],
                            $insuranceCompanyIds
                        );
                    } catch (\Exception $e) {
                        \Log::error('Erreur création facture pour transaction ' . $transaction->id . ': ' . $e->getMessage());
                        return null;
                    }
                })->filter(); 


            }

            DB::commit();
            
            return redirect()->back()->with('success', 'Paiement traité avec succès');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    
}
