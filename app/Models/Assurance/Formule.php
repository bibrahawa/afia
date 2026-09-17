<?php

namespace App\Models\Assurance;

use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/**
 * Garanties d'une catégorie de personnel au sein d'un contrat (ex. « Cadres »,
 * « Agents »). Écarts par famille d'actes : FormuleGarantie.
 */
class Formule extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['contrat_id' => Contrat::class];

    protected $table = 'assurance_formules';

    protected $fillable = [
        'etablissement_id', 'contrat_id', 'libelle', 'taux_prise_en_charge',
        'plafond_annuel_beneficiaire', 'plafond_annuel_famille', 'delai_carence_jours',
        'age_max_enfant', 'age_max_enfant_etudiant', 'actif',
    ];

    protected $casts = [
        'taux_prise_en_charge' => 'decimal:2',
        'plafond_annuel_beneficiaire' => 'decimal:2',
        'plafond_annuel_famille' => 'decimal:2',
        'delai_carence_jours' => 'integer',
        'age_max_enfant' => 'integer',
        'age_max_enfant_etudiant' => 'integer',
        'actif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn (Formule $formule) => app(\App\Services\Assurance\ProjectionCouvertureService::class)->pourFormule($formule));
    }

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }

    public function garanties()
    {
        return $this->hasMany(FormuleGarantie::class);
    }

    /** Règles propres à une famille d'actes, ou null (règles générales de la formule). */
    public function garantiePour(\App\Enums\Assurance\FamilleActe $famille): ?FormuleGarantie
    {
        return $this->garanties->first(fn (FormuleGarantie $g) => $g->famille_acte === $famille);
    }

    public function adhesions()
    {
        return $this->hasMany(Adhesion::class);
    }
}
