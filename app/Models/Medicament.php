<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    use BelongsToEtablissement;

    protected $fillable = ['nom', 'forme', 'dosage', 'frequence', 'duree','amount', 'instructions', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    /**
     * Seuls les éléments proposés au choix (accueil, consultation, forfaits…).
     * NE PAS en faire un filtre global : les consultations et factures passées
     * doivent continuer à relire un élément masqué.
     */
    public function scopeActifs($query)
    {
        return $query->where($this->getTable() . '.actif', true);
    }

    
    public function coverage()
    {
        return $this->morphOne(InsuranceCoverage::class, 'coverageable');
    }
}
