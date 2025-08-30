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

    // Scopes
    public function scopePending($query)
    {
        return $query->where('insurance_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('insurance_status', 'approved');
    }

    public function scopeForInsurance($query, $insuranceId)
    {
        return $query->where('insurance_company_id', $insuranceId);
    }

    // Méthodes utilitaires
    public function markAsPaid($paymentDate = null, $claimNumber = null, $notes = null)
    {
        $this->update([
            'insurance_status' => 'approved',
            'insurance_payment_date' => $paymentDate ?? now(),
            'insurance_claim_number' => $claimNumber,
            'insurance_notes' => $notes
        ]);

        // Mettre à jour le statut de la transaction si nécessaire
        if ($this->patient_amount_status === 'paid') {
            $this->transaction->update(['status' => 'completed']);
        }
    }

    public function getFormattedInsuranceAmountAttribute()
    {
        return number_format($this->insurance_amount, 0, ',', ' ') . ' FCFA';
    }

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
