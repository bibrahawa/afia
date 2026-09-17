<?php

namespace App\Models;

use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceCoverage extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['insurance_company_id' => InsuranceCompany::class];

    use HasFactory;

    protected $fillable = [
        'insurance_company_id', 'coverageable_type', 'coverageable_id',
        'max_amount', 'min_amount', 'max_usage_count',
        'usage_period', 'coverage_amount_limit', 'conditions', 'valid_from',
        'valid_to', 'requires_preauthorization', 'status', 'acte_price'
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'coverage_amount_limit' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'requires_preauthorization' => 'boolean'
    ];

    protected static function booted(): void
    {
        // coverageable_id vient d'un formulaire sans règle exists possible (polymorphe) :
        // on vérifie ici que l'acte couvert appartient bien au même établissement.
        $verifier = function (InsuranceCoverage $couverture) {
            $classe = $couverture->coverageable_type;
            if (! $classe || ! $couverture->coverageable_id || ! class_exists($classe)
                || ! method_exists($classe, 'bootBelongsToEtablissement')) {
                return;
            }

            $proprietaire = $classe::withoutGlobalScopes()->whereKey($couverture->coverageable_id)->value('etablissement_id');

            if ($proprietaire === null || (int) $proprietaire !== (int) $couverture->etablissement_id) {
                throw new \LogicException('Couverture refusée : l\'acte couvert n\'appartient pas à cet établissement.');
            }
        };

        // creating/updating (et non saving) : l'établissement est renseigné par le trait au « creating ».
        static::creating($verifier);
        static::updating($verifier);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function coverageable()
    {
        return $this->morphTo();
    }
}
