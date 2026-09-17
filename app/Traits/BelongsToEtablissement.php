<?php

namespace App\Traits;

use App\Support\EtablissementContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Cloisonnement des données par établissement.
 *
 * Niveau 1 — lectures : toute requête Eloquent est filtrée sur
 *   l'établissement courant. Utilisateur connecté sans établissement →
 *   aucune ligne (fermé par défaut), sauf administrateur plateforme.
 *
 * Niveau 2 — écritures (garde du modèle, indépendante des contrôleurs) :
 *   impossible de créer une ligne pour un autre établissement, ni de
 *   modifier / supprimer une ligne d'un autre établissement, même via un
 *   chemin de code qui aurait contourné le filtre de lecture.
 *
 * Limites connues (à traiter dans le code appelant) :
 *   - DB::table() et les règles de validation `exists:`/`unique:` ne passent
 *     pas par Eloquent → utiliser `exists_etablissement` / `unique_etablissement`.
 *   - Les requêtes de masse (Model::where()->update()) ne déclenchent pas
 *     les événements : elles restent filtrées en lecture, donc sûres dans un
 *     contexte authentifié.
 *
 * NE PAS appliquer sur Patient (global) ni sur Etablissement.
 */
trait BelongsToEtablissement
{
    protected static function bootBelongsToEtablissement(): void
    {
        static::addGlobalScope('etablissement', function (Builder $builder) {
            $colonne = $builder->getModel()->getTable() . '.etablissement_id';

            if ($id = EtablissementContext::id()) {
                $builder->where($colonne, $id);
            } elseif (EtablissementContext::doitBloquer()) {
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $model) {
            $courant = EtablissementContext::id();

            if (empty($model->etablissement_id) && $courant) {
                $model->etablissement_id = $courant;
            }

            if ($courant && (int) $model->etablissement_id !== (int) $courant) {
                throw new \LogicException(sprintf('Création refusée : %s destiné à un autre établissement.', class_basename($model)));
            }
        });

        $garde = function (Model $model) {
            $courant = EtablissementContext::id();
            $proprietaire = $model->getOriginal('etablissement_id');

            if ($courant && $proprietaire !== null && (int) $proprietaire !== (int) $courant) {
                throw new \LogicException(sprintf('Accès refusé : %s #%s appartient à un autre établissement.', class_basename($model), $model->getKey()));
            }

            if ($model->isDirty('etablissement_id') && $model->exists && $proprietaire !== null) {
                throw new \LogicException('Changement d\'établissement interdit : créez une nouvelle fiche.');
            }
        };

        static::updating($garde);
        static::deleting($garde);
    }

    public function etablissement()
    {
        return $this->belongsTo(\App\Models\Etablissement::class);
    }

    /**
     * Échappatoire volontairement explicite et grep-able : uniquement dans un
     * contexte super-admin déjà vérifié en amont, jamais dans un contrôleur métier.
     */
    public function scopeTousEtablissements(Builder $query): Builder
    {
        return $query->withoutGlobalScope('etablissement');
    }
}
