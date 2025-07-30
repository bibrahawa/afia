<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceCoverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'coverageable_id',
        'coverageable_type',
        'coverage_percentage',
        'max_amount',
        'min_amount',
        'max_usage_count',
        'usage_period',
        'coverage_amount_limit',
        'conditions',
        'valid_from',
        'valid_to',
        'requires_preauthorization',
        'status',
    ];

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function coverageable()
    {
        return $this->morphTo();
    }
}
