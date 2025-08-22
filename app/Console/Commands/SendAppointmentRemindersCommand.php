<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Jobs\SendAppointmentReminderJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'appointments:send-reminders 
                           {--type=all : Type de rappel (24h, 2h, all)} 
                           {--dry-run : Simulation sans envoi réel}';

    protected $description = 'Envoie les rappels SMS pour les rendez-vous médicaux';

    public function handle()
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        $this->info("🏥 Traitement des rappels SMS ({$type})");
        
        if ($dryRun) {
            $this->warn("⚠️  MODE SIMULATION - Aucun SMS ne sera envoyé");
        }

        $totalProcessed = 0;

        // Traiter les rappels 24h
        if ($type === 'all' || $type === '24h') {
            $totalProcessed += $this->processReminders('24h', $dryRun);
        }

        // Traiter les rappels 2h
        if ($type === 'all' || $type === '2h') {
            $totalProcessed += $this->processReminders('2h', $dryRun);
        }

        if (!in_array($type, ['24h', '2h', 'all'])) {
            $this->error("Type de rappel invalide. Utilisez '24h', '2h' ou 'all'");
            return 1;
        }

        $this->info("🎉 Total: {$totalProcessed} rappels traités");

        Log::info("Commande rappels SMS exécutée", [
            'type' => $type,
            'total_processed' => $totalProcessed,
            'dry_run' => $dryRun
        ]);

        return 0;
    }

    private function processReminders(string $type, bool $dryRun): int
    {
        $this->newLine();
        $this->info("📱 === RAPPELS {$type} ===");

        // Sélectionner les appointments selon le type
        if ($type === '24h') {
            $appointments = Appointment::needingReminder()->with(['patient', 'employee'])->get();
            $jobType = 'reminder_24h';
            $title = 'Rappels 24h avant';
        } else { // 2h
            $appointments = Appointment::needingLastMinuteReminder()->with(['patient', 'employee'])->get();
            $jobType = 'reminder_2h';
            $title = 'Rappels 2h avant';
        }

        $count = $appointments->count();
        $this->info("📋 {$count} rendez-vous trouvés pour {$title}");

        if ($appointments->isEmpty()) {
            $this->info("✅ Aucun rappel {$type} à envoyer");
            return 0;
        }

        // Afficher le tableau des RDV
        $tableData = $appointments->map(function ($appointment) {
            return [
                'ID' => $appointment->id,
                'Patient' => $appointment->patient->getFullNameAttribute(),
                'Médecin' => $appointment->employee->getFullNameAttribute(),
                'Date RDV' => $appointment->getFormattedDateAttribute(),
                'Téléphone' => $appointment->patient->user->phone,
                'Statut' => $appointment->status
            ];
        });

        $this->table(['ID', 'Patient', 'Médecin', 'Date RDV', 'Téléphone', 'Statut'], $tableData);

        if ($dryRun) {
            $this->warn("🔍 SIMULATION: {$count} rappels {$type} seraient envoyés");
            return $count;
        }

        // Confirmation seulement si on est en mode interactif
        if ($this->input->isInteractive() && !$this->option('force')) {
            if (!$this->confirm("Confirmer l'envoi de {$count} rappels {$type} ?", true)) {
                $this->warn("❌ Rappels {$type} annulés");
                return 0;
            }
        } else {
            $this->info("🤖 Mode automatique détecté, envoi de {$count} rappels {$type}");
        }

        // Envoi des rappels
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($appointments as $appointment) {
            try {

                SendAppointmentReminderJob::dispatch($appointment, $jobType)->delay(now()->addSeconds(2));
                
                // Marquer le rappel 24h comme envoyé
                if ($type === '24h') {
                    $appointment->update(['reminder_sent_at' => now()]);
                }
                
                $successCount++;
                
                // Petit délai pour éviter le spam
                if ($count > 10) {
                    usleep(rand(100000, 500000)); // 0.1 à 0.5 secondes
                }
                
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Erreur envoi rappel {$type}", [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($errorCount > 0) {
            $this->warn("⚠️  {$successCount} rappels {$type} envoyés, {$errorCount} erreurs");
        } else {
            $this->info("✅ {$successCount} rappels {$type} envoyés avec succès");
        }

        return $successCount;
    }
}