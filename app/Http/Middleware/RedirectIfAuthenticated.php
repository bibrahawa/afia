<?php

namespace App\Http\Middleware;

use App\Support\PageAccueil;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Utilisateur déjà connecté qui revient sur /login : vers SA page d'accueil
     * (et non /home, interdit aux rôles du laboratoire).
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            return redirect()->to(PageAccueil::url(Auth::guard($guard)->user()));
        }

        return $next($request);
    }
}
