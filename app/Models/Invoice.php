<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'insurance_company_id',
        'patient_insurance_id',
        'total_amount',
        'patient_amount',
        'insurance_amount',
        'insurance_status',
        'patient_amount_status',
        'insurance_submission_date',
        'insurance_payment_date',
        'insurance_claim_number',
        'insurance_notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'patient_amount' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'insurance_submission_date' => 'date',
        'insurance_payment_date' => 'date',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
}
