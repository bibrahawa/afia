<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;


class Transaction extends Model
{
    protected $fillable = [
        'invoice_no',
        'transactionable_id',
        'transactionable_type',
        'account_id',
        'user_id',
        'patient_id',
        'description',
        'sub_total',
        'tax_amount',
        'discount',
        'montant_payer',
        'total',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeWithInsurance($query)
    {
        return $query->whereHas('invoice');
    }

    public function scopePendingInsurancePayment($query)
    {
        return $query->whereHas('invoice', function($q) {
            $q->where('insurance_status', 'pending');
        });
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    protected static function booted()
    {
        static::creating(function ($transaction) {
            $annee = now()->year;
            $last = self::whereYear('created_at', $annee)->latest('id')->first();
            $number = $last ? (int)substr($last->invoice_no, -5) + 1 : 1;
            $transaction->invoice_no = 'T-' . $annee . str_pad($number, 5, '0', STR_PAD_LEFT);

            // Total auto si non défini
            $transaction->total = $transaction->sub_total + $transaction->tax_amount - $transaction->discount;
        });
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'transaction_id');
    }
}
