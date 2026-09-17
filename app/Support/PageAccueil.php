<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Page d'arrivée d'un utilisateur selon ses permissions.
 *
 * Le tableau de bord général (/home) exige « dashboard.view » et affiche des
 * données de direction (factures impayées, recettes…) : les rôles du
 * laboratoire ne l'ont volontairement pas. Sans cette classe, ils recevaient
 * un 403 juste après s'être connectés.
 */
class PageAccueil
{
    /** Première page accessible, dans l'ordre : [permission, nom de route]. */
    private const PAGES = [
        ['dashboard.view', null],                              // tableau de bord général (/home)
        ['labo.tableau_bord', 'labo.tableau-bord'],            // technicien, biologiste
        ['labo.validation.technique', 'labo.validation.index'],
        ['labo.resultat.saisir', 'labo.paillasse.index'],
        ['labo.reception', 'labo.reception.index'],
        ['labo.prelevement', 'labo.prelevements.index'],       // préleveur
        ['labo.demande.view', 'labo.demandes.index'],          // accueil laboratoire
    ];

    public static function url(?User $user): string
    {
        if (! $user) {
            return url('/login');
        }

        foreach (self::PAGES as [$permission, $route]) {
            if (! $user->can($permission)) {
                continue;
            }

            if ($route === null) {
                return url('/home');
            }

            if (Route::has($route)) {
                return route($route);
            }
        }

        return url('/home');
    }

    /** Vrai si l'utilisateur doit être envoyé ailleurs que sur /home. */
    public static function aUneAutrePage(?User $user): bool
    {
        return $user && ! $user->can('dashboard.view') && static::url($user) !== url('/home');
    }
}
