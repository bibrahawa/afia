<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceCompany extends Model
{
    use BelongsToEtablissement;

    protected $fillable = [
        'name', 'code', 'contact_person', 'phone', 'email', 'address',
        'contract_start_date', 'contract_end_date', 'status',
        'default_coverage_percentage', 'notes'
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'default_coverage_percentage' => 'decimal:2',
    ];


    public function pendingInvoices()
    {
        return $this->hasMany(Invoice::class)->where('insurance_status', 'pending');
    }

    public function settlements()
    {
        return $this->hasMany(InsuranceSettlement::class);
    }

    public function approvedInvoices()
    {
        return $this->hasMany(Invoice::class)->where('insurance_status', 'approved');
    }

    // Accesseurs pour les montants
    public function getMontantDuAttribute()
    {
        return $this->pendingInvoices()->sum('insurance_amount');
    }

    public function getMontantPayeAttribute()
    {
        return $this->approvedInvoices()->sum('insurance_amount');
    }

    public function getMontantTotalAttribute()
    {
        return $this->invoices()->sum('insurance_amount');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithBalance($query)
    {
        return $query->withSum('pendingInvoices as montant_du', 'insurance_amount')
                    ->withSum('approvedInvoices as montant_paye', 'insurance_amount')
                    ->withSum('invoices as montant_total', 'insurance_amount')
                    ->withCount('pendingInvoices as factures_impayees')
                    ->withCount('invoices as total_factures');
    }
    
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

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Obtenir la couverture pour un type de service spécifique
    public function getCoverageForService($serviceType, $serviceId, $insuranceId)
    {
        return InsuranceCoverage::where('insurance_company_id', $insuranceId)
                    ->where('coverageable_type', $serviceType)
                    ->where('coverageable_id', $serviceId)
                    ->where('status', 'active')
                    ->where('valid_from', '<=', now())
                    ->where(function($query) {
                        $query->whereNull('valid_to')
                            ->orWhere('valid_to', '>=', now());
                    })->first();
    }

    public function getTotalAmountCoverage($serviceType, $serviceId, $insuranceId)
    {
        return $this->invoices()
                    ->where('status', 'active')
                    ->where('valid_from', '<=', now())
                    ->where(function($query) {
                        $query->whereNull('valid_to')
                            ->orWhere('valid_to', '>=', now());
                    })->first();
    }
}

