<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceSettlementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_settlement_id',
        'invoice_id',
        'invoice_amount',
        'already_settled_amount',
        'remaining_before',
        'applied_discount_amount',
        'applied_paid_amount',
        'settled_amount',
        'remaining_after',
    ];

    protected $casts = [
        'invoice_amount' => 'decimal:2',
        'already_settled_amount' => 'decimal:2',
        'remaining_before' => 'decimal:2',
        'applied_discount_amount' => 'decimal:2',
        'applied_paid_amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'remaining_after' => 'decimal:2',
    ];

    public function settlement()
    {
        return $this->belongsTo(InsuranceSettlement::class, 'insurance_settlement_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}