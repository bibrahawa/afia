<?php

namespace App\Support\Labo;

/**
 * Choisit LA plage de référence applicable à un patient parmi toutes
 * celles d'un paramètre. Pur (objets ou tableaux), sans requête SQL.
 *
 * Règles :
 * - un critère null sur la plage = « s'applique à tous » ;
 * - on garde la plage la plus spécifique (le plus de critères renseignés),
 *   puis la tranche d'âge la plus étroite ;
 * - âge inconnu : seules les plages SANS critère d'âge sont éligibles.
 *   Mieux vaut « norme non définie » qu'une norme adulte appliquée à un
 *   nourrisson dont on ignore la date de naissance.
 */
class SelecteurValeurReference
{
    /**
     * @param  iterable<object|array>  $plages  champs : sexe, age_min_jours, age_max_jours, grossesse
     * @param  string|null  $sexe  'M' | 'F'
     */
    public static function choisir(iterable $plages, ?string $sexe, ?int $ageJours, bool $grossesse = false): object|array|null
    {
        $meilleure = null;
        $meilleurScore = null;

        foreach ($plages as $plage) {
            $p = self::lire($plage);

            if ($p->sexe !== null && $p->sexe !== $sexe) {
                continue;
            }

            if ($p->age_min_jours !== null || $p->age_max_jours !== null) {
                if ($ageJours === null) {
                    continue;
                }
                if ($p->age_min_jours !== null && $ageJours < (int) $p->age_min_jours) {
                    continue;
                }
                if ($p->age_max_jours !== null && $ageJours > (int) $p->age_max_jours) {
                    continue;
                }
            }

            if ($p->grossesse !== null && (bool) $p->grossesse !== $grossesse) {
                continue;
            }

            $criteres = ($p->sexe !== null) + ($p->age_min_jours !== null || $p->age_max_jours !== null) + ($p->grossesse !== null);
            $largeur = ($p->age_max_jours ?? 50000) - ($p->age_min_jours ?? 0);
            $score = [$criteres, -$largeur];

            if ($meilleurScore === null || $score > $meilleurScore) {
                $meilleure = $plage;
                $meilleurScore = $score;
            }
        }

        return $meilleure;
    }

    private static function lire(object|array $plage): object
    {
        if (is_array($plage)) {
            $attributs = $plage;
        } elseif (method_exists($plage, 'getAttributes')) {
            $attributs = $plage->getAttributes();
        } else {
            $attributs = get_object_vars($plage);
        }

        return (object) array_merge(
            ['sexe' => null, 'age_min_jours' => null, 'age_max_jours' => null, 'grossesse' => null],
            $attributs
        );
    }
}
