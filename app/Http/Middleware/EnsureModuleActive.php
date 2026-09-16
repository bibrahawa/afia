<?php

namespace App\Http\Middleware;

use App\Support\EtablissementContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Usage dans routes/web.php :
 *
 *   Route::middleware('module:laboratoire')->group(function () {
 *       ... routes du module labo ...
 *   });
 *
 * Ne compte jamais uniquement sur le menu pour cacher une fonctionnalité :
 * ce middleware garantit qu'un utilisateur qui devine/tape l'URL directement
 * reçoit un 403, même si le lien n'apparaît nulle part dans son interface.
 *
 * Enregistrement dans bootstrap/app.php (Laravel 11/12) :
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias(['module' => \App\Http\Middleware\EnsureModuleActive::class]);
 *   })
 */
class EnsureModuleActive
{
    public function handle(Request $request, Closure $next, string $moduleCode)
    {
        $etablissement = EtablissementContext::current();

        if (! $etablissement || ! $etablissement->aModule($moduleCode)) {
            throw new AccessDeniedHttpException(
                "Le module « {$moduleCode} » n'est pas activé pour cet établissement."
            );
        }

        return $next($request);
    }
}
