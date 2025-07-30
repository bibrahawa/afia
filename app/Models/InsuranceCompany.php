<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'contract_start_date',
        'contract_end_date',
        'status',
        'default_coverage_percentage',
        'notes',
    ];

    public function coverages()
    {
        return $this->hasMany(InsuranceCoverage::class);
    }

    public function patientInsurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }
}

