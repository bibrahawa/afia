<?php

namespace App\Service;

use App\Models\Transaction;
use App\Models\Paiement;
use App\Models\Consultation;
use App\Models\Account;
use Illuminate\Support\Facades\DB;


class ConsultationService
{
    public static function attachItems(Consultation $consultation, array $selectedItems, array $billingStatus)
    {
        foreach ($selectedItems as $category => $items) {
            $syncValues = [];
            foreach ($items as $item) {
                $prefix = $category === 'examens' ? 'examen' : rtrim($category, 's');
                $billingKey = $prefix . '-' . $item['value'];
                $syncValues[$item['value']] = [
                    'facturer' => $billingStatus[$billingKey] ?? false
                ];
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

    public static function calculateAmount(Consultation $consultation)
    {
        return
            $consultation->services->sum('amount') +
            $consultation->packages->sum('price') +
            $consultation->tests->sum('amount') +
            $consultation->medicaments->sum('amount');
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
                    'quantity'    => 1,
                    'total'       => $medicament->amount,
                ];

                $totalAmount += $medicament->amount;
            }

            // Hospitalisation
            if($transaction->transactionable_type == "App\\Models\\Hospitalisation"){
                $hospitalisations = [$consultation];
                foreach ($hospitalisations as $hospitalisation) {

                    $items[$key][] = [
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
                $items = [$consultation];
                foreach ($items as $item) {
                    $actes[] = [
                            'id' => $item->id,
                            'type' => 'Hospitalisation',
                            'nom' => "Hospitalisation",
                            'description' => $item->date_entree." au ".$item->date_sortie_effective,
                            'prix_unitaire' => $item->chambre->prix_par_jour,
                            'quantite' => $item->nombre_jours,
                            'total' => $item->total_payer,
                            'created_at' => $item->created_at
                    ];
                }
            }

        }

        return $actes;
    }
}



