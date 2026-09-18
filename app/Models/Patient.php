<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSanteIdentite;

class Patient extends Model
{

    use HasSanteIdentite;

	protected $fillable =
	[
        'first_name', 'middle_name', 'last_name', 'age', 'gender', 'birth_date', 'country', 'state', 'district' , 'location' , 'occupation' ,
        'description' , 'relative_name' , 'relative_phone' , 'marital_status', 'blood_group','user_id'
    ];


    /**
     * Le patient est GLOBAL à la plateforme : il n'a pas de global scope.
     * Toute LISTE de patients côté établissement doit donc passer par ce
     * scope, sinon une clinique verrait les patients de toutes les autres.
     *
     *   Patient::suivisParEtablissement()->orderBy('last_name')->get();
     */
    public function scopeSuivisParEtablissement($query, ?int $etablissementId = null)
    {
        $etablissementId ??= \App\Support\EtablissementContext::id();

        if (! $etablissementId) {
            return \App\Support\EtablissementContext::estAdministrateurPlateforme() ? $query : $query->whereRaw('1 = 0');
        }

        return $query->whereExists(fn ($q) => $q->selectRaw('1')->from('etablissement_patient as ep')
            ->whereColumn('ep.patient_id', 'patients.id')
            ->where('ep.etablissement_id', $etablissementId));
    }

    public function etablissements()
    {
        return $this->belongsToMany(Etablissement::class, 'etablissement_patient')
            ->withPivot(['premiere_visite_le', 'derniere_visite_le'])->withTimestamps();
    }

    public function reports()
    {
        return $this->hasMany('App\Models\Report');
    }

    public function paiements(){
        return $this->hasMany('App\Models\Paiement');
    }

    public function account()
    {
        return $this->morphOne(Account::class, 'owner');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getPendingAndPartialTransaction(){
        return $this->transactions()->whereIn('status', ['pending', 'partial'])->with('transactionable')->get();
    }

    public function files()
    {
        return $this->hasMany(FichierPatient::class);
    }

    public function antecedant()
    {
        return $this->hasOne(Antecedent::class);
    }

    public function hospitalisations()
    {
        return $this->hasMany(Hospitalisation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullName(){
        return $this->first_name." ".$this->last_name;
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    /** Employeurs (module Assurance). */
    public function emplois()
    {
        return $this->hasMany(\App\Models\Assurance\PatientEmploi::class);
    }

    /** Couvertures du référentiel : comme adhérent, conjoint, enfant… */
    public function beneficiairesAssurance()
    {
        return $this->hasMany(\App\Models\Assurance\Beneficiaire::class);
    }

    // Nouvelles relations pour les assurances
    public function patientInsurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }

    public function activeInsurances()
    {
        return $this->hasMany(PatientInsurance::class)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            });
    }

    public function getTauxCoverage($patientId)
    {
        return PatientInsurance::where('patient_id', $patientId)
                                ->where('status', 'active')
                                ->where('start_date', '<=', now())
                                ->whereNull('end_date')
                                ->orWhere('end_date', '>=', now())->first()->coverage_percentage;
    }

    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, Transaction::class);
    }

    public function insuranceClaims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    // Méthodes utilitaires pour les assurances
    public function hasActiveInsurance()
    {
        return $this->activeInsurances()->exists();
    }

    public function getActiveInsurancesList()
    {
        return $this->activeInsurances()
            ->with('insuranceCompany')
            ->get()
            ->map(function($insurance) {
                return [
                    'id' => $insurance->id,
                    'company_name' => $insurance->insuranceCompany->name,
                    'policy_number' => $insurance->policy_number,
                    'coverage_percentage' => $insurance->insuranceCompany->default_coverage_percentage,
                    'remaining_limit' => $insurance->getRemainingLimit()
                ];
            });
    }

    public function getTotalInsuranceCoverage($amount)
    {
        $activeInsurances = $this->activeInsurances()->with('insuranceCompany')->get();
        $totalCoverage = 0;
        $remainingAmount = $amount;

        foreach ($activeInsurances as $insurance) {
            if ($remainingAmount <= 0) break;

            $coveragePercentage = $insurance->insuranceCompany->default_coverage_percentage;
            $coveredAmount = ($remainingAmount * $coveragePercentage) / 100;

            // Vérifier le plafond restant
            $remainingLimit = $insurance->getRemainingLimit();
            if ($remainingLimit !== null && $coveredAmount > $remainingLimit) {
                $coveredAmount = $remainingLimit;
            }

            $totalCoverage += $coveredAmount;
            $remainingAmount -= $coveredAmount;
        }

        return [
            'total_coverage' => $totalCoverage,
            'patient_amount' => $remainingAmount,
            'coverage_percentage' => $amount > 0 ? ($totalCoverage / $amount) * 100 : 0
        ];
    }

    // Accesseurs
    public function getFullNameAttribute()
    {
        // preg_replace : sans deuxième prénom, la concaténation laissait un
        // double espace, visible partout — écrans, ordonnances, SMS.
        return preg_replace('/\s+/u', ' ', trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name));
    }

    public function getFullAddressAttribute()
    {
        return $this->district . '/' . $this->location;
    }

    /**
     * CORRIGÉ — lisait une colonne `date_of_birth` inexistante et renvoyait
     * toujours null, en masquant au passage la vraie colonne `age`.
     *
     * Âge en années révolues : calculé depuis `birth_date` (texte libre)
     * quand il est lisible, sinon la colonne `age` saisie à la main.
     */
    public function getAgeAttribute($valeurColonne = null): ?int
    {
        if ($naissance = $this->dateNaissance()) {
            return (int) $naissance->diffInYears(now());
        }

        return is_numeric($valeurColonne) ? (int) $valeurColonne : null;
    }

    /** Date de naissance exploitable, ou null (texte illisible, date future). */
    public function dateNaissance(): ?\Carbon\Carbon
    {
        if (empty($this->birth_date)) {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($this->birth_date);
        } catch (\Throwable) {
            return null;
        }

        return $date->isFuture() ? null : $date;
    }

    /** « 3 ans », « 8 mois », « 12 jours » — pour les en-têtes et les étiquettes. */
    public function getAgeTexteAttribute(): string
    {
        $naissance = $this->dateNaissance();

        if (! $naissance) {
            return $this->age !== null ? "{$this->age} ans" : 'Âge non renseigné';
        }

        return match (true) {
            ($ans = (int) $naissance->diffInYears(now())) >= 2 => "{$ans} ans",
            ($mois = (int) $naissance->diffInMonths(now())) >= 1 => "{$mois} mois",
            default => (int) $naissance->diffInDays(now()) . ' jours',
        };
    }

    // Scopes
    public function scopeWithActiveInsurances($query)
    {
        return $query->with(['activeInsurances.insuranceCompany']);
    }

    public function scopeHasInsurance($query)
    {
        return $query->whereHas('activeInsurances');
    }

    public function scopeWithoutInsurance($query)
    {
        return $query->whereDoesntHave('activeInsurances');
    }

}
