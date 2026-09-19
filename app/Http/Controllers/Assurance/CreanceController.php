<?php

namespace App\Http\Controllers\Assurance;

use App\Http\Controllers\Controller;
use App\Models\Assurance\Bordereau;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceSettlement;
use App\Services\Assurance\BordereauService;
use App\Services\Assurance\ReglementAssuranceService;

/**
 * Créances sur les organismes payeurs : ce qui reste à envoyer, à encaisser,
 * les écarts en attente. Remplace l'écran « Soldes » (calcul par facture).
 */
class CreanceController extends Controller
{
    /**
     * Une seule requête agrégée sur les montants stockés (reste_du_calcule,
     * ecart_calcule) au lieu de recalculer chaque réclamation : l'écran reste
     * instantané quel que soit le nombre de réclamations ouvertes.
     * Mêmes règles qu'avant : « ouverte » = non réglée avec un reste dû ≥ 0,01 ;
     * « à envoyer » = brouillon hors bordereau.
     */
    public function index()
    {
        $ouverte = "status <> 'paid' AND reste_du_calcule >= 0.01";

        $agregats = InsuranceClaim::query()
            ->selectRaw('insurance_company_id')
            ->selectRaw("SUM(CASE WHEN status = 'draft' AND bordereau_id IS NULL THEN 1 ELSE 0 END) AS a_envoyer")
            ->selectRaw("SUM(CASE WHEN {$ouverte} THEN 1 ELSE 0 END) AS nb_ouvertes")
            ->selectRaw("SUM(CASE WHEN {$ouverte} THEN reste_du_calcule ELSE 0 END) AS reste_du")
            ->selectRaw("SUM(CASE WHEN {$ouverte} THEN ecart_calcule ELSE 0 END) AS ecarts")
            ->selectRaw("MIN(CASE WHEN {$ouverte} THEN COALESCE(submission_date, DATE(created_at)) END) AS plus_ancienne")
            ->where(fn ($q) => $q->where('status', '<>', 'paid')->orWhereNull('status'))
            ->groupBy('insurance_company_id')
            ->get()
            ->keyBy('insurance_company_id');

        $organismes = InsuranceCompany::whereIn('id', $agregats->keys())->orderBy('name')->get();

        $lignes = $organismes->map(function (InsuranceCompany $o) use ($agregats) {
            $a = $agregats->get($o->id);

            return [
                'organisme' => $o,
                'a_envoyer' => (int) $a->a_envoyer,
                'nb_ouvertes' => (int) $a->nb_ouvertes,
                'reste_du' => round((float) $a->reste_du, 2),
                'ecarts' => round((float) $a->ecarts, 2),
                'plus_ancienne' => $a->plus_ancienne ? \Illuminate\Support\Carbon::parse($a->plus_ancienne) : null,
            ];
        })->filter(fn ($l) => $l['nb_ouvertes'] > 0 || $l['a_envoyer'] > 0)->sortByDesc('reste_du')->values();

        return view('assurance.creances.index', ['lignes' => $lignes]);
    }

    public function show(InsuranceCompany $assuranceOrganisme, ReglementAssuranceService $reglements, BordereauService $bordereaux)
    {
        $ouvertes = $reglements->reclamationsOuvertes($assuranceOrganisme);

        return view('assurance.creances.show', [
            'organisme' => $assuranceOrganisme,
            'ouvertes' => $ouvertes,
            'aEnvoyer' => $bordereaux->reclamationsAEnvoyer($assuranceOrganisme, null, null)->count(),
            'bordereaux' => Bordereau::where('insurance_company_id', $assuranceOrganisme->id)->withCount('reclamations')->latest()->limit(20)->get(),
            'reglements' => InsuranceSettlement::where('insurance_company_id', $assuranceOrganisme->id)->latest()->limit(20)->get(),
            'anciennete' => $reglements->anciennete($assuranceOrganisme),
        ]);
    }
}
