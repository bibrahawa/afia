<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lot E1 — Appliqué à toutes les pages web (groupe « web »).
 *
 * 1. Compte suspendu : la session est fermée immédiatement, même déjà ouverte.
 *    CORRIGÉ — la suspension n'était jamais vérifiée : un compte « suspendu »
 *    continuait de se connecter et de travailler.
 * 2. Mot de passe provisoire : la personne doit le changer avant d'utiliser
 *    l'application (seules « Mon profil », le changement de mot de passe et la
 *    déconnexion restent accessibles).
 */
class VerifierCompteActif
{
    private const ROUTES_AUTORISEES = ['employee.profile', 'employee.mot-de-passe', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = Auth::guard('web')->user();

        if (! $utilisateur) {
            return $next($request);
        }

        if ($utilisateur->status !== null && ! (bool) $utilisateur->status) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Compte suspendu.'], 403)
                : redirect()->route('login')->withErrors(['phone' => 'Ce compte est suspendu. Contactez l\'administrateur de la clinique.']);
        }

        if ($utilisateur->doit_changer_mot_de_passe && ! $request->routeIs(...self::ROUTES_AUTORISEES)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Changez votre mot de passe provisoire.'], 403)
                : redirect()->route('employee.profile')->with('warning', 'Bienvenue ! Choisissez votre propre mot de passe pour continuer.');
        }

        return $next($request);
    }
}
