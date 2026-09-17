<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chambre extends Model
{
    use BelongsToEtablissement;

    protected $fillable = [
        'numero',
        'type',
        'prix_par_jour',
        'statut',
    ];

    public function hospitalisations(): HasMany {
        return $this->hasMany(Hospitalisation::class);
    }

    public function coverage()
    {
        return $this->morphOne(InsuranceCoverage::class, 'coverageable');
    }

    public function getFullNameAttribute()
    {
        return $this->type . '-' . $this->numero;
    }
}

