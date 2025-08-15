<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{


	protected $fillable =
	[
        'first_name', 'middle_name', 'last_name', 'age', 'gender', 'birth_date', 'country', 'state', 'district' , 'location' , 'occupation' ,
        'description' , 'relative_name' , 'relative_phone' , 'marital_status', 'blood_group','user_id'
    ];


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

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
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
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function getFullAddressAttribute()
    {
        return $this->district . '/' . $this->location;
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
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
