<?php

namespace App\Providers;

use App\Exceptions\Facturation\OperationFacturationImpossible;
use App\Support\Facturation\TypesFacturables;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

/**
 * Branchement du module Facturation (partagé par consultation,
 * hospitalisation et laboratoire).
 *
 * Organisation : sous-dossier « Facturation » dans chaque dossier Laravel
 * standard (app/Support/Facturation, app/Traits/Facturation,
 * database/migrations/facturation, tests/Feature/Facturation…), comme le
 * module Laboratoire.
 *
 * Enregistrement (Laravel 11/12) — bootstrap/providers.php :
 *   App\Providers\FacturationServiceProvider::class,
 *
 * IMPORTANT : la carte des types et la migration qui convertit les données
 * sont chargées par CE provider. Les deux vont ensemble : sans lui, rien
 * ne change (anciens noms de classe) ; avec lui, lancer `php artisan migrate`
 * immédiatement après le déploiement.
 */
class FacturationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        TypesFacturables::enregistrer();

        $this->loadMigrationsFrom(database_path('migrations/facturation'));

        $this->enregistrerRenduErreursMetier();
    }

    /** Une règle de facturation violée revient à la page précédente avec son message. */
    private function enregistrerRenduErreursMetier(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(function (OperationFacturationImpossible $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });
    }
}
