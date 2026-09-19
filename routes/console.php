<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
| CORRIGÉ — Avec Laravel 11 (bootstrap/app.php « Application::configure »),
| app/Console/Kernel.php n'est plus chargé : toute la planification qui s'y
| trouvait (rappels de rendez-vous 24 h et 2 h, nettoyage des journaux SMS)
| ne s'exécutait JAMAIS. Elle est déclarée ici, là où Laravel 11 la lit.
|
| Côté serveur, une seule ligne cron suffit (cPanel / LWS : « Tâches cron ») :
|   * * * * * cd /chemin/vers/hali && php artisan schedule:run >> /dev/null 2>&1
|
| Vérifier : php artisan schedule:list
*/

// Rappels 24 h avant : plusieurs passages pour couvrir tous les créneaux
// (fenêtre de détection 22 h – 26 h ; la commande ignore les rappels déjà envoyés).
foreach (['00:00', '08:00', '14:00', '20:00'] as $heure) {
    Schedule::command('appointments:send-reminders --type=24h --force')
        ->dailyAt($heure)
        ->withoutOverlapping(10)
        ->appendOutputTo(storage_path('logs/rappels-24h.log'));
}

// Rappels 2 h avant, toutes les heures en journée.
Schedule::command('appointments:send-reminders --type=2h --force')
    ->hourlyAt(0)
    ->between('06:00', '22:00')
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/rappels-2h.log'));

// Rappels de consultation prénatale (CPN) : commande existante, jamais planifiée jusqu'ici.
Schedule::command('hali:rappels-cpn --jours=3')
    ->dailyAt('09:00')
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/rappels-cpn.log'));

// File d'attente : les SMS de confirmation, de reprogrammation et de résultats
// partent en tâche de fond. Sur un hébergement mutualisé (pas de « worker »
// permanent), on vide la file chaque minute.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);

// Journaux SMS de plus de 90 jours (commande existante du projet).
Schedule::command('sms:clean-logs --days=90')
    ->dailyAt('02:00');
