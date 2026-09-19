<?php

namespace App\Console\Commands\Assurance;

use App\Models\InsuranceClaim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcule le reste dû et l'écart stockés sur les réclamations.
 * Filet de sécurité nocturne (un encaissement fait hors du circuit des
 * réclamations peut modifier le reste dû sans enregistrer la réclamation).
 *
 *   php artisan assurance:recalculer-creances          (réclamations non réglées)
 *   php artisan assurance:recalculer-creances --toutes (y compris réglées)
 */
class RecalculerCreances extends Command
{
    protected $signature = 'assurance:recalculer-creances {--toutes : inclure les réclamations déjà réglées}';

    protected $description = 'Recalcule le reste dû et l\'écart stockés sur chaque réclamation d\'assurance';

    public function handle(): int
    {
        $modifiees = 0;
        $total = 0;

        InsuranceClaim::withoutGlobalScopes()
            ->when(! $this->option('toutes'), fn ($q) => $q->where('status', '!=', 'paid')->orWhere('reste_du_calcule', '>', 0))
            ->with('invoice.transaction')
            ->chunkById(200, function ($reclamations) use (&$modifiees, &$total) {
                foreach ($reclamations as $r) {
                    $total++;
                    [$reste, $ecart] = $r->montantsCalcules();
                    if (abs($reste - (float) $r->reste_du_calcule) >= 0.01 || abs($ecart - (float) $r->ecart_calcule) >= 0.01 || ! $r->montants_calcules_le) {
                        // Écriture directe : ni événements ni garde d'établissement (tâche de fond, toutes cliniques).
                        DB::table('insurance_claims')->where('id', $r->id)->update([
                            'reste_du_calcule' => $reste,
                            'ecart_calcule' => $ecart,
                            'montants_calcules_le' => now(),
                        ]);
                        $modifiees++;
                    }
                }
            });

        $this->info("{$total} réclamation(s) vérifiée(s), {$modifiees} mise(s) à jour.");

        return self::SUCCESS;
    }
}
