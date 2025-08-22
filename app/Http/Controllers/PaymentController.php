<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InsuranceCalculationService;
use App\Services\ConsultationService;
use App\Services\TransactionService;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\InsuranceCompany;
use App\Models\PatientInsurance;
use App\Models\Hospitalisation;
use App\Models\Transaction;
use DB;

class PaymentController extends Controller
{
    protected $insuranceService, $consultationItem, $transactionPay;

    public function __construct(InsuranceCalculationService $insuranceService, ConsultationService $consultationItem, TransactionService $transactionPay)
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

        $transactionId = $request->transaction_id;
        // $montantOriginal = $request->montant_original;
        // $insuranceIds = $request->insurance_ids ?? [];

        $transaction = Transaction::find($transactionId);

        $result      = $this->insuranceService->getTransactionAndHospitalisationItems($transaction);

        $calculation = $this->insuranceService->calculateInsuranceCoverage($request->patient_id, $result['items']);
        
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

            $actes = $this->consultationItem->getActesAndHospitalisationFromPendingTransactions($patient);


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

    private function getConsultationAndHospitalisationItems($patient)
    {
        
        $transactions = $patient->getPendingAndPartialTransaction();
       
        $items = [];
        $totalAmount = 0;

        foreach ($transactions as $transaction) {

            $consultation = $transaction->transactionable;

            if (!$consultation) continue;     
            
            // Services
            foreach ($consultation->services ?? [] as $service) {

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

            // Hospitalisation
            if($transaction->transactionable_type == "App\\Models\\Hospitalisation"){
                $hospitalisations = [$consultation];
                foreach ($hospitalisations as $hospitalisation) {

                    $items[] = [
                        'acte_type'   => 'App\\Models\\Hospitalisation',
                        'acte_id'     => $hospitalisation->id,
                        'description' => $hospitalisation->date_entree." au ".$hospitalisation->date_sortie_effective,
                        'unit_price'  => $hospitalisation->chambre->prix_par_jour,
                        'quantity'    => $hospitalisation->nombre_jours,
                        'total'       => $hospitalisation->total_payer,
                    ];

                    $totalAmount += $hospitalisation->total_payer;
                }
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
        
        // try {

            $montant = $request->montant;

            if ($montant == 0 || $montant == null || $montant < 0) {
                return redirect()->back()->with('error', 'Please enter a valid amount.');
            }

            $assurance_1            = $request->selected_insurances[0] ?? false;
            $assurance_2            = $request->selected_insurances[1] ?? false;
            $insuranceCompanyIds    = array_filter([$assurance_1, $assurance_2]);
            $transaction            = Transaction::where('id',$request->transaction_id)->with('patient', 'invoice')->first();
            
            $this->transactionPay->paiementTransaction($request->source, $request->montant, $request->description, $transaction, $request->part_patient);
            
            // Si il y a des assurances, créer la facture avec couverture
            if ($request->use_insurance && ($assurance_1 || $assurance_2) && $request->part_insurance > 0)
                $this->insuranceService->updateInvoiceWithInsurance($insuranceCompanyIds, $transaction, $request->montant);

            DB::commit();
            
            return redirect()->back()->with('success', 'Paiement traité avec succès');
            
        // } catch (\Exception $e) {
        //     DB::rollback();
        //     return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        // }
    }
    
    /**
     * Traiter le paiement avec assurance
     */
    public function processPayment_old(Request $request)
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

            $patient = Patient::find($request->patient_id);

            
            // Si il y a des assurances, créer la facture avec couverture
            if ($request->use_insurance && ($assurance_1 || $assurance_2) && $request->part_insurance > 0) {

                $insuranceCompanyIds = array_filter([$assurance_1, $assurance_2]);
                
                try {
                    return $this->insuranceService->updateInvoiceWithInsurance(
                        $patient,
                        $insuranceCompanyIds
                    );
                } catch (\Exception $e) {
                    \Log::error('Erreur création facture pour transaction ' . $transaction->id . ': ' . $e->getMessage());
                    return null;
                }
            }

            $this->transactionPay->paiementTransaction($patient, $request->source, $request->montant, $request->description);

            DB::commit();
            
            return redirect()->back()->with('success', 'Paiement traité avec succès');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

/**
 * Processes the payment for a specific invoice.
 *
 * @param Request $request The incoming request containing payment details.
 * @param int $invoiceId The ID of the invoice to be paid.
 * @return \Illuminate\Http\RedirectResponse Redirects back with a success message upon successful payment.
 */

    public function payInvoice(Request $request, $invoiceId)
    {
        $paymentData = $request->only(['payment_method', 'insurance_status', 'insurance_payment_date', 'insurance_claim_number', 'insurance_notes']);
        $this->insuranceService->processInvoicePayment($invoiceId, $paymentData);
        return redirect()->back()->with('success', 'Paiement effectué avec succès.');
    }

    public function paiementHospitalisation(Hospitalisation $hospitalisation)
    {
        
        try {

            DB::beginTransaction();

            // Durée réelle
            $dateDebut      = \Carbon\Carbon::parse($hospitalisation->date_entree);
            $dateFin        = $hospitalisation->date_sortie_effective ?? now();
            $nombreJours    = ceil($dateDebut->diffInDays($dateFin) ?: 1);

            $prixJour = $hospitalisation->chambre->prix_par_jour;
            $total = $prixJour * $nombreJours;

            // CREATE INVOICE FOR HOSPITALISATION
            $invoice = $this->insuranceService->createInvoiceForHospitalisation(
                            $hospitalisation->patient->id,
                            $hospitalisation,
                            $nombreJours,
                            $prixJour,
                            $total
                        );

            // Marquer comme libéré si pas déjà fait
            if ($hospitalisation->status !== 'Terminé') {

                $hospitalisation->date_sortie_effective = now();
                $hospitalisation->nombre_jours = $nombreJours;
                $hospitalisation->total_payer = $invoice['total_amount'];
                $hospitalisation->statut = 'Terminé';
                $hospitalisation->save();

                // Libérer la chambre
                $hospitalisation->chambre->update(['statut' => 'Libre']);
                
            }

            DB::commit();


            return redirect()->back()->with('success', 'Paiement traité avec succès');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    public function processPaymentPatientOrInsurance(Request $request)
    {
        $request->validate([
            'patient_id'   => 'required|exists:patients,id',
            'montant_patient' => 'nullable|numeric|min:0',
            'montant_assurance' => 'nullable|numeric|min:0',
            'source_patient' => 'nullable|string',
            'source_assurance' => 'nullable|string',
            'description_patient' => 'nullable|string',
            'description_assurance' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $patient = Patient::findOrFail($request->patient_id);

            $transactions = $patient->getPendingAndPartialTransaction();

            // -------------------------
            // 1. Paiement Part Patient
            // -------------------------
            $montantPatient = (float) $request->montant_patient;
            if ($montantPatient > 0) {
                foreach ($transactions as $transaction) {
                    if ($montantPatient <= 0) break;

                    $invoice = $transaction->invoice;
                    if (!$invoice) continue;

                    $restePatient = $invoice->patient_amount - $this->getPaidAmount($invoice->id, 'patient');
                    if ($restePatient <= 0) continue;

                    $montantAPayer = min($montantPatient, $restePatient);

                    // Enregistrer le paiement patient
                    $this->createPaiement($transaction, $patient, $request->source_patient ?? 'patient', $montantAPayer, $request->description_patient);

                    // Mettre à jour la transaction et la facture
                    $transaction->montant_payer += $montantAPayer;
                    if (($restePatient - $montantAPayer) <= 0) {
                        $invoice->patient_amount_status = 'paid';
                        $transaction->status = 'approved'; // Patient réglé
                    } else {
                        $invoice->patient_amount_status = 'pending';
                        $transaction->status = 'partial';
                    }

                    $invoice->save();
                    $transaction->save();

                    // Ajuster compte patient
                    $account = $patient->account;
                    $account->balance -= $montantAPayer;
                    $account->save();

                    $montantPatient -= $montantAPayer;
                }
            }

            // -------------------------
            // 2. Paiement Part Assurance
            // -------------------------
            $montantAssurance = (float) $request->montant_assurance;
            if ($montantAssurance > 0) {
                foreach ($transactions as $transaction) {
                    if ($montantAssurance <= 0) break;

                    $invoice = $transaction->invoice;
                    if (!$invoice) continue;

                    $resteAssurance = $invoice->insurance_amount - $this->getPaidAmount($invoice->id, 'ASSURANCE');
                    if ($resteAssurance <= 0) continue;

                    $montantAPayer = min($montantAssurance, $resteAssurance);

                    // Enregistrer le paiement assurance
                    $this->createPaiement($transaction, $patient, 'ASSURANCE', $montantAPayer, $request->description_assurance);

                    // Mettre à jour la facture
                    if (($resteAssurance - $montantAPayer) <= 0) {
                        $invoice->insurance_status = 'paid';
                    } else {
                        $invoice->insurance_status = 'pending';
                    }

                    $invoice->save();

                    $montantAssurance -= $montantAPayer;
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Paiement traité avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    /**
     * Crée un paiement
     */
    private function createPaiement($transaction, $patient, $source, $montant, $description)
    {
        Paiement::create([
            'user_id' => auth()->id(),
            'patient_id' => $patient->id,
            'transaction_id' => $transaction->id,
            'source' => strtoupper($source),
            'description' => $description,
            'montant' => $montant,
        ]);
    }

    /**
     * Récupère le montant déjà payé par type (patient ou assurance)
     */
    private function getPaidAmount($invoiceId, $type)
    {
        return Paiement::whereHas('transaction.invoice', function($q) use ($invoiceId) {
            $q->where('id', $invoiceId);
        })
        ->where('source', strtoupper($type))
        ->sum('montant');
    }


    
}
