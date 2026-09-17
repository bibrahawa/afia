<?php

namespace App\Models\Labo;

use Illuminate\Database\Eloquent\Model;

/**
 * Pas de BelongsToEtablissement ici : une plage n'est jamais lue autrement
 * que via son paramètre, lui-même scopé. Les écritures passent toutes par
 * CatalogueController, qui vérifie l'appartenance du paramètre.
 */
class LaboValeurReference extends Model
{
    protected $table = 'labo_valeurs_reference';

    protected $fillable = [
        'parametre_id', 'sexe', 'age_min_jours', 'age_max_jours', 'grossesse',
        'min', 'max', 'critique_min', 'critique_max', 'valeur_attendue', 'texte_affiche',
    ];

    protected $casts = [
        'grossesse' => 'boolean',
        'min' => 'float', 'max' => 'float',
        'critique_min' => 'float', 'critique_max' => 'float',
    ];

    public function parametre()
    {
        return $this->belongsTo(LaboParametre::class, 'parametre_id');
    }

    public function libelleCritere(): string
    {
        $morceaux = [];
        if ($this->sexe) {
            $morceaux[] = $this->sexe === 'M' ? 'Homme' : 'Femme';
        }
        if ($this->age_min_jours !== null || $this->age_max_jours !== null) {
            $morceaux[] = self::formaterAge($this->age_min_jours) . ' → ' . ($this->age_max_jours === null ? '∞' : self::formaterAge($this->age_max_jours));
        }
        if ($this->grossesse !== null) {
            $morceaux[] = $this->grossesse ? 'Enceinte' : 'Non enceinte';
        }

        return $morceaux ? implode(' · ', $morceaux) : 'Tous patients';
    }

    public static function formaterAge(?int $jours): string
    {
        if ($jours === null || $jours === 0) {
            return '0';
        }
        if ($jours < 60) {
            return $jours . ' j';
        }
        if ($jours < 730) {
            return intdiv($jours, 30) . ' mois';
        }

        return intdiv($jours, 365) . ' ans';
    }
}
