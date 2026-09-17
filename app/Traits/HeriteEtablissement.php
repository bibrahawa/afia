<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Pour les lignes créées hors contexte utilisateur (jobs, callbacks, seeders) :
 * l'établissement est repris du parent (facture → réclamation, chambre →
 * hospitalisation…). Déclarer dans le modèle :
 *
 *   protected static array $etablissementDepuis = ['chambre_id' => Chambre::class];
 *
 * Le premier parent renseigné l'emporte. Si un contexte utilisateur existe,
 * BelongsToEtablissement vérifie en plus que le parent est bien du même
 * établissement (un id de parent étranger lève une exception).
 */
trait HeriteEtablissement
{
    protected static function bootHeriteEtablissement(): void
    {
        static::creating(function (Model $model) {
            if (! empty($model->etablissement_id)) {
                return;
            }

            foreach (static::$etablissementDepuis as $cle => $classe) {
                if (! empty($model->{$cle})) {
                    $id = $classe::withoutGlobalScopes()->whereKey($model->{$cle})->value('etablissement_id');
                    if ($id) {
                        $model->etablissement_id = $id;

                        return;
                    }
                }
            }
        });
    }
}
