<?php

namespace App\Service;

use App\Models\Transaction;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Account;
use App\Models\PatientInsurance;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InsuranceClaim;
use Illuminate\Support\Facades\DB;


class InsuranceCalculationService{


    public function createInvoiceForConsulation($patientId, $transactionId, $consultation)
    {

        $itemCoverage = [];

        $items = $this->getConsultationItems($consultation);
            
        $calculation = $this->calculateInsuranceCoverage($patientId, $items['items']);

        // Créer la facture principale
        $invoice = Invoice::create([
            'transaction_id' => $transactionId,
            'insurance_company_id' => null,
            'total_amount' => $calculation['total_amount'],
            'patient_amount' => $calculation['patient_amount'],
            'insurance_amount' => $calculation['insurance_coverage'],
            'insurance_status' => $calculation['insurance_coverage'] > 0 ? 'pending' : null,
            'patient_amount_status' => 'pending'
        ]);

         // Créer les nouveaux items
            $details = $calculation['details'] ?: $items['items'];
            $data = $items['items'];
            foreach ($details as $index => $detail) {
                $source = array_merge($item ?? [], $detail);
                InvoiceItem::create([
                    'invoice_id'                   => $invoice->id,
                    'coverage_type_type'           => $source['acte_type'] ?? $data[$index]['acte_type'] ?? null,
                    'coverage_type_id'             => $source['acte_id'] ?? $data[$index]['acte_id'] ?? null,
                    'description'                  => $source['item_description'] ?? $source['description'] ?? '',
                    'unit_price'                   => (float)($source['unit_price'] ?? $data[$index]['unit_price'] ?? 0),
                    'quantity'                     => (int)($source['quantity'] ?? $data[$index]['quantity'] ?? 0),
                    'total_amount'                 => (float)($source['item_amount'] ?? $source['total'] ?? 0),
                    'insurance_covered_amount'     => (float)($source['insurance_amount'] ?? 0),
                    'patient_amount'               => (float)($source['patient_amount'] ?? $source['total'] ?? 0),
                    'coverage_percentage_applied'  => !empty($source['insurances_applied'])
                        ? $this->getAverageCoveragePercentage($source['insurances_applied'])
                        : 0
                ]);
            }

    }

