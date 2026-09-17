<?php

namespace App\Providers;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Http\Controllers\Labo\ResultatsPublicsController;
use App\Http\Middleware\EnsureModuleActive;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Branchement du module Laboratoire.
 *
 * Organisation : chaque dossier Laravel standard contient un sous-dossier
 * « Labo » (app/Models/Labo, app/Http/Controllers/Labo, resources/views/labo,
 * database/migrations/labo…). Ce provider déclare ce qui ne se charge pas
 * tout seul : le sous-dossier de migrations, les routes et le rendu des
 * erreurs métier.
 *
 * Enregistrement (Laravel 11/12) — bootstrap/providers.php :
 *   App\Providers\LaboServiceProvider::class,
 */
class LaboServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/labo'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\Labo\ActiverLaboratoire::class,
            ]);
        }

        $this->enregistrerRoutes();
        $this->enregistrerRenduErreursMetier();
    }

    private function enregistrerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['web', 'auth', EnsureModuleActive::class . ':laboratoire'])
            ->prefix('laboratoire')
            ->name('labo.')
            ->group(base_path('routes/labo.php'));

        // Lien envoyé par SMS au patient : pas de session ; l'autorisation est
        // la signature + l'expiration de l'URL (vérifiée dans le contrôleur,
        // pour tolérer le paramètre « pdf »), avec limitation de débit.
        Route::middleware(['web', 'throttle:30,1'])
            ->get('/resultats-labo/{demandeId}', [ResultatsPublicsController::class, 'afficher'])
            ->whereNumber('demandeId')
            ->name('labo.public.resultats');
    }

    /** Une règle métier violée revient à la page précédente avec le message, sans try/catch dans chaque contrôleur. */
    private function enregistrerRenduErreursMetier(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(function (OperationLaboImpossible $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });
    }
}
