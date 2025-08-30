<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Paiement;
use App\Models\Account;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function Paiement(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Étape 1 : Créer une transaction liée à la consultation
            $transaction = Transaction::create([
                'consultation_id' => $data['consultation_id'],
                'montant' => $data['total'],
                'type' => 'credit', // ou 'debit' selon le contexte
                'source' => $data['mode_paiement'],
                'status' => 'valide', // ou en attente, annulée, etc.
                'description' => $data['description'] ?? null,
            ]);

            // Étape 2 : Enregistrer le paiement
            $paiement = Paiement::create([
                'user_id' => $data['user_id'],
                'patient_id' => $data['patient_id'],
                'consultation_id' => $data['consultation_id'],
                'transaction_id' => $transaction->id,
                'mode_paiement' => $data['mode_paiement'],
                'invoice_no' => $this->generateInvoiceNumber(),
                'description' => $data['description'] ?? null,
                'sub_total' => $data['sub_total'],
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'total' => $data['total'],
                'payer' => true,
            ]);

            // Étape 3 : Mettre à jour le compte du patient
            $this->mettreAJourCompte($data['patient_id'], 'App\Models\Patient', $data['total'], 'credit');

            return $paiement;
        });
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

    private function generateInvoiceNumber()
    {
        $latest = Paiement::latest()->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        return 'FAC-' . date('Y') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    public function paiementTransaction($source, $amount, $description, $transaction, $patient_amount)
    {
        $montant = $amount;

        if ($montant == 0 || $montant == null || $montant < 0) {
            return redirect()->back()->with('error', 'Please enter a valid amount.');
        }

        if ($montant > 0) {
            $deja_payer = $transaction->montant_payer;
            $total = $transaction->total;

            if ($deja_payer < $total) {
                $montantRestant = $total - $deja_payer;
                if ($montant >= $montantRestant) {
                    $transaction->montant_payer += $montantRestant;
                    $deja_payer = $montantRestant;
                    $montant -= $montantRestant;
                }else{
                        $transaction->montant_payer += $montant;
                        $deja_payer = $montant;
                        $montant = 0;
                    }

                // Calculer le montant réellement payé pour cette transaction
                $montantPayePourCetteTransaction = ($montant >= $montantRestant) ? $montantRestant : $deja_payer;

                if ($transaction->save()) {
                    $paiement = new Paiement();
                    $paiement->user_id = auth()->user()->id;
                    $paiement->patient_id = $transaction->patient->id;
                    $paiement->transaction_id = $transaction->id;
                    $paiement->source = $source;
                    $paiement->description = $description ?? "Paiement de la consultation par le patient";
                    $paiement->montant = $montantPayePourCetteTransaction; // Montant réel pour cette transaction

                    if ($paiement->save()) {
                        // Mettre à jour le statut de la transaction
                        if ($transaction->montant_payer == $patient_amount){
                            $transaction->status = 'approved';
                        }else if($transaction->montant_payer == $transaction->total){
                            $transaction->status = 'paid';
                            $transaction->invoice->update([
                                'patient_amount_status' => 'paid'
                            ]);
                        }else{
                            $transaction->status = 'partial';
                        }
                        $transaction->save();
                    }
                }
            }
        }

        // Déduire le montant total payé du solde du compte (une seule fois à la fin)
        $montantTotalPaye = $amount - $montant; // Ce qui reste dans $montant n'a pas été utilisé
        if ($montantTotalPaye > 0) {
            $account = $transaction->patient->account;
            $account->balance -= $montantTotalPaye;
            $account->save();
        }

        return $transaction;
    }

    public function paiementPartPatientOrPartInsurance($patient, $source, $amountPatient, $amountInsurance, $descriptionPatient = null, $descriptionInsurance = null)
    {
        if (($amountPatient <= 0 && $amountInsurance <= 0)) {
            return redirect()->back()->with('error', 'Please enter a valid amount.');
        }

        $transactions = $patient->getPendingAndPartialTransaction();

        /** --------------------
         *  1. PAIEMENT PATIENT
         *  -------------------- */
        $montantPatient = $amountPatient;
        foreach ($transactions as $transaction) {
            if ($montantPatient <= 0) break;

            $dejaPayePatient = $transaction->patient_amount_paid ?? 0;
            $partPatient = $transaction->patient_amount ?? $transaction->total;
            $restantPatient = $partPatient - $dejaPayePatient;

            if ($restantPatient > 0) {
                $montantAPayer = min($montantPatient, $restantPatient);

                $transaction->patient_amount_paid = $dejaPayePatient + $montantAPayer;
                $transaction->montant_payer = ($transaction->montant_payer ?? 0) + $montantAPayer;
                $transaction->save();

                // Enregistrer le paiement patient
                $paiement = new Paiement();
                $paiement->user_id = auth()->id();
                $paiement->patient_id = $patient->id;
                $paiement->transaction_id = $transaction->id;
                $paiement->source = $source;
                $paiement->description = $descriptionPatient ?? "Paiement part patient";
                $paiement->montant = $montantAPayer;
                $paiement->save();

                $montantPatient -= $montantAPayer;
            }
        }

        /** --------------------
         *  2. PAIEMENT ASSURANCE
         *  -------------------- */
        $montantAssurance = $amountInsurance;
        foreach ($transactions as $transaction) {
            if ($montantAssurance <= 0) break;

            $dejaPayeAssurance = $transaction->insurance_amount_paid ?? 0;
            $partAssurance = $transaction->insurance_amount ?? 0;
            $restantAssurance = $partAssurance - $dejaPayeAssurance;

            if ($restantAssurance > 0) {
                $montantAPayer = min($montantAssurance, $restantAssurance);

                $transaction->insurance_amount_paid = $dejaPayeAssurance + $montantAPayer;
                $transaction->montant_payer = ($transaction->montant_payer ?? 0) + $montantAPayer;
                $transaction->save();

                // Enregistrer le paiement assurance
                $paiement = new Paiement();
                $paiement->user_id = auth()->id();
                $paiement->patient_id = $patient->id;
                $paiement->transaction_id = $transaction->id;
                $paiement->source = "ASSURANCE"; // On peut mettre une constante
                $paiement->description = $descriptionInsurance ?? "Paiement part assurance";
                $paiement->montant = $montantAPayer;
                $paiement->save();

                $montantAssurance -= $montantAPayer;
            }
        }

        /** --------------------
         *  3. MISE À JOUR STATUTS
         *  -------------------- */
        foreach ($transactions as $transaction) {
            $patientOk = ($transaction->patient_amount_paid >= ($transaction->patient_amount ?? 0));
            $assuranceOk = ($transaction->insurance_amount_paid >= ($transaction->insurance_amount ?? 0));

            if ($patientOk && $assuranceOk) {
                $transaction->status = 'approved';
            } elseif ($patientOk && !$assuranceOk) {
                $transaction->status = 'waiting_insurance';
            } elseif (!$patientOk && $assuranceOk) {
                $transaction->status = 'waiting_patient';
            } else {
                $transaction->status = 'partial';
            }
            $transaction->save();
        }

        /** --------------------
         *  4. DÉDUCTION SOLDE PATIENT
         *  -------------------- */
        $montantTotalPatientPaye = $amountPatient - $montantPatient;
        if ($montantTotalPatientPaye > 0) {
            $account = $patient->account;
            $account->balance -= $montantTotalPatientPaye;
            $account->save();
        }

        return $transactions;
    }

}
