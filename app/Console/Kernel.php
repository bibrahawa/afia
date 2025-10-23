<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\AppointmentSmsLog;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // ============================================
        // RAPPELS SMS 24H - 5 fois par jour
        // ============================================
        // Exécution à 00h, 8h, 12h, 16h et 20h pour couvrir tous les créneaux
        // Fenêtre de détection : 22h-26h avant le RDV

        $schedule->command('appointments:send-reminders --type=24h')
                ->dailyAt('00:00')
                ->withoutOverlapping(10)
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/reminders-24h.log'))
                ->emailOutputOnFailure(config('mail.admin_email'));
        
        $schedule->command('appointments:send-reminders --type=24h --force')
                ->dailyAt('08:00')
                ->withoutOverlapping(10)
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/reminders-24h.log'))
                ->emailOutputOnFailure(config('mail.admin_email'));

        $schedule->command('appointments:send-reminders --type=24h --force')
                ->dailyAt('14:00')
                ->withoutOverlapping(10)
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/reminders-24h.log'))
                ->emailOutputOnFailure(config('mail.admin_email'));

        $schedule->command('appointments:send-reminders --type=24h --force')
                ->dailyAt('20:00')
                ->withoutOverlapping(10)
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/reminders-24h.log'))
                ->emailOutputOnFailure(config('mail.admin_email'));

        // ============================================
        // RAPPELS SMS 2H - Toutes les heures en journée
        // ============================================
        // Exécution de 6h à 22h (heures d'ouverture clinique)
        // Fenêtre de détection : 1h30-2h30 avant le RDV
        
        $schedule->command('appointments:send-reminders --type=2h --force')
                ->hourlyAt(0)
                ->between('06:00', '23:00')
                ->withoutOverlapping(10)
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/reminders-2h.log'))
                ->emailOutputOnFailure(config('mail.admin_email'));

        // ============================================
        // NETTOYAGE DES ANCIENS LOGS SMS
        // ============================================
        // Suppression des logs de plus de 90 jours (RGPD)
        
        $schedule->command('model:prune', ['--model' => [AppointmentSmsLog::class]])
                ->daily()
                ->at('02:00')
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/cleanup.log'));

        // ============================================
        // NETTOYAGE DES ANCIENS LOGS LARAVEL
        // ============================================
        // Suppression des logs de plus de 30 jours
        
        $schedule->command('log:clear')
                ->weekly()
                ->sundays()
                ->at('03:00')
                ->onOneServer();

        // ============================================
        // SURVEILLANCE DE LA QUEUE
        // ============================================
        // Redémarrer le queue worker si nécessaire
        
        $schedule->command('queue:restart')
                ->everyFiveMinutes()
                ->onOneServer()
                ->runInBackground();

        // ============================================
        // BACKUP BASE DE DONNÉES (Optionnel)
        // ============================================
        // Décommentez si vous voulez activer les backups automatiques
        
        /*
        $schedule->call(function () {
            $db_name = env('DB_DATABASE');
            $db_user = env('DB_USERNAME');
            $db_password = env('DB_PASSWORD');
            $db_host = env('DB_HOST', 'localhost');
            $backup_path = storage_path('backups/backup_' . date('Y-m-d_H-i-s') . '.sql');

            // Créer le dossier backups s'il n'existe pas
            if (!file_exists(storage_path('backups'))) {
                mkdir(storage_path('backups'), 0755, true);
            }

            $command = sprintf(
                'mysqldump --opt -h%s -u%s -p%s %s > %s',
                escapeshellarg($db_host),
                escapeshellarg($db_user),
                escapeshellarg($db_password),
                escapeshellarg($db_name),
                escapeshellarg($backup_path)
            );

            exec($command, $output, $result);

            if ($result === 0) {
                \Log::info('Backup base de données réussi', ['file' => $backup_path]);
            } else {
                \Log::error('Échec backup base de données', ['command' => $command]);
            }
        })->daily()->at('01:00');
        */
    }

    # Test du endpoint de login

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}