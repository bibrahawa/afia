<?php

namespace App\Support;

use App\Models\Etablissement;
use Closure;

/**
 * Exécute un traitement AU NOM d'un autre établissement.
 *
 * Nécessaire pour les échanges entre établissements (une clinique qui envoie
 * une demande au laboratoire partenaire) : la demande appartient au
 * laboratoire, donc tous les modèles cloisonnés doivent la voir comme telle
 * le temps de l'écriture.
 *
 * Le contexte est TOUJOURS restauré, même en cas d'exception. À n'utiliser
 * que dans un service qui a déjà vérifié le droit d'écrire chez l'autre
 * établissement (partenariat actif, appartenance de la demande…).
 */
class ContexteTemporaire
{
    public static function pour(Etablissement|int $etablissement, Closure $traitement): mixed
    {
        $etablissement = $etablissement instanceof Etablissement
            ? $etablissement
            : Etablissement::withoutGlobalScopes()->findOrFail($etablissement);

        $precedent = app()->has('etablissement.public') ? app('etablissement.public') : null;

        app()->instance('etablissement.public', $etablissement);

        try {
            return $traitement($etablissement);
        } finally {
            if ($precedent) {
                app()->instance('etablissement.public', $precedent);
            } else {
                app()->forgetInstance('etablissement.public');
            }
        }
    }
}
