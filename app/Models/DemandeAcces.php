<?php

namespace App\Models;

use App\Enums\PorteeAcces;
use Illuminate\Database\Eloquent\Model;

class DemandeAcces extends Model
{
    protected $table = 'demandes_acces';

    protected $fillable = [
        'patient_id', 'demandeur_type', 'demandeur_id', 'etablissement_id',
        'portee_demandee', 'canal_confirmation', 'code_confirmation', 'motif',
        'statut', 'expire_le',
    ];

    protected $casts = [
        'portee_demandee' => 'array', // tableau de valeurs PorteeAcces::value
        'expire_le' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function demandeur()
    {
        return $this->morphTo();
    }

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function consentement()
    {
        return $this->hasOne(Consentement::class);
    }

    public function portees(): array
    {
        return array_map(fn ($v) => PorteeAcces::from($v), $this->portee_demandee);
    }
}
