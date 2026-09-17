<?php

namespace App\Jobs\Labo;

use App\Models\Labo\LaboCompteRendu;
use App\Services\LienCourtService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Le SMS ne contient JAMAIS de résultat (téléphone partagé, perdu, lu par
 * un proche) : uniquement un lien court, signé et expirant.
 */
class EnvoyerSmsResultatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 30;

    public const VALIDITE_LIEN_JOURS = 30;

    public function __construct(public int $compteRenduId, public bool $forcer = false)
    {
    }

    public function handle(SmsService $sms, LienCourtService $liens): void
    {
        $cr = LaboCompteRendu::withoutGlobalScope('etablissement')->find($this->compteRenduId);
        if (! $cr || ($cr->sms_envoye_le && ! $this->forcer)) {
            return;
        }

        $demande = $cr->demande()->withoutGlobalScope('etablissement')
            ->with(['patient.comptesPatients', 'etablissement'])
            ->first();

        if (! $demande || ! $demande->peutEtreRemisAuPatient()) {
            return; // solde impayé : envoi manuel depuis la fiche demande après encaissement
        }

        $telephone = $demande->patient->telephone;
        if (! $telephone) {
            Log::info('Résultats labo : aucun téléphone patient', ['demande' => $demande->numero]);

            return;
        }

        $urlSignee = URL::temporarySignedRoute(
            'labo.public.resultats',
            now()->addDays(self::VALIDITE_LIEN_JOURS),
            ['demandeId' => $demande->id]
        );
        $lien = $liens->creer($urlSignee, now()->addDays(self::VALIDITE_LIEN_JOURS));

        // Sans accents : un seul accent fait passer le SMS en Unicode (70
        // caractères au lieu de 160) et double son coût.
        $etablissement = Str::ascii($demande->etablissement->nom);
        $message = $cr->est_rectificatif
            ? "{$etablissement}: un compte rendu RECTIFIE de vos analyses ({$demande->numero}) est disponible : {$lien}"
            : "{$etablissement}: les resultats de vos analyses ({$demande->numero}) sont disponibles : {$lien}";

        $retour = $sms->sendSms($telephone, $message);

        if ($retour['success'] ?? false) {
            $cr->update(['sms_envoye_le' => now()]);

            return;
        }

        Log::warning('Échec SMS résultats labo', ['demande' => $demande->numero, 'erreur' => $retour['error'] ?? null]);

        if ($this->attempts() < $this->tries) {
            $this->release(300);
        }
    }
}
