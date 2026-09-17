<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class InsuranceClaim extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['invoice_id' => Invoice::class, 'insurance_company_id' => InsuranceCompany::class];

    use HasFactory;

    protected $fillable = [
        'claim_number',
        'invoice_id',
        'insurance_company_id',
        'patient_insurance_id',
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

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
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

