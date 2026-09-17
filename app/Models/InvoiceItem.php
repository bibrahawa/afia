<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceItem extends Model
{
    use BelongsToEtablissement;

    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (InvoiceItem $item) {
            if (empty($item->etablissement_id) && $item->invoice_id) {
                $item->etablissement_id = Invoice::withoutGlobalScopes()->whereKey($item->invoice_id)->value('etablissement_id');
            }
        });
    }

    protected $fillable = [
        'etablissement_id',
        'invoice_id',
        'coverage_type_id', // Renommé pour la convention Laravel (polymorphisme)
        'coverage_type_type', // Renommé pour la convention Laravel (polymorphisme)
        'description',
        'unit_price',
        'quantity',
        'total_amount',
        'discount',
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

    public function reCalculerApresReduction()
    {
        // Sous-total brut
        $sousTotal = $this->unit_price * $this->quantity;

        // Réduction ligne (si applicable)
        $totalApresDiscount = $sousTotal - $this->discount;
        
        $patientAmount   = $this->patient_amount - $this->discount;
        // Mise à jour de la ligne
        $this->total_amount = $totalApresDiscount;
        $this->patient_amount = $patientAmount;

        $this->save();
    }


}
