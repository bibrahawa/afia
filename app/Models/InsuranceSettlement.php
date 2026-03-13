<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'settlement_no',
        'period_start',
        'period_end',
        'gross_amount',
        'discount_amount',
        'paid_amount',
        'net_amount',
        'remaining_amount',
        'payment_method',
        'payment_reference',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'gross_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function ($settlement) {
            if (!$settlement->settlement_no) {
                $year = now()->year;
                $last = self::whereYear('created_at', $year)->latest('id')->first();
                $next = $last ? ((int) substr($last->settlement_no, -5)) + 1 : 1;

                $settlement->settlement_no = 'SET-' . $year . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function items()
    {
        return $this->hasMany(InsuranceSettlementItem::class);
    }
}