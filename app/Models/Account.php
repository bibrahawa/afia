<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Account extends Model
{
    use BelongsToEtablissement;

    protected $fillable = [
        'etablissement_id',
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
