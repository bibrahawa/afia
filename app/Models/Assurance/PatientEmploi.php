<?php

namespace App\Models\Assurance;

use App\Models\Patient;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Rattachement d'un patient à son employeur (matricule, poste, période). */
class PatientEmploi extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['entreprise_id' => Entreprise::class];

    protected $table = 'patient_emplois';

    protected $fillable = ['etablissement_id', 'patient_id', 'entreprise_id', 'matricule', 'poste', 'date_debut', 'date_fin'];

    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>=', today()));
    }

    public function estEnCours(): bool
    {
        return $this->date_fin === null || $this->date_fin->gte(today());
    }
}
