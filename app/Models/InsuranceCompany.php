<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceCompany extends Model
{
    protected $fillable = [
        'name', 'code', 'contact_person', 'phone', 'email', 'address',
        'contract_start_date', 'contract_end_date', 'status',
        'default_coverage_percentage', 'notes'
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'default_coverage_percentage' => 'decimal:2'
    ];

    public function coverages()
    {
        return $this->hasMany(InsuranceCoverage::class);
    }

    public function patientInsurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    // Vérifier si l'assurance est active
    public function isActive()
    {
        return $this->status === 'active' && 
               (!$this->contract_end_date || $this->contract_end_date >= now());
    }

    // Obtenir la couverture pour un type de service spécifique
    public function getCoverageForService($serviceType, $serviceId)
    {
        return $this->coverages()
            ->where('coverageable_type', $serviceType)
            ->where('coverageable_id', $serviceId)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where(function($query) {
                $query->whereNull('valid_to')
                      ->orWhere('valid_to', '>=', now());
            })
            ->first();
    }
}

