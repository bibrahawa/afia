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
}