    public function updateInvoiceForConsultation($patientId, $invoiceId, $consultation = null)
    {
        try {
            // Récupérer la facture existante
            $invoice = Invoice::findOrFail($invoiceId);
            
            // Si une consultation est fournie, recalculer les items
            if ($consultation) {
                $items = $this->getConsultationItems($consultation);
                $calculation = $this->calculateInsuranceCoverage($patientId, $items['items']);
                
                // Mettre à jour la facture principale
                $invoice->update([
                    'total_amount' => $calculation['total_amount'],
                    'patient_amount' => $calculation['patient_amount'],
                    'insurance_amount' => $calculation['insurance_coverage'],
                    'insurance_status' => $calculation['insurance_coverage'] > 0 ? 'pending' : null,
                ]);
                
                // Supprimer les anciens items
                InvoiceItem::where('invoice_id', $invoiceId)->delete();
                
               // Créer les nouveaux items
                $details = $calculation['details'] ?: $items['items'];
                $data = $items['items'];
                foreach ($details as $index => $detail) {
                    $source = array_merge($item ?? [], $detail);
                    InvoiceItem::create([
                        'invoice_id'                   => $invoice->id,
                        'coverage_type_type'           => $source['acte_type'] ?? $data[$index]['acte_type'] ?? null,
                        'coverage_type_id'             => $source['acte_id'] ?? $data[$index]['acte_id'] ?? null,
                        'description'                  => $source['item_description'] ?? $source['description'] ?? '',
                        'unit_price'                   => (float)($source['unit_price'] ?? $data[$index]['unit_price'] ?? 0),
                        'quantity'                     => (int)($source['quantity'] ?? $data[$index]['quantity'] ?? 0),
                        'total_amount'                 => (float)($source['item_amount'] ?? $source['total'] ?? 0),
                        'insurance_covered_amount'     => (float)($source['insurance_amount'] ?? 0),
                        'patient_amount'               => (float)($source['patient_amount'] ?? $source['total'] ?? 0),
                        'coverage_percentage_applied'  => !empty($source['insurances_applied'])
                            ? $this->getAverageCoveragePercentage($source['insurances_applied'])
                            : 0
                    ]);
                }
            }
            
            return $invoice->fresh();
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de la facture: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculer la couverture d'assurance pour une facture
     */
    public function calculateInsuranceCoverage($patientId, $items)
    {
        $activeInsurances = $this->getActivePatientInsurances($patientId);
                

        if ($activeInsurances->isEmpty()) {
            return [
                'total_amount' => array_sum(array_column($items, 'total')),
                'insurance_coverage' => 0,
                'patient_amount' => array_sum(array_column($items, 'total')),
                'insurances_used' => [],
                'details' => []
            ];
        }

        $totalAmount = 0;
        $totalInsuranceCoverage = 0;
        $insurancesUsed = [];
        $itemDetails = [];

        foreach ($items as $item) {
            $itemTotal = $item['total'];
            $totalAmount += $itemTotal;
            
            $itemCoverage = $this->calculateItemCoverage($item, $activeInsurances);
            $totalInsuranceCoverage += $itemCoverage['insurance_amount'];
            $itemDetails[] = $itemCoverage;
            
            // Collecter les assurances utilisées
            foreach ($itemCoverage['insurances_applied'] as $insurance) {
                if (!isset($insurancesUsed[$insurance['insurance_id']])) {
                    $insurancesUsed[$insurance['insurance_id']] = [
                        'insurance_id' => $insurance['insurance_id'],
                        'insurance_company' => $insurance['insurance_company'],
                        'policy_number' => $insurance['policy_number'],
                        'total_covered' => 0
                    ];
                }
                $insurancesUsed[$insurance['insurance_id']]['total_covered'] += $insurance['amount_covered'];
            }
        }

        return [
            'total_amount' => $totalAmount,
            'insurance_coverage' => $totalInsuranceCoverage,
            'patient_amount' => $totalAmount - $totalInsuranceCoverage,
            'insurances_used' => array_values($insurancesUsed),
            'details' => $itemDetails
        ];
    }

    /**
     * Calculer la couverture pour un item spécifique
     */
    private function calculateItemCoverage($item, $activeInsurances)
    {
        $itemAmount = $item['total'];
        $remainingAmount = $itemAmount;
        $totalCovered = 0;
        $insurancesApplied = [];

        // Trier les assurances par priorité (vous pourriez ajouter un champ priority)
        $sortedInsurances = $activeInsurances->sortBy('id');

        foreach ($sortedInsurances as $patientInsurance) {

            if ($remainingAmount <= 0) break;

            $coverage = $this->getCoverageForItem($item, $patientInsurance);
            
            if ($coverage) {
                // Recuperer le pourcentage de l'assurance du patient
                $coveragePercentage = $patientInsurance->coverage_percentage;
                // $coveragePercentage = $coverage->coverage_percentage;
                $maxAmount = $coverage->coverage_amount_limit;
            }else {
                // Utiliser le pourcentage par défaut de l'assurance
                // $coveragePercentage = $patientInsurance->insuranceCompany->default_coverage_percentage;
                $coveragePercentage = 0;
                $maxAmount = null;
            }

            if ($coveragePercentage > 0) {
                // Calculer le montant couvert par cette assurance
                $coveredAmount = ($remainingAmount * $coveragePercentage) / 100;
                
                // Appliquer les limites
                if ($maxAmount && $coveredAmount > $maxAmount) {
                    $coveredAmount = $maxAmount;
                } 

                // Vérifier le plafond annuel restant
                $remainingLimit = $patientInsurance->getRemainingLimit();
                if ($remainingLimit !== null && $coveredAmount > $remainingLimit) {
                    $coveredAmount = $remainingLimit;
                }

                if ($coveredAmount > 0) {
                    $totalCovered += $coveredAmount;
                    $remainingAmount -= $coveredAmount;

                    $insurancesApplied[] = [
                        'insurance_id' => $patientInsurance->id,
                        'insurance_company' => $patientInsurance->insuranceCompany->name,
                        'policy_number' => $patientInsurance->policy_number,
                        'coverage_percentage' => $coveragePercentage,
                        'amount_covered' => $coveredAmount,
                        'remaining_after' => $remainingAmount
                    ];
                }
            }
        }

        return [
            'item_description' => $item['description'],
            'item_amount' => $itemAmount,
            'insurance_amount' => $totalCovered,
            'patient_amount' => $remainingAmount,
            'insurances_applied' => $insurancesApplied
        ];
    }

    public function getConsultationItems($consultation)
    {

        $items = [];
        $actes = [];
        $totalAmount = 0;
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

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Obtenir les assurances actives d'un patient
     */
    private function getActivePatientInsurances($patientId)
    {
        return PatientInsurance::where('patient_id', $patientId)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })
            ->with('insuranceCompany')
            ->get();
    }

    /**
     * Obtenir la couverture spécifique pour un item
     */
    private function getCoverageForItem($item, $patientInsurance)
    {
        return $patientInsurance->insuranceCompany
            ->getCoverageForService($item['acte_type'], $item['acte_id']);
    }

    /**
     * Créer une facture avec couverture d'assurance
     */
    public function createInvoiceWithInsurance($patient, $insuranceCompanyIds = [])
    {

        DB::beginTransaction();

        $invoice = [];
        $data = $this->getConsultationAndHospitalisationAllItems($patient);

        foreach ($data as $key => $items) {
            
            foreach ($items as $key => $item) {

                try {
                    // Calculer la couverture
                    $calculation = $this->calculateInsuranceCoverage($patient->id, $item);

                    // Créer la facture principale
                    $invoice = Invoice::create([
                        'transaction_id' => $item['transaction_id'],
                        'insurance_company_id' => !empty($insuranceCompanyIds) ? $insuranceCompanyIds[0]['id'] : null,
                        'total_amount' => $calculation['total_amount'],
                        'patient_amount' => $calculation['patient_amount'],
                        'insurance_amount' => $calculation['insurance_coverage'],
                        'insurance_status' => $calculation['insurance_coverage'] > 0 ? 'pending' : null,
                        'patient_amount_status' => 'pending'
                    ]);


                    // Créer les item de facture
                    foreach ($calculation['details'] as $index => $detail) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'coverage_type_type' => $item['acte_type'],
                            'coverage_type_id' => $item['acte_id'],
                            'description' => $detail['item_description'],
                            'unit_price' => $item['unit_price'],
                            'quantity' => $item['quantity'],
                            'total_amount' => $detail['item_amount'],
                            'insurance_covered_amount' => $detail['insurance_amount'],
                            'patient_amount' => $detail['patient_amount'],
                            'coverage_percentage_applied' => $this->getAverageCoveragePercentage($detail['insurances_applied'])
                        ]);
                    }

                    // Mettre à jour les montants utilisés des assurances
                    foreach ($calculation['insurances_used'] as $insuranceUsed) {
                        $patientInsurance = PatientInsurance::find($insuranceUsed['insurance_id']);
                        if ($patientInsurance) {
                            $patientInsurance->increment('used_amount', $insuranceUsed['total_covered']);
                        }
                    }

                    // Créer les réclamations d'assurance si nécessaire
                    if ($calculation['insurance_coverage'] > 0) {
                        $this->createInsuranceClaims($invoice, $calculation['insurances_used'], $patient->id);
                    }

                    // Mettre à jour la transaction
                    $transaction = Transaction::find($item['transaction_id']);
                    
                    $totalAmount = $calculation['patient_amount'] + $calculation['insurance_coverage'];

                    if($totalAmount == $calculation['total_amount']){
                        $transaction->update(['status'=> 'approved']);
                    }else{
                        $transaction->update(['status'=> 'partial']);
                    }

                    DB::commit();
                    
                    
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
            }
        }
        
