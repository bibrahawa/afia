<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Patient;
use InvalidArgumentException;

class PatientAccountService
{
    public function getOrCreate(Patient $patient): Account
    {
        return Account::firstOrCreate([
            'owner_id' => $patient->id,
            'owner_type' => Patient::class,
        ]);
    }

    public function credit(Patient $patient, float $amount): Account
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Le montant à créditer ne peut pas être négatif.');
        }

        $account = $this->getOrCreate($patient);
        $account->balance += $amount;
        $account->save();

        return $account;
    }

    public function debit(Patient $patient, float $amount): Account
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Le montant à débiter ne peut pas être négatif.');
        }

        $account = $this->getOrCreate($patient);
        $account->balance -= $amount;
        $account->save();

        return $account;
    }

    public function adjust(Patient $patient, float $amount, string $direction): Account
    {
        return match ($direction) {
            'credit' => $this->credit($patient, $amount),
            'debit' => $this->debit($patient, $amount),
            default => throw new InvalidArgumentException('Direction invalide.'),
        };
    }
}