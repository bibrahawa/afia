<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Paiement;
use App\Models\InsuranceCoverage;
use App\Models\Consultation;
use App\Models\Account;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class ConsultationService
{
    public static function attachItems(Consultation $consultation, array $selectedItems)
    {
        foreach ($selectedItems as $category => $items) {
            $syncValues = [];

            foreach ($items as $item) {
                if ($category === "medicaments") {
                    // pivot avec quantité
                    $syncValues[$item['id']] = ['quantity' => $item['quantity']];
                } else {
                    // simple pivot
                    $syncValues[] = $item['id'];
                }
            }

            match ($category) {
                'medicaments' => $consultation->medicaments()->sync($syncValues),
                'services'    => $consultation->services()->sync($syncValues),
                'packages'    => $consultation->packages()->sync($syncValues),
                'examens'     => $consultation->tests()->sync($syncValues),
                default       => null
            };
        }
    }


    public static function mettreAJourCompte($owner_id, $owner_type, $montant, $type)
    {
        $account = Account::firstOrCreate([
            'owner_id' => $owner_id,
            'owner_type' => $owner_type,
        ]);

        if ($type === 'credit') {
            $account->balance += $montant;
        } elseif ($type === 'debit') {
            $account->balance -= $montant;
        }

        $account->save();

        return $account->id;
    }

    public static function appliedDiscount($transaction, $actes){

        $typeMap = [
            'Service' => 'App\\Models\\Service',
            'Test' => 'App\\Models\\Test',
            'Medicament' => 'App\\Models\\Medicament',
            'Package' => 'App\\Models\\Package',
            'Hospitalisation' => 'App\\Models\\Hospitalisation'
        ];

        \DB::transaction(function () use ($actes, $typeMap, $transaction) {
           
            $invoice = $transaction->invoice;
            $subTotal = 0;
            $insuranceTotal = 0;
            $patientTotal = 0;
            $discountTotal = 0;

            foreach ($actes as $type => $items) {
                $modelClass = $typeMap[$type] ?? null;

                if (!$modelClass) {
                    continue;
                }

                foreach ($items as $id => $discount) {
                    // Récupère la ligne
                    $invoiceItem = $invoice->items
                        ->where('coverage_type_type', $modelClass)
                        ->where('coverage_type_id', $id)
                        ->first();

                    if ($invoiceItem) {
                        // Calcul du nouveau patient_amount
                        $invoiceItem->discount = $discount;
                        $invoiceItem->save();
                        $invoiceItem->reCalculerApresReduction();
                    }

                    $discountTotal  += $invoiceItem->discount;
                    $subTotal       += $invoiceItem->total_amount;
                    $insuranceTotal += $invoiceItem->insurance_covered_amount;
                    $patientTotal   += $invoiceItem->patient_amount;
                    
                }
            }

            // Mise a jour de l'invoice
            $invoice->insurance_amount = $insuranceTotal;
            $invoice->patient_amount   = $patientTotal;
            $invoice->total_amount     = $subTotal;
            $invoice->save();

            // Mise à jour de la transaction
            $transaction->sub_total   = $subTotal + $discountTotal;
            $transaction->discount    = $discountTotal;
            $transaction->total       = $subTotal;
            $transaction->save();

            // Mettre à jour le statut de la transaction
            if ($transaction->montant_payer == $invoice->patient_amount && $invoice->assuinsurance_amount > 0){
                $transaction->status = 'approved';
            }else if($transaction->montant_payer == $transaction->total){
                $transaction->status = 'paid';
                $invoice->update([
                    'patient_amount_status' => 'paid'
                ]);
            }else{
                $transaction->status = 'partial';
            }
            $transaction->save();
            
            $account = $transaction->patient->account;
            $account->balance -= $discountTotal;
            $account->save();

        });

    }

    public static function calculateAmount(Consultation $consultation)
    {
        return
            $consultation->services->sum('amount') +
            $consultation->packages->sum('price') +
            $consultation->tests->sum('amount') +
            $consultation->medicaments->sum(function($med){
                return $med->pivot->quantity * $med->amount;
            });
    }

    public static function updateProchainRdvAppoitments($consultation, $nouveau_prochain_rdv)
    {
        DB::transaction(function () use ($consultation, $nouveau_prochain_rdv) {

            $ancien_prochain_rdv = $consultation->prochain_rdv;
            // Créer le rendez-vous
            Appointment::where('employee_id', $consultation->medecin_id)->where('patient_id', $consultation->patient_id)
                ->where('appointment_date', Carbon::parse($ancien_prochain_rdv)->format('Y-m-d'))
                ->where('appointment_time', Carbon::parse($ancien_prochain_rdv)->format('H:i:s'))
                ->update([
                    'appointment_date' => Carbon::parse($nouveau_prochain_rdv)->format('Y-m-d'),
                    'appointment_time' => Carbon::parse($nouveau_prochain_rdv)->format('H:i:s')
                ]);

                // Marquer le slot comme indisponible
                AppointmentSlot::where('employee_id', $consultation->medecin_id)
                    ->where('date', Carbon::parse($ancien_prochain_rdv)->format('Y-m-d'))
                    ->where('time', Carbon::parse($ancien_prochain_rdv)->format('H:i:s'))
                    ->update(['is_available' => true]);

                // Marquer le slot comme indisponible
                AppointmentSlot::where('employee_id', $consultation->medecin_id)
                    ->where('date', Carbon::parse($nouveau_prochain_rdv)->format('Y-m-d'))
                    ->where('time', Carbon::parse($nouveau_prochain_rdv)->format('H:i:s'))
                    ->update(['is_available' => false]);
        });
    }

    public function calculateAmountAndReturnItems($consultationItem, $isConsultation = true)
    {
        $items = [];
        $totalAmount = 0;
        $consultation = null;
        $transaction = null;

        if($isConsultation){
            $consultation = $consultationItem;
            $transaction = $consultationItem->transaction;
            
        }else{
            $consultation = $consultationItem->transactionable;
            $transaction = $consultationItem;
        }

        $insuranceId = $consultation->patient->activeInsurances()?->first()?->insurance_company_id;
        // Services
        foreach ($consultation->services ?? [] as $service) {

            $amount = $this->getAmount("App\\Models\\Service", $service->id, $insuranceId, $service);

            $items[] = [
                'acte_type'   => 'App\\Models\\Service',
                'acte_id'     => $service->id,
                'description' => $service->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        // Packages
        foreach ($consultation->packages ?? [] as $package) {
            $amount = $this->getAmount("App\\Models\\Package", $package->id, $insuranceId, $package);

            $items[] = [
                'acte_type'   => 'App\\Models\\Package',
                'acte_id'     => $package->id,
                'description' => $package->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        // Tests
        foreach ($consultation->tests ?? [] as $test) {
            $amount = $this->getAmount("App\\Models\\Test", $test->id, $insuranceId, $test);

            $items[] = [
                'acte_type'   => 'App\\Models\\Test',
                'acte_id'     => $test->id,
                'description' => $test->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        // Médicaments
        foreach ($consultation->medicaments ?? [] as $medicament) {
            $amount = $this->getAmount("App\\Models\\Medicament", $medicament->id, $insuranceId, $medicament);
            $items[] = [
                'acte_type'   => 'App\\Models\\Medicament',
                'acte_id'     => $medicament->id,
                'description' => $medicament->nom,
                'unit_price'  => $amount,
                'quantity'    => $medicament->pivot->quantity,
                'total'       => $amount * $medicament->pivot->quantity,
            ];

            $totalAmount += $amount * $medicament->pivot->quantity;
        }

        if($transaction?->transactionable_type == "App\\Models\\Hospitalisation"){
            
            $hospitalisations = [$transaction->transactionable];

            foreach ($hospitalisations as $hospitalisation) {
                                
                $items[] = [
                    'acte_type'   => 'App\\Models\\Chambre',
                    'acte_id'     => $hospitalisation->chambre_id,
                    'description' => $hospitalisation->date_entree." au ".$hospitalisation->date_sortie_effective,
                    'unit_price' => $this->getAmount("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                    'quantity'   => $hospitalisation->nombre_jours,
                    'total'     => $this->getAmountHospitalisation("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                ];

                $totalAmount += $hospitalisation->total_payer;
            }
        }

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    public static function calculateAmountWithAssurance(Consultation $consultation) {

        $actes = [];
        $totalAmount = 0;

        $insuranceId = $consultation->patient->activeInsurances()?->first()->insurance_company_id;

        // Services
        foreach ($consultation->services ?? [] as $item) {
            $totalAmount += getAmount("App\\Models\\Service", $item->id, $insuranceId, $item);
        }

        // Packages
        foreach ($consultation->packages ?? [] as $item) {
            $totalAmount += getAmount("App\\Models\\Package", $item->id, $insuranceId, $item);
        }

        // Tests
        foreach ($consultation->tests ?? [] as $item) {
            $totalAmount += getAmount("App\\Models\\Test", $item->id, $insuranceId, $item);
        }
        
        // Médicaments
        foreach ($consultation->medicaments ?? [] as $item) {
            $totalAmount += getAmount("App\\Models\\Medicament", $item->id, $insuranceId, $item) * $item->pivot->quantity;
        }

        return $totalAmount;
    }

    private function getConsultationAndHospitalisationItems($patient)
    {
        
        $transactions = $patient->getPendingAndPartialTransaction();
       
        $items = [];
        $totalAmount = 0;

        foreach ($transactions as $key => $transaction) {

            $consultation = $transaction->transactionable;

            if (!$consultation) continue;     
            
            // Services
            foreach ($consultation->services ?? [] as $service) {

                $items[$key][] = [
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

                $items[$key][] = [
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

                $items[$key][] = [
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

                $items[$key][] = [
                    'acte_type'   => 'App\\Models\\Medicament',
                    'acte_id'     => $medicament->id,
                    'description' => $medicament->nom,
                    'unit_price'  => $medicament->amount,
                    'quantity'    => $medicament->pivot->quantity,
                    'total'       => $medicament->amount * $medicament->pivot->quantity,
                ];

                $totalAmount += $medicament->amount * $medicament->pivot->quantity;
            }

            // Hospitalisation
            if($transaction->transactionable_type == "App\\Models\\Hospitalisation"){
                $hospitalisations = [$consultation];
                foreach ($hospitalisations as $hospitalisation) {

                    $items[$key][] = [
                        'acte_type'   => 'App\\Models\\Hospitalisation',
                        'acte_id'     => $hospitalisation->chambre_id,
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

    public function getActesAndHospitalisationFromPendingTransactions($patient)
    {
        $transactions = $patient->transactions->whereIn('status', ['pending', 'partial']);

        $actes = [];

        foreach ($transactions as $transaction) {

            $consultation = $transaction->transactionable;

            if (!$consultation) continue;

            // Services
            foreach ($consultation->services ?? [] as $item) {
                $actes[] = [
                        'id' => $item->id,
                        'type' => 'Service',
                        'nom' => $item->name,
                        'description' => $item->name,
                        'prix_unitaire' => $item->amount,
                        'quantite' => 1,
                        'created_at' => $item->created_at
                ];
            }

            // Packages
            foreach ($consultation->packages ?? [] as $item) {
                $actes[] = [
                        'id' => $item->id,
                        'type' => 'Package',
                        'nom' => $item->name,
                        'description' => $item->name,
                        'prix_unitaire' => $item->amount,
                        'quantite' => 1,
                        'created_at' => $item->created_at
                ];
            }

            // Tests
            foreach ($consultation->tests ?? [] as $item) {
                $actes[] = [
                        'id' => $item->id,
                        'type' => 'Test',
                        'nom' => $item->name,
                        'description' => $item->name,
                        'prix_unitaire' => $item->amount,
                        'quantite' => 1,
                        'created_at' => $item->created_at
                ];
            }

            // Médicaments
            foreach ($consultation->medicaments ?? [] as $item) {
                $actes[] = [
                        'id' => $item->id,
                        'type' => 'Medicament',
                        'nom' => $item->nom,
                        'description' => $item->nom,
                        'prix_unitaire' => $item->amount,
                        'quantite' => 1,
                        'created_at' => $item->created_at
                ];
            }

             // Hospitalisations
            if($transaction->transactionable_type == 'App\Models\Hospitalisation'){
                $hospitalisations = [$transaction->transactionable];
                foreach ($hospitalisations as $hospitalisation) {
                    $actes[] = [
                            'id' => $hospitalisation->chambre_id,
                            'type' => 'Hospitalisation',
                            'nom' => "Hospitalisation",
                            'description' => $hospitalisation->date_entree." au ".$hospitalisation->date_sortie_effective,
                            'prix_unitaire' => $this->getAmount("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                            'quantite' => $hospitalisation->nombre_jours,
                            'total' => $this->getAmountHospitalisation("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                            'created_at' => $hospitalisation->created_at
                    ];
                }
            }

        }

        return $actes;
    }

    public function getActes($transaction) {

        $actes = [];

        $consultation = $transaction->transactionable;

        $insuranceId = $transaction->patient->activeInsurances()?->first()?->insurance_company_id;

        // Services
        foreach ($consultation->services ?? [] as $item) {
            
            $actes[] = [
                    'id' => $item->id,
                    'type' => 'Service',
                    'nom' => $item->name,
                    'description' => $item->name,
                    'prix_unitaire' => $this->getAmount("App\\Models\\Service", $item->id, $insuranceId, $item),
                    'quantite' => 1,
                    'created_at' => $item->created_at
            ];
        }

        // Packages
        foreach ($consultation->packages ?? [] as $item) {
            $actes[] = [
                    'id' => $item->id,
                    'type' => 'Package',
                    'nom' => $item->name,
                    'description' => $item->name,
                    'prix_unitaire' => $this->getAmount("App\\Models\\Package", $item->id, $insuranceId, $item),
                    'quantite' => 1,
                    'created_at' => $item->created_at
            ];
        }

        // Tests
        foreach ($consultation->tests ?? [] as $item) {
            $actes[] = [
                    'id' => $item->id,
                    'type' => 'Test',
                    'nom' => $item->name,
                    'description' => $item->name,
                    'prix_unitaire' => $this->getAmount("App\\Models\\Test", $item->id, $insuranceId, $item),
                    'quantite' => 1,
                    'created_at' => $item->created_at
            ];
        }

        // Médicaments
        foreach ($consultation->medicaments ?? [] as $item) {
            $actes[] = [
                    'id' => $item->id,
                    'type' => 'Medicament',
                    'nom' => $item->nom,
                    'description' => $item->nom,
                    'prix_unitaire' => $this->getAmount("App\\Models\\Medicament", $item->id, $insuranceId, $item),
                    'quantite' => $item->pivot->quantity,
                    'created_at' => $item->created_at
            ];
        }

        // Hospitalisations
        if($transaction->transactionable_type == 'App\Models\Hospitalisation'){
            $hospitalisations = [$transaction->transactionable];
            foreach ($hospitalisations as $hospitalisation) {

                $actes[] = [
                        'id' => $hospitalisation->chambre_id,
                        'type' => 'Hospitalisation',
                        'nom' => "Hospitalisation",
                        'description' => $hospitalisation->date_entree." au ".$hospitalisation->date_sortie_effective,
                        'prix_unitaire' => $this->getAmount("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                        'quantite' => $hospitalisation->nombre_jours,
                        'total' => $this->getAmountHospitalisation("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                        'created_at' => $hospitalisation->created_at
                ];
            }
        }

        return $actes;
    }

    /**
     * Returns the amount of a service item covered by the insurance, if any.
     * Otherwise, returns the amount of the service item.
     *
     * @param string $serviceType The type of the service item.
     * @param int $serviceId The ID of the service item.
     * @param int $insuranceId The ID of the insurance company.
     * @param mixed $acte The service item.
     * @return float The amount of the service item covered by the insurance, if any. Otherwise, returns the amount of the service item.
     */
    public function getAmount($serviceType, $serviceId, $insuranceId, $acte){

        $item = $this->getCoverageItem($serviceType, $serviceId, $insuranceId);

        return (!empty($item))
                ? $item->acte_price 
                : $acte?->amount ?? $acte?->price ?? $acte->chambre->prix_par_jour;
    }
    
    private function getAmountHospitalisation($serviceType, $serviceId, $insuranceId, $acte){
        $item = $this->getCoverageItem($serviceType, $serviceId, $insuranceId);

        return (!empty($item))
                ? ($item->acte_price * $acte->nombre_jours) 
                : $acte->total_payer;
    }

    private function getCoverageItem($serviceType, $serviceId, $insuranceId){
        
        return InsuranceCoverage::where('insurance_company_id', $insuranceId)
                    ->where('coverageable_type', $serviceType)
                    ->where('coverageable_id', $serviceId)
                    ->where('status', 'active')
                    ->where('valid_from', '<=', now())
                    ->where(function($query) {
                        $query->whereNull('valid_to')
                            ->orWhere('valid_to', '>=', now());
                    })->first();
    }
}



