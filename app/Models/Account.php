<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use App\Traits\Facturation\NormaliseTypesFacturables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Account extends Model
{
    use BelongsToEtablissement, NormaliseTypesFacturables;

    /** Enregistré sous alias stable (« patient »), voir TypesFacturables. */
    protected static array $colonnesTypesFacturables = ['owner_type'];

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
