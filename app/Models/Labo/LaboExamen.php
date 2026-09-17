<?php

namespace App\Models\Labo;

use App\Models\InsuranceCoverage;
use App\Models\Test;
use App\Enums\Labo\TypeExamen;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboExamen extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_examens';

    protected $fillable = [
        'etablissement_id', 'modele_id', 'section_id', 'test_id',
        'code', 'nom', 'abreviation', 'type_examen', 'methode',
        'type_echantillon', 'tube', 'volume_ml', 'a_jeun', 'instructions_patient',
        'delai_rendu_heures', 'prix', 'sous_traite', 'laboratoire_sous_traitant',
        'ordre', 'actif',
    ];

    protected $casts = [
        'type_examen' => TypeExamen::class,
        'a_jeun' => 'boolean',
        'sous_traite' => 'boolean',
        'actif' => 'boolean',
        'prix' => 'decimal:2',
        'volume_ml' => 'decimal:2',
    ];

    public const TYPES_ECHANTILLON = [
        'sang' => 'Sang', 'urine' => 'Urine', 'selles' => 'Selles', 'crachat' => 'Crachat',
        'lcr' => 'Liquide céphalo-rachidien', 'prelevement_vaginal' => 'Prélèvement vaginal',
        'prelevement_uretral' => 'Prélèvement urétral', 'pus' => 'Pus', 'sperme' => 'Sperme',
        'liquide_ponction' => 'Liquide de ponction', 'autre' => 'Autre',
    ];

    public const TUBES = [
        'violet' => 'Violet (EDTA)', 'bleu' => 'Bleu (citrate)', 'rouge' => 'Rouge (sec)',
        'jaune' => 'Jaune (sec + gel)', 'vert' => 'Vert (héparine)', 'gris' => 'Gris (fluorure)',
        'pot' => 'Pot stérile', 'ecouvillon' => 'Écouvillon', 'lame' => 'Lame',
    ];

    public function section()
    {
        return $this->belongsTo(LaboSection::class, 'section_id');
    }

    public function parametres()
    {
        return $this->hasMany(LaboParametre::class, 'examen_id')->orderBy('ordre');
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /** Permet aux assurances de définir un prix/prise en charge par examen (moteur existant). */
    public function coverage()
    {
        return $this->morphOne(InsuranceCoverage::class, 'coverageable');
    }

    public function estBacteriologie(): bool
    {
        return $this->type_examen === TypeExamen::BACTERIOLOGIE;
    }

    /** Clé de regroupement des tubes : un même tube sert plusieurs examens. */
    public function cleContenant(): string
    {
        return $this->type_echantillon . '|' . ($this->tube ?? '-');
    }

    public function libelleContenant(): string
    {
        $type = self::TYPES_ECHANTILLON[$this->type_echantillon] ?? $this->type_echantillon;

        return $this->tube ? $type . ' — ' . (self::TUBES[$this->tube] ?? $this->tube) : $type;
    }
}
