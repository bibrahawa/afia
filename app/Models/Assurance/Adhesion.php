<?php

namespace App\Models\Assurance;

use App\Enums\Assurance\LienBeneficiaire;
use App\Enums\Assurance\StatutCouverture;
use App\Models\Patient;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Assuré principal d'un contrat : carte d'assuré, formule, emploi, période. */
class Adhesion extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['formule_id' => Formule::class];

    protected $table = 'assurance_adhesions';

    protected $fillable = [
        'etablissement_id', 'formule_id', 'patient_id', 'patient_emploi_id', 'numero_carte',
        'date_debut', 'date_fin', 'statut', 'reprise_patient_insurance_id', 'notes',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'statut' => StatutCouverture::class,
    ];

    protected static function booted(): void
    {
        static::saved(fn (Adhesion $adhesion) => app(\App\Services\Assurance\ProjectionCouvertureService::class)->pourAdhesion($adhesion));
    }

    public function formule()
    {
        return $this->belongsTo(Formule::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function emploi()
    {
        return $this->belongsTo(PatientEmploi::class, 'patient_emploi_id');
    }

    public function beneficiaires()
    {
        return $this->hasMany(Beneficiaire::class);
    }

    public function ayantsDroit()
    {
        return $this->beneficiaires()->where('lien', '!=', LienBeneficiaire::Adherent->value);
    }
}
