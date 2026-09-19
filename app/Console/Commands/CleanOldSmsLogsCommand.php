<?php

namespace App\Console\Commands;

use App\Models\AppointmentSmsLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanOldSmsLogsCommand extends Command
{
    protected $signature = 'sms:clean-logs {--days=90 : Nombre de jours à conserver}';
    protected $description = 'Nettoie les anciens logs SMS';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $deletedCount = AppointmentSmsLog::where('created_at', '<', $cutoffDate)->delete();
        // Journal des SMS par clinique (lot S1) : même durée de conservation.
        $journal = \App\Models\SmsJournal::withoutGlobalScopes()->where('created_at', '<', $cutoffDate)->delete();

        $this->info("{$deletedCount} anciens logs SMS de rendez-vous et {$journal} lignes du journal SMS supprimés (plus de {$days} jours)");

        return 0;
    }
}