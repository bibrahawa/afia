<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'coverage_type_id', // Renommé pour la convention Laravel (polymorphisme)
        'coverage_type_type', // Renommé pour la convention Laravel (polymorphisme)
        'description',
        'unit_price',
        'quantity',
        'total_amount',
        'insurance_covered_amount',
        'patient_amount',
        'coverage_percentage_applied',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'insurance_covered_amount' => 'decimal:2',
        'patient_amount' => 'decimal:2',
        'coverage_percentage_applied' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Relation polymorphe
    public function coverageType(): MorphTo
    {
        return $this->morphTo();
    }

}
