<?php

namespace App\Service;

use App\Models\Consultation;
use App\Models\Transaction;
use App\Models\Paiement;
use App\Models\Account;
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
}
