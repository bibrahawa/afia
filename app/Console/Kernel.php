<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * NE PLUS UTILISER POUR LA PLANIFICATION.
 *
 * Laravel 11 (bootstrap/app.php) ne charge pas cette classe : les tâches
 * déclarées ici ne tournaient jamais. Toute la planification est désormais
 * dans routes/console.php. Classe gardée vide pour éviter qu'une ancienne
 * liaison ne double les envois si elle était un jour réactivée.
 */
class Kernel extends ConsoleKernel
{
}
