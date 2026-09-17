<?php

namespace App\Models\Assurance;

use App\Enums\Assurance\LienBeneficiaire;
use App\Enums\Assurance\StatutCouverture;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Personne couverte par une adhésion : l'adhérent lui-même, ses conjoints
 * (plusieurs possibles), ses enfants, un autre ayant droit.
 */
class Beneficiaire extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['adhesion_id' => Adhesion::class];

    protected $table = 'assurance_beneficiaires';

    protected $fillable = [
        'etablissement_id', 'adhesion_id', 'patient_id', 'lien', 'etudiant',
        'numero_carte', 'date_debut', 'date_fin', 'statut',
    ];

    protected $casts = [
        'lien' => LienBeneficiaire::class,
        'statut' => StatutCouverture::class,
        'etudiant' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(fn (Beneficiaire $b) => app(\App\Services\Assurance\ProjectionCouvertureService::class)->synchroniser($b));
    }

    public function adhesion()
    {
        return $this->belongsTo(Adhesion::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function prisesEnCharge()
    {
        return $this->hasMany(PriseEnCharge::class);
    }

    /** Ligne lue par le moteur de calcul actuel (pont jusqu'à l'étape 2b). */
    public function projection()
    {
        return $this->hasOne(PatientInsurance::class, 'beneficiaire_id');
    }

    /**
     * Dernier jour de couverture lié à l'âge (enfants), ou null si la date de
     * naissance est inconnue ou si le lien n'est pas « enfant ».
     */
    public function finDroitsParAge(?Formule $formule = null): ?Carbon
    {
        if ($this->lien !== LienBeneficiaire::Enfant) {
            return null;
        }

        $naissance = self::dateNaissance($this->patient);
        $formule ??= $this->adhesion?->formule;

        if (! $naissance || ! $formule) {
            return null;
        }

        $ageMax = $this->etudiant ? $formule->age_max_enfant_etudiant : $formule->age_max_enfant;

        // Couvert jusqu'à la veille de ses (âge max + 1) ans.
        return $naissance->copy()->addYears($ageMax + 1)->subDay();
    }

    /** patients.birth_date est une chaîne libre : formats courants tolérés. */
    public static function dateNaissance(?Patient $patient): ?Carbon
    {
        $valeur = trim((string) $patient?->birth_date);

        if ($valeur === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $date = rescue(fn () => Carbon::createFromFormat('!' . $format, $valeur), null, false);
            if ($date && $date->format($format) === $valeur) {
                return $date;
            }
        }

        return null;
    }
}
