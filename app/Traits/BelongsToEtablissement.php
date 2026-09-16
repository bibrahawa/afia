<?php

namespace App\Traits;

use App\Support\EtablissementContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Isolation niveau 1 (défense en profondeur, voir niveau 2 = les Policies).
 *
 * - Filtre automatiquement toute lecture par l'établissement courant.
 * - Renseigne automatiquement etablissement_id à la création.
 *
 * IMPORTANT : ce trait ne remplace pas les Policies. Une action sensible
 * (update/delete/export) doit toujours revérifier explicitement
 * l'appartenance dans une Policy dédiée — ce trait protège les lectures
 * "par défaut", pas les écritures.
 *
 * À appliquer sur Consultation, Appointment, Invoice, Hospitalisation,
 * Employee, Chambre, etc. lors de leur re-migration. NE PAS l'appliquer
 * sur Patient (qui reste global) ni sur Etablissement lui-même.
 */
trait BelongsToEtablissement
{
    protected static function bootBelongsToEtablissement(): void
    {
        static::addGlobalScope('etablissement', function (Builder $builder) {
            if ($id = EtablissementContext::id()) {
                $builder->where(
                    $builder->getModel()->getTable() . '.etablissement_id',
                    $id
                );
            }
        });

        static::creating(function (Model $model) {
            if (empty($model->etablissement_id) && $id = EtablissementContext::id()) {
                $model->etablissement_id = $id;
            }
        });
    }

    public function etablissement()
    {
        return $this->belongsTo(\App\Models\Etablissement::class);
    }

    /**
     * Échappatoire volontairement explicite et grep-able : à utiliser
     * uniquement dans un contexte super-admin déjà vérifié en amont
     * (jamais dans un contrôleur métier normal).
     */
    public function scopeTousEtablissements(Builder $query): Builder
    {
        return $query->withoutGlobalScope('etablissement');
    }
}
