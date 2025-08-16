<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceCoverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id', 'coverageable_type', 'coverageable_id',
        'max_amount', 'min_amount', 'max_usage_count',
        'usage_period', 'coverage_amount_limit', 'conditions', 'valid_from',
        'valid_to', 'requires_preauthorization', 'status', 'acte_price'
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'coverage_amount_limit' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'requires_preauthorization' => 'boolean'
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
