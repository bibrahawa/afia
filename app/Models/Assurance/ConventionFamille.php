<?php

namespace App\Models\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Models\InsuranceCompany;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Règle de convention pour toute une famille d'actes chez un organisme :
 * « toute la pharmacie au prix catalogue », « tout le laboratoire avec 10 %
 * de remise ». Une ligne de convention par acte (InsuranceCoverage) reste
 * prioritaire : prix négocié ou exclusion d'un acte précis.
 */
class ConventionFamille extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['insurance_company_id' => InsuranceCompany::class];

    protected $table = 'assurance_convention_familles';

    protected $fillable = [
        'etablissement_id', 'insurance_company_id', 'famille_acte', 'remise_pourcentage',
        'plafond_par_acte', 'accord_prealable', 'valid_from', 'valid_to', 'actif',
    ];

    protected $casts = [
        'famille_acte' => FamilleActe::class,
        'remise_pourcentage' => 'decimal:2',
        'plafond_par_acte' => 'decimal:2',
        'accord_prealable' => 'boolean',
        'actif' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function organisme()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function enVigueurLe(Carbon $date): bool
    {
        return $this->actif
            && $this->valid_from->lte($date)
            && ($this->valid_to === null || $this->valid_to->gte($date->copy()->startOfDay()));
    }
}
