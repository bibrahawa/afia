<?php

namespace App\Models;

use App\Enums\PorteeAcces;
use Illuminate\Database\Eloquent\Model;

class Consentement extends Model
{
    protected $fillable = [
        'patient_id', 'beneficiaire_type', 'beneficiaire_id',
        'portee', 'statut', 'accorde_le', 'expire_le', 'revoque_le', 'demande_acces_id',
    ];

    protected $casts = [
        'portee' => 'array',
        'accorde_le' => 'datetime',
        'expire_le' => 'datetime',
        'revoque_le' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function beneficiaire()
    {
        return $this->morphTo();
    }

    public function demandeAcces()
    {
        return $this->belongsTo(DemandeAcces::class);
    }

    public function estActif(): bool
    {
        return $this->statut === 'actif'
            && (! $this->expire_le || $this->expire_le->isFuture());
    }

    public function couvre(PorteeAcces $portee): bool
    {
        return $this->estActif()
            && (in_array($portee->value, $this->portee, true)
                || in_array(PorteeAcces::CarnetComplet->value, $this->portee, true));
    }
}
