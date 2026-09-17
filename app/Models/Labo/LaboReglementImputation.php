<?php

namespace App\Models\Labo;

use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Part d'un règlement affectée à une créance précise. */
class LaboReglementImputation extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['reglement_id' => LaboReglementPartenaire::class];

    protected $table = 'labo_reglement_imputations';

    protected $fillable = ['etablissement_id', 'reglement_id', 'creance_id', 'montant'];

    protected $casts = ['montant' => 'decimal:2'];

    public function reglement() { return $this->belongsTo(LaboReglementPartenaire::class, 'reglement_id'); }
    public function creance() { return $this->belongsTo(LaboCreancePartenaire::class, 'creance_id'); }
}
