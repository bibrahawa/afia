<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Models\Labo\LaboCompteRendu;
use App\Models\Labo\LaboDemande;
use App\Services\Labo\CompteRenduService;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * Page ouverte par le patient depuis le lien SMS. Aucune session :
 * l'autorisation, c'est la signature + l'expiration de l'URL (middleware
 * signée, vérifiée en tête d'action). Pas de contexte établissement ici → withoutGlobalScope
 * explicite, sur l'identifiant contenu dans l'URL signée uniquement.
 */
class ResultatsPublicsController extends Controller
{
    public function afficher(Request $request, int $demandeId, CompteRenduService $service)
    {
        // Signature vérifiée ici plutôt que par le middleware « signed » : le
        // bouton « Télécharger » ajoute ?pdf=1, qui invaliderait la signature.
        abort_unless($request->hasValidSignatureWhileIgnoring(['pdf']), 403, 'Lien expiré ou invalide. Demandez un nouveau lien au laboratoire.');

        $demande = LaboDemande::withoutGlobalScope('etablissement')->with('etablissement')->findOrFail($demandeId);

        if (! $demande->peutEtreRemisAuPatient()) {
            abort(403, 'Résultats non disponibles. Veuillez contacter le laboratoire.');
        }

        $cr = LaboCompteRendu::withoutGlobalScope('etablissement')
            ->where('demande_id', $demande->id)->orderByDesc('version')->firstOrFail();

        ActivityLog::create([
            'etablissement_id' => $demande->etablissement_id,
            'action' => 'labo.resultats_consultes_patient',
            'subject_type' => LaboCompteRendu::class,
            'subject_id' => $cr->id,
            'description' => $request->query('source') === 'portail' ? 'Consultation depuis le portail patient' : 'Consultation via lien SMS',
            'proprietes' => ['version' => $cr->version],
            'ip_address' => $request->ip(),
        ]);

        if ($request->boolean('pdf')) {
            return response($service->pdf($cr), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="resultats-' . $demande->numero . '.pdf"',
                'Cache-Control' => 'no-store, private',
            ]);
        }

        return response()
            ->view('labo.public.resultats', ['cr' => $cr, 'c' => $cr->contenu])
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
