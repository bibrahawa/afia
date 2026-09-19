<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Appointment;
use App\Observers\AppointmentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Appointment::observe(AppointmentObserver::class);

        // Pagination aux couleurs de Bootstrap 5 (celui de KaiAdmin). Par défaut
        // Laravel produit du balisage Tailwind, non chargé ici : les listes
        // paginées affichaient des flèches géantes et des liens sans style.
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        /*
        | @module('laboratoire') … @endmodule
        |
        | Le menu se réglait sur les seules permissions : un administrateur les
        | ayant toutes, un laboratoire voyait les écrans d'une clinique — que
        | ses routes lui refusent de toute façon. La question à poser est
        | double : l'utilisateur a-t-il le droit, ET l'établissement a-t-il le
        | module ?
        */
        \Illuminate\Support\Facades\Blade::if('module', function (string ...$codes) {
            $etablissement = \App\Support\EtablissementContext::current();

            if (! $etablissement) {
                return false;
            }

            foreach ($codes as $code) {
                if ($etablissement->aModule($code)) {
                    return true;
                }
            }

            return false;
        });
    }
}
