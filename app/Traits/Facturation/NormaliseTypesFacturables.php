<?php

namespace App\Traits\Facturation;

use App\Support\Facturation\TypesFacturables;
use Illuminate\Database\Eloquent\Model;

/**
 * Filet de sécurité : quel que soit le code qui écrit (ancien contrôleur,
 * import, tinker), une colonne *_type facturable est enregistrée sous son
 * alias stable (« service ») et non sous un nom de classe PHP.
 *
 * Le modèle déclare ses colonnes :
 *   protected static array $colonnesTypesFacturables = ['coverageable_type'];
 */
trait NormaliseTypesFacturables
{
    protected static function bootNormaliseTypesFacturables(): void
    {
        static::saving(function (Model $modele) {
            foreach (static::$colonnesTypesFacturables ?? [] as $colonne) {
                $valeur = $modele->getAttribute($colonne);

                if ($valeur && TypesFacturables::classe($valeur)) {
                    $modele->setAttribute($colonne, TypesFacturables::alias($valeur));
                }
            }
        });
    }
}
