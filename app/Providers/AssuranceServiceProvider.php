<?php

namespace App\Providers;

use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Http\Middleware\EnsureModuleActive;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Module Assurance : organismes payeurs, entreprises, contrats, formules,
 * adhésions et ayants droit.
 *
 * Organisation (comme Laboratoire) : sous-dossier « Assurance » dans chaque
 * dossier Laravel standard.
 *
 * Enregistrement — bootstrap/providers.php :
 *   App\Providers\AssuranceServiceProvider::class,
 */
class AssuranceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/assurance'));

        if ($this->app->runningInConsole()) {
            $this->commands([\App\Console\Commands\Assurance\SynchroniserCouvertures::class]);
        }

        if (! $this->app->routesAreCached()) {
            Route::middleware(['web', 'auth', EnsureModuleActive::class . ':assurance'])
                ->prefix('assurance')
                ->name('assurance.')
                ->group(base_path('routes/assurance.php'));
        }

        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'renderable')) {
            $handler->renderable(function (OperationAssuranceImpossible $e, $request) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => $e->getMessage()], 422);
                }

                return back()->withInput()->with('error', $e->getMessage());
            });
        }
    }
}
