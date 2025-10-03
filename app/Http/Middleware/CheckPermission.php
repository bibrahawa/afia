<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter');
        }

        if (!Auth::user()->can($permission)) {
            abort(403, 'Accès non autorisé. Vous n\'avez pas la permission requise.');
        }

        return $next($request);
    }
}