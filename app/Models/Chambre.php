<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chambre extends Model
{
    protected $fillable = [
        'numero',
        'type',
        'prix_par_jour',
        'statut',
    ];

    public function hospitalisations(): HasMany {
        return $this->hasMany(Hospitalisation::class);
    }
}

