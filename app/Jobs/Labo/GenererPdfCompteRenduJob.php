<?php

namespace App\Jobs\Labo;

use App\Models\Labo\LaboCompteRendu;
use App\Services\Labo\CompteRenduService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Génération PDF hors requête HTTP (dompdf est lent sur mutualisé). Si le
 * worker n'est pas encore passé quand quelqu'un télécharge,
 * CompteRenduService::pdf() génère à la volée : ce job est une
 * optimisation, pas une dépendance.
 */
class GenererPdfCompteRenduJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [30, 120];

    public function __construct(public int $compteRenduId)
    {
    }

    public function handle(CompteRenduService $service): void
    {
        // Aucun établissement en contexte dans un worker : lecture explicite.
        $cr = LaboCompteRendu::withoutGlobalScope('etablissement')->find($this->compteRenduId);

        if ($cr && ! $cr->pdf_path) {
            $service->genererPdf($cr);
        }
    }
}
