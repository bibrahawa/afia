<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'owner_id',
        'owner_type',
        'balance',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    // Méthodes pratiques
    public function credit($amount)
    {
        $this->balance += $amount;
        $this->save();
    }

    public function debit($amount)
    {
        $this->balance -= $amount;
        $this->save();
    }
}
