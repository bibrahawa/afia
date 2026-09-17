<?php

namespace App\Console\Commands\Assurance;

use App\Models\Assurance\Beneficiaire;
use App\Services\Assurance\ProjectionCouvertureService;
use Illuminate\Console\Command;

/**
 * Recalcule toutes les couvertures projetées (ex. après correction en masse
 * des dates de naissance, dont dépend la fin des droits des enfants).
 */
class SynchroniserCouvertures extends Command
{
    protected $signature = 'assurance:synchroniser-couvertures';

    protected $description = 'Recalcule les couvertures (patient_insurances) à partir du référentiel assurance';

    public function handle(ProjectionCouvertureService $projection): int
    {
        $n = 0;

        Beneficiaire::withoutGlobalScopes()->with('patient', 'adhesion.formule.contrat')->chunkById(200, function ($lot) use ($projection, &$n) {
            foreach ($lot as $beneficiaire) {
                $projection->synchroniser($beneficiaire);
                $n++;
            }
        });

        $this->info("{$n} couverture(s) synchronisée(s).");

        return self::SUCCESS;
    }
}
