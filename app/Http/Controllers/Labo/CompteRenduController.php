<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Jobs\Labo\EnvoyerSmsResultatsJob;
use App\Models\Labo\LaboCompteRendu;
use App\Models\Labo\LaboDemande;
use App\Services\Labo\CompteRenduService;
use App\Support\Labo\ContexteLabo;
use Illuminate\Http\Request;

class CompteRenduController extends Controller
{
    public function __construct(private CompteRenduService $comptesRendus)
    {
    }

    public function publier(Request $request, LaboDemande $laboDemande)
    {
        $cr = $this->comptesRendus->publier($laboDemande, $request->user(), $request->boolean('notifier_patient', true));

        $message = $cr->est_rectificatif
            ? "Compte rendu RECTIFICATIF v{$cr->version} publié."
            : "Compte rendu v{$cr->version} publié" . ($cr->est_partiel ? ' (partiel).' : '.');

        if (! $laboDemande->fresh()->peutEtreRemisAuPatient()) {
            $message .= ' SMS patient retenu : part patient non réglée.';
        }

        return back()->with('success', $message);
    }

    public function renvoyerSms(Request $request, LaboDemande $laboDemande)
    {
        ContexteLabo::verifierAppartenance($laboDemande);

        $cr = $laboDemande->comptesRendus()->first();
        if (! $cr) {
            return back()->with('error', 'Aucun compte rendu publié pour cette demande.');
        }

        // Remise d'un résultat non réglé : réservée à la facturation, et tracée.
        if (! $laboDemande->peutEtreRemisAuPatient()) {
            if (! $request->user()->can('labo.facturation')) {
                return back()->with('error', 'Part patient non réglée : encaissez avant d\'envoyer les résultats.');
            }
            $laboDemande->update(['resultats_retenus_si_impaye' => false]);
            ContexteLabo::journaliser('remise_resultats_non_regles_autorisee', $laboDemande);
        }

        EnvoyerSmsResultatsJob::dispatch($cr->id, true);
        ContexteLabo::journaliser('sms_resultats_renvoye', $cr);

        return back()->with('success', 'SMS de résultats en cours d\'envoi.');
    }

    public function pdf(Request $request, LaboCompteRendu $laboCompteRendu)
    {
        $contenu = $this->comptesRendus->pdf($laboCompteRendu);
        ContexteLabo::journaliser('compte_rendu_imprime', $laboCompteRendu, "Version {$laboCompteRendu->version}");

        $nom = sprintf('CR-%s-v%d.pdf', $laboCompteRendu->contenu['demande']['numero'] ?? $laboCompteRendu->demande_id, $laboCompteRendu->version);

        return response($contenu, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('telecharger') ? 'attachment' : 'inline') . "; filename=\"{$nom}\"",
        ]);
    }
}
