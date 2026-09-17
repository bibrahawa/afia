<?php

namespace App\Models\Labo;

use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Ce qu'une clinique partenaire doit au laboratoire pour UNE demande. */
class LaboCreancePartenaire extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    public const A_FACTURER = 'a_facturer';
    public const FACTUREE = 'facturee';
    public const REGLEE = 'reglee';
    public const ANNULEE = 'annulee';

    protected static array $etablissementDepuis = ['demande_id' => LaboDemande::class];

    protected $table = 'labo_creances_partenaires';

    protected $fillable = ['etablissement_id', 'partenariat_id', 'demande_id', 'releve_id', 'montant', 'montant_regle', 'statut'];

    protected $casts = ['montant' => 'decimal:2', 'montant_regle' => 'decimal:2'];

    public function demande() { return $this->belongsTo(LaboDemande::class, 'demande_id'); }
    public function partenariat() { return $this->belongsTo(LaboPartenariat::class, 'partenariat_id'); }
    public function releve() { return $this->belongsTo(LaboRelevePartenaire::class, 'releve_id'); }
    public function imputations() { return $this->hasMany(LaboReglementImputation::class, 'creance_id'); }

    public function resteDu(): float
    {
        return max(0, round((float) $this->montant - (float) $this->montant_regle));
    }

    /** Une créance figée est celle d'un relevé déjà envoyé : son montant ne bouge plus. */
    public function estFigee(): bool
    {
        return in_array($this->statut, [self::FACTUREE, self::REGLEE], true);
    }

    public function scopeOuvertes(Builder $query): Builder
    {
        return $query->whereIn('statut', [self::A_FACTURER, self::FACTUREE]);
    }
}
