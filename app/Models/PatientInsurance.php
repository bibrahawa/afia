<?php

namespace App\Models;

use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientInsurance extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['insurance_company_id' => InsuranceCompany::class];

    use HasFactory;

    protected $fillable = [
        'patient_id', 'insurance_company_id', 'policy_number',
        'start_date', 'end_date', 'status', 'annual_limit',
        'used_amount', 'notes', 'coverage_percentage'
    ];

    protected $casts = [
        'start_date' => 'date',
        'coverage_percentage' => 'decimal:2',
        'end_date' => 'date',
        'annual_limit' => 'decimal:2',
        'used_amount' => 'decimal:2'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    // Vérifier si l'assurance patient est active
    public function isActive()
    {
        return $this->status === 'active' && 
               $this->start_date <= now() &&
               (!$this->end_date || $this->end_date >= now());
    }

    // Obtenir le plafond restant
    public function getRemainingLimit()
    {
        if (!$this->annual_limit) {
            return null; // Plafond illimité
        }
        return max(0, $this->annual_limit - $this->used_amount);
    }

    // Vérifier si le montant peut être couvert
    public function canCover($amount)
    {
        $remaining = $this->getRemainingLimit();
        return $remaining === null || $remaining >= $amount;
    }
}
