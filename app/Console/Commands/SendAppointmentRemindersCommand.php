<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Jobs\SendAppointmentReminderJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'appointments:send-reminders 
                           {--type=all : Type de rappel (24h, 2h, all)} 
                           {--dry-run : Simulation sans envoi réel}
                           {--force : Ne pas demander de confirmation}';

    protected $description = 'Envoie les rappels SMS pour les rendez-vous médicaux';

    public function handle()
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        $this->info("🏥 Démarrage du traitement des rappels SMS");
        $this->info("📅 Date/Heure : " . now()->format('d/m/Y H:i:s'));
        $this->info("🔧 Type : {$type}");
        
        if ($dryRun) {
            $this->warn("⚠️  MODE SIMULATION - Aucun SMS ne sera envoyé");
        }

        $this->newLine();

        // Valider le type
        if (!in_array($type, ['24h', '2h', 'all'])) {
            $this->error("❌ Type de rappel invalide. Utilisez '24h', '2h' ou 'all'");
            return 1;
        }

        $totalProcessed = 0;
        $startTime = microtime(true);

        // Traiter les rappels 24h
        if ($type === 'all' || $type === '24h') {
            $totalProcessed += $this->processReminders('24h', $dryRun);
        }

        // Traiter les rappels 2h
        if ($type === 'all' || $type === '2h') {
            $totalProcessed += $this->processReminders('2h', $dryRun);
        }

        $executionTime = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info("✅ Traitement terminé en {$executionTime}s");
        $this->info("📊 Total : {$totalProcessed} rappels traités");

        Log::info("Commande rappels SMS exécutée", [
            'type' => $type,
            'total_processed' => $totalProcessed,
            'execution_time' => $executionTime,
            'dry_run' => $dryRun
        ]);

        return 0;
    }

    private function processReminders(string $type, bool $dryRun): int
    {
        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📱 TRAITEMENT DES RAPPELS {$type}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Configuration selon le type
        if ($type === '24h') {
            $query = Appointment::needingReminder();
            $jobType = 'reminder_24h';
            $title = 'Rappels 24h avant';
            $reminderField = 'reminder_sent_at';
        } else { // 2h
            $query = Appointment::needingLastMinuteReminder();
            $jobType = 'reminder_2h';
            $title = 'Rappels 2h avant';
            $reminderField = 'last_minute_reminder_sent_at';
        }

        // Compter le nombre de RDV
        $totalCount = $query->count();
        
        $this->info("🔍 Recherche en cours...");
        $this->info("📋 {$totalCount} rendez-vous trouvés pour {$title}");

        if ($totalCount === 0) {
            $this->info("✅ Aucun rappel {$type} à envoyer");
            return 0;
        }

        // Afficher les détails de la fenêtre temporelle
        if ($type === '24h') {
            $start = now()->addHours(22)->format('d/m/Y H:i');
            $end = now()->addHours(26)->format('d/m/Y H:i');
            $this->comment("⏰ Fenêtre de détection : {$start} → {$end}");
        } else {
            $start = now()->addMinutes(90)->format('d/m/Y H:i');
            $end = now()->addMinutes(150)->format('d/m/Y H:i');
            $this->comment("⏰ Fenêtre de détection : {$start} → {$end}");
        }

        $this->newLine();

        // En mode dry-run, afficher tous les détails
        if ($dryRun) {
            return $this->showDryRunResults($query, $type, $totalCount);
        }

        // Confirmation en mode interactif (sauf si --force)
        if (!$this->option('force') && $this->input->isInteractive()) {
            if (!$this->confirm("❓ Confirmer l'envoi de {$totalCount} rappels {$type} ?", true)) {
                $this->warn("❌ Envoi annulé par l'utilisateur");
                return 0;
            }
        }

        // Traitement par chunks
        return $this->sendReminders($query, $jobType, $reminderField, $totalCount);
    }

    private function showDryRunResults($query, string $type, int $totalCount): int
    {
        $this->warn("🔍 MODE SIMULATION - Aperçu des {$totalCount} rappels qui seraient envoyés :");
        $this->newLine();

        // Récupérer les appointments avec les relations
        $appointments = $query->with(['patient.user', 'employee'])->get();

        // Préparer les données pour le tableau
        $tableData = $appointments->map(function ($appointment) {
            return [
                'ID' => $appointment->id,
                'Patient' => $appointment->patient->getFullNameAttribute(),
                'Médecin' => $appointment->employee->getFullNameAttribute(),
                'Date RDV' => $appointment->getFormattedDateAttribute(),
                'Dans' => $appointment->getTimeUntilAppointment(),
                'Téléphone' => $appointment->patient->user->phone ?? 'N/A',
                'Statut' => $appointment->status
            ];
        });

        // Afficher le tableau
        $this->table(
            ['ID', 'Patient', 'Médecin', 'Date RDV', 'Dans', 'Téléphone', 'Statut'],
            $tableData
        );

        $this->newLine();
        $this->info("💡 Pour envoyer réellement ces rappels, relancez sans --dry-run");
        
        return $totalCount;
    }

    private function sendReminders($query, string $jobType, string $reminderField, int $totalCount): int
    {
        $this->info("🚀 Envoi en cours...");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalCount);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->start();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        try {
            $query->with(['patient.user', 'employee'])
                ->chunk(50, function ($appointments) use (&$successCount, &$errorCount, &$skippedCount, $jobType, $reminderField, $bar) {
                    foreach ($appointments as $appointment) {
                        try {
                            // Vérifier que le patient a un téléphone
                            if (!$appointment->patient->user->phone) {
                                $skippedCount++;
                                Log::warning("Téléphone manquant", [
                                    'appointment_id' => $appointment->id,
                                    'patient_id' => $appointment->patient_id
                                ]);
                                $bar->advance();
                                continue;
                            }

                            // Petit délai pour éviter le spam
                            if ($successCount > 0) {
                                usleep(rand(500000, 1500000)); // 0.5 à 1.5 secondes
                            }

                            // 1. D'ABORD envoyer le SMS
                            SendAppointmentReminderJob::dispatchSync($appointment, $jobType);
                            
                            // 2. SI ça marche, ALORS marquer comme envoyé
                            $appointment->update([$reminderField => now()]);
                            
                            $successCount++;
                            
                            Log::info("SMS envoyé avec succès", [
                                'appointment_id' => $appointment->id,
                                'patient' => $appointment->patient->getFullNameAttribute(),
                                'type' => $jobType
                            ]);
                            
                        } catch (\Exception $e) {
                            $errorCount++;
                            
                            // Le RDV n'a PAS été marqué comme envoyé, donc il sera retenté
                            Log::error("Erreur envoi SMS", [
                                'appointment_id' => $appointment->id,
                                'patient' => $appointment->patient->getFullNameAttribute(),
                                'type' => $jobType,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                        
                        $bar->advance();
                    }
                });

        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->error("❌ Erreur critique : " . $e->getMessage());
            
            Log::error("Erreur critique dans processReminders", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 0;
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher le résumé
        $this->displaySummary($successCount, $errorCount, $skippedCount);

        return $successCount;
    }

    private function displaySummary(int $successCount, int $errorCount, int $skippedCount): void
    {
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 RÉSUMÉ");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        if ($successCount > 0) {
            $this->info("✅ Rappels envoyés : {$successCount}");
        }

        if ($skippedCount > 0) {
            $this->warn("⏭️  Rappels ignorés : {$skippedCount} (téléphone manquant)");
        }

        if ($errorCount > 0) {
            $this->error("❌ Erreurs : {$errorCount}");
            $this->comment("💡 Consultez les logs pour plus de détails : storage/logs/laravel.log");
        }

        if ($errorCount === 0 && $skippedCount === 0) {
            $this->info("🎉 Tous les rappels ont été traités avec succès !");
        }
    }
}