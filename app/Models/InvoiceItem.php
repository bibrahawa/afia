<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToEtablissement;
use App\Traits\Facturation\NormaliseTypesFacturables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceItem extends Model
{
    use BelongsToEtablissement, NormaliseTypesFacturables;

    /** Enregistré sous alias stable (« service »…), voir TypesFacturables. */
    protected static array $colonnesTypesFacturables = ['coverage_type_type'];

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
        'discount' => 'decimal:2',
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

    /**
     * Montant brut de la ligne (avant remise), tel qu'enregistré en base.
     * total_amount est le montant APRÈS remise : brut = total_amount + discount.
     */
    public function montantBrut(): float
    {
        return round((float) $this->getOriginal('total_amount') + (float) $this->getOriginal('discount'), 2);
    }

    /** Part patient avant remise : ce que la remise peut réduire au maximum. */
    public function partPatientAvantRemise(): float
    {
        return max(0.0, round($this->montantBrut() - (float) $this->getOriginal('insurance_covered_amount'), 2));
    }

    /**
     * Fixe la remise de la ligne (montant ABSOLU, pas cumulatif) et recalcule
     * total et part patient à partir du brut.
     *
     * RÈGLE : la remise est un geste de la clinique envers le PATIENT. Elle
     * réduit la part patient et ne touche jamais la part assurance (déjà
     * réclamée ou à réclamer à l'assureur). Elle est donc plafonnée à la part
     * patient de la ligne.
     *
     * Remplace reCalculerApresReduction(), qui soustrayait la remise à chaque
     * appel (deux clics = remise doublée, part patient négative possible).
     *
     * @return float la remise réellement appliquée (plafonnée)
     */
    public function appliquerRemise(float $remise): float
    {
        $brut = $this->montantBrut();
        $plafond = $this->partPatientAvantRemise();
        $remise = round(min(max(0.0, $remise), $plafond), 2);

        $this->discount = $remise;
        $this->total_amount = round($brut - $remise, 2);
        $this->patient_amount = round($plafond - $remise, 2);
        $this->save();

        return $remise;
    }
}
