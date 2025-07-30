<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'invoice_id',
        'insurance_company_id',
        'patient_id',
        'claimed_amount',
        'approved_amount',
        'paid_amount',
        'status',
        'submission_date',
        'approval_date',
        'payment_date',
        'rejection_reason',
        'documents', // Le champ JSON
    ];

    protected $casts = [
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'submission_date' => 'date',
        'approval_date' => 'date',
        'payment_date' => 'date',
        'documents' => 'array', // Cast pour gérer le JSON comme un tableau PHP
    ];

    /**
     * Get the invoice associated with the insurance claim.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the insurance company associated with the insurance claim.
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Get the patient associated with the insurance claim.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class); // Assure-toi que le modèle Patient existe
    }
}

