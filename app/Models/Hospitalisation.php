<?php

// app/Models/Hospitalisation.php
namespace App\Models;

use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use App\Traits\RattachePatientEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Hospitalisation extends Model
{
    use HeriteEtablissement, BelongsToEtablissement, RattachePatientEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['chambre_id' => Chambre::class];

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

