<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'insurance_company_id',
        'policy_number',
        'start_date',
        'end_date',
        'status',
        'annual_limit',
        'used_amount',
        'notes',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function activeInsurance()
    {
        return $this->hasOne(PatientInsurance::class)->where('is_active', true);
    }
}
