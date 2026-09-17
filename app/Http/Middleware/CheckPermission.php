<?php

namespace App\Http\Middleware;

use App\Support\PageAccueil;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter');
        }

        if (! Auth::user()->can($permission)) {
            // Lien « Accueil » / logo cliqué par un rôle sans tableau de bord
            // général : on le ramène à sa propre page d'accueil plutôt qu'un 403.
            if ($permission === 'dashboard.view' && $request->isMethod('GET') && PageAccueil::aUneAutrePage(Auth::user())) {
                return redirect()->to(PageAccueil::url(Auth::user()));
            }

            abort(403, 'Accès non autorisé. Vous n\'avez pas la permission requise.');
        }

        return $next($request);
    }
}
