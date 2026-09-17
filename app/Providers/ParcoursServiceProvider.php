<?php

namespace App\Providers;

use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Http\Middleware\EnsureModuleActive;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Module Parcours patient : accueil, constantes, file d'attente, consultation.
 *
 * Dossier « Parcours » (et non « Consultation ») : App\Models\Consultation est
 * déjà une classe ; un espace de noms du même nom prêterait à confusion.
 *
 * Enregistrement — bootstrap/providers.php :
 *   App\Providers\ParcoursServiceProvider::class,
 */
class ParcoursServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/parcours'));

        if (! $this->app->routesAreCached()) {
            Route::middleware(['web', 'auth', EnsureModuleActive::class . ':consultation'])
                ->prefix('parcours')
                ->name('parcours.')
                ->group(base_path('routes/parcours.php'));
        }

        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'renderable')) {
            $handler->renderable(function (OperationParcoursImpossible $e, $request) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => $e->getMessage()], 422);
                }

                return back()->withInput()->with('error', $e->getMessage());
            });
        }
    }
}
