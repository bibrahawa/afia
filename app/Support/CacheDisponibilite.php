<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Invalidation du cache des disponibilités d'un médecin.
 *
 * Le driver de cache actuel ne gère pas forcément les tags : plutôt que de
 * supprimer des clés une à une (on ne connaît pas toutes les combinaisons
 * motif/date/fenêtre), chaque clé embarque un numéro de VERSION propre au
 * médecin. Incrémenter la version rend toutes les anciennes clés
 * inaccessibles d'un coup ; elles expirent ensuite d'elles-mêmes (TTL court).
 *
 * Appelé par les événements de modèle (rendez-vous, congé, pause, planning)
 * et explicitement après les mises à jour de masse qui ne déclenchent pas
 * ces événements.
 */
class CacheDisponibilite
{
    public static function version(int $employeeId): int
    {
        return (int) Cache::get(static::cleVersion($employeeId), 0);
    }

    public static function invalider(?int $employeeId): void
    {
        if (! $employeeId) {
            return;
        }

        try {
            Cache::forever(static::cleVersion($employeeId), static::version($employeeId) + 1);
        } catch (\Throwable $e) {
            // Un cache indisponible ne doit jamais faire échouer une écriture
            // métier : au pire, l'affichage reste périmé le temps du TTL.
            report($e);
        }
    }

    /** Préfixe de clé versionné, à utiliser dans toutes les clés de disponibilité. */
    public static function prefixe(int $employeeId): string
    {
        return "dispo:m{$employeeId}:v" . static::version($employeeId);
    }

    protected static function cleVersion(int $employeeId): string
    {
        return "dispo:version:{$employeeId}";
    }
}
