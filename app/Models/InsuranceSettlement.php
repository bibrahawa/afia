<?php

namespace App\Models;

use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceSettlement extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['insurance_company_id' => InsuranceCompany::class];

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
            if (! $settlement->settlement_no && $settlement->etablissement_id) {
                $settlement->settlement_no = app(\App\Services\NumerotationDocumentService::class)
                    ->numero($settlement->etablissement_id, 'SET', 'reglement-assurance');
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