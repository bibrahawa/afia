<?php

// app/Models/Hospitalisation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Hospitalisation extends Model
{
    protected $fillable = [
        'patient_id',
        'chambre_id',
        'date_entree',
        'nombre_jours',
        'date_sortie_prevue',
        'date_sortie_effective',
        'statut',
        'observation',
    ];

    public function patient(): BelongsTo {
        return $this->belongsTo(Patient::class);
    }

    public function chambre(): BelongsTo {
        return $this->belongsTo(Chambre::class);
    }

    public function getDateSortiePrevueAttribute($value) {
        return Carbon::parse($value)->format('d/m/Y');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }
}