        return $invoice;
    }

    private function getConsultationAndHospitalisationAllItems($patient)
    {
        
        $transactions = $patient->getPendingAndPartialTransaction();
       
        $items = [];
        $totalAmount = 0;

        foreach ($transactions as $key => $transaction) {

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
                    'transaction_id'    => $transaction->id,
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
                    'transaction_id'    => $transaction->id,
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
                    'transaction_id'    => $transaction->id,
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
                    'transaction_id'    => $transaction->id,
                    'total'       => $medicament->amount,
                ];

                $totalAmount += $medicament->amount;
            }

            // Hospitalisation
            if($transaction->transactionable_type == "App\\Models\\Hospitalisation"){
                $hospitalisations = [$consultation];
                foreach ($hospitalisations as $hospitalisation) {

                    $items[] = [
                        'acte_type'     => 'App\\Models\\Hospitalisation',
                        'acte_id'       => $hospitalisation->id,
                        'description'   => $hospitalisation->date_entree." au ".$hospitalisation->date_sortie_effective,
                        'unit_price'    => $hospitalisation->chambre->prix_par_jour,
                        'quantity'      => $hospitalisation->nombre_jours,
                        'transaction_id'=> $transaction->id,
                        'total'         => $hospitalisation->total_payer,
                    ];

                    $totalAmount += $hospitalisation->total_payer;
                }
            }
        
        }

        return [
            'items' => collect($items)->groupBy('transaction_id')->toArray(),
            'total_amount' => $totalAmount,
        ];

    }

    // private function createInvoiceAndItemInvoice(){
    //     try {
    //         // Calculer la couverture
    //         $calculation = $this->calculateInsuranceCoverage($patientId, $items);

    //         // Créer la facture principale
    //         $invoice = Invoice::create([
    //             'transaction_id' => $transaction->id,
    //             'insurance_company_id' => !empty($insuranceCompanyIds) ? $insuranceCompanyIds[0]['id'] : null,
    //             'total_amount' => $calculation['total_amount'],
    //             'patient_amount' => $calculation['patient_amount'],
    //             'insurance_amount' => $calculation['insurance_coverage'],
    //             'insurance_status' => $calculation['insurance_coverage'] > 0 ? 'pending' : null,
    //             'patient_amount_status' => 'pending'
    //         ]);

    //         $totalAmount = $calculation['patient_amount'] + $calculation['insurance_coverage'];

    //         if($totalAmount == $calculation['total_amount']){
    //             $transaction->status == "approved";
    //         }else{
    //             $transaction->status == "partial";
    //         }
        
    //         $transaction->update();

    //         if($calculation['patient_amount'])

    //         // Créer les items de facture
    //         foreach ($calculation['details'] as $index => $detail) {
    //             InvoiceItem::create([
    //                 'invoice_id' => $invoice->id,
    //                 'coverage_type_type' => $items[$index]['acte_type'],
    //                 'coverage_type_id' => $items[$index]['acte_id'],
    //                 'description' => $detail['item_description'],
    //                 'unit_price' => $items[$index]['unit_price'],
    //                 'quantity' => $items[$index]['quantity'],
    //                 'total_amount' => $detail['item_amount'],
    //                 'insurance_covered_amount' => $detail['insurance_amount'],
    //                 'patient_amount' => $detail['patient_amount'],
    //                 'coverage_percentage_applied' => $this->getAverageCoveragePercentage($detail['insurances_applied'])
    //             ]);
    //         }

    //         // Mettre à jour les montants utilisés des assurances
    //         foreach ($calculation['insurances_used'] as $insuranceUsed) {
    //             $patientInsurance = PatientInsurance::find($insuranceUsed['insurance_id']);
    //             if ($patientInsurance) {
    //                 $patientInsurance->increment('used_amount', $insuranceUsed['total_covered']);
    //             }
    //         }

    //         // Créer les réclamations d'assurance si nécessaire
    //         if ($calculation['insurance_coverage'] > 0) {
    //             $this->createInsuranceClaims($invoice, $calculation['insurances_used'], $patientId);
    //         }

    //         DB::commit();
    //         return $invoice;
            
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         throw $e;
    //     }
    // }

    /**
     * Créer les réclamations d'assurance
     */
    private function createInsuranceClaims($invoice, $insurancesUsed, $patientId)
    {
        foreach ($insurancesUsed as $insuranceData) {
            InsuranceClaim::create([
                'claim_number' => $this->generateClaimNumber(),
                'invoice_id' => $invoice->id,
                'insurance_company_id' => $insuranceData['insurance_id'],
                'patient_id' => $patientId,
                'claimed_amount' => $insuranceData['total_covered'],
                'status' => 'draft'
            ]);
        }
    }

    /**
     * Générer un numéro de réclamation unique
     */
    private function generateClaimNumber()
    {
        return 'CLM-' . date('Ymd') . '-' . str_pad(InsuranceClaim::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculer le pourcentage moyen de couverture appliqué
     */
    private function getAverageCoveragePercentage($insurancesApplied)
    {
        if (empty($insurancesApplied)) return null;
        
        $totalPercentage = array_sum(array_column($insurancesApplied, 'coverage_percentage'));
        return $totalPercentage / count($insurancesApplied);
    }

}