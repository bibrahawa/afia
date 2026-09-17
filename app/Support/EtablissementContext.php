<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Point d'entrée UNIQUE pour connaître l'établissement courant.
 *
 * MISE À JOUR — ordre de résolution, du plus prioritaire au moins :
 * 1. Contexte PUBLIC résolu par ResolveEtablissementPublic (page de prise
 *    de rdv sans authentification, un patient anonyme n'a pas de
 *    Auth::user()). C'EST LE CORRECTIF DU BUG D'ISOLATION identifié à
 *    l'analyse : sans ce niveau, le global scope ne filtrait rien du tout
 *    sur les pages publiques.
 * 2. Impersonation support (un super-admin qui consulte un établissement).
 * 3. L'établissement du User staff authentifié (cas normal back-office).
 */
class EtablissementContext
{
    public static function id(): ?int
    {
        if (app()->has('etablissement.public')) {
            return app('etablissement.public')->id;
        }

        if (session()->has('support_etablissement_id')) {
            return session('support_etablissement_id');
        }

        return Auth::user()?->etablissement_id;
    }

    public static function current()
    {
        if (app()->has('etablissement.public')) {
            return app('etablissement.public');
        }

        $id = static::id();

        return $id ? \App\Models\Etablissement::find($id) : null;
    }

    /** Rôle Spatie des administrateurs de la plateforme (équipe Aprosafe), seuls autorisés à voir tous les établissements. */
    public const ROLE_PLATEFORME = 'super-admin';

    /**
     * Cloisonnement « fermé par défaut » : un membre du personnel connecté
     * SANS établissement (compte mal configuré, établissement supprimé…)
     * ne doit voir AUCUNE donnée, et non celles de toutes les cliniques.
     *
     * Restent non filtrés : la console et les jobs (pas d'utilisateur), et
     * l'administrateur plateforme hors impersonation.
     */
    public static function doitBloquer(): bool
    {
        if (static::id() !== null) {
            return false;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        $utilisateur = Auth::guard('web')->user();
        if (! $utilisateur) {
            return false;
        }

        // Mémorisé pour la requête : le filtre est évalué à chaque requête SQL.
        $cache = request()->attributes;
        $cle = 'etablissement.bloquer.' . $utilisateur->getKey();
        if (! $cache->has($cle)) {
            $cache->set($cle, ! static::estAdministrateurPlateforme($utilisateur));
        }

        return $cache->get($cle);
    }

    public static function estAdministrateurPlateforme($utilisateur = null): bool
    {
        $utilisateur ??= Auth::guard('web')->user();

        return $utilisateur
            && empty($utilisateur->etablissement_id)
            && method_exists($utilisateur, 'hasRole')
            && $utilisateur->hasRole(static::ROLE_PLATEFORME);
    }
}
