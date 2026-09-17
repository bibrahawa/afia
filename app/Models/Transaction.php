<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;


class Transaction extends Model
{
    use BelongsToEtablissement;

    protected $fillable = [
        'etablissement_id',
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
            // Contexte sans utilisateur (job, callback de paiement) : on hérite
            // de l'établissement de l'acte facturé.
            if (empty($transaction->etablissement_id) && $transaction->transactionable_id
                && $transaction->transactionable_type
                && method_exists($transaction->transactionable_type, 'bootBelongsToEtablissement')) {
                $transaction->etablissement_id = $transaction->transactionable_type::withoutGlobalScopes()
                    ->whereKey($transaction->transactionable_id)->value('etablissement_id');
            }

            if (empty($transaction->etablissement_id)) {
                throw new \LogicException('Transaction sans établissement : impossible de la numéroter.');
            }

            if (empty($transaction->invoice_no)) {
                $transaction->invoice_no = app(\App\Services\NumerotationDocumentService::class)
                    ->numero($transaction->etablissement_id, 'T', 'transaction');
            }

            // Total auto si non défini
            $transaction->total = $transaction->sub_total + $transaction->tax_amount - $transaction->discount;
        });
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'transaction_id');
    }
}
