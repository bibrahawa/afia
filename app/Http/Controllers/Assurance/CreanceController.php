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
    public function index(ReglementAssuranceService $reglements)
    {
        $organismes = InsuranceCompany::orderBy('name')->get();

        $lignes = $organismes->map(function (InsuranceCompany $o) use ($reglements) {
            $ouvertes = $reglements->reclamationsOuvertes($o);

            return [
                'organisme' => $o,
                'a_envoyer' => InsuranceClaim::where('insurance_company_id', $o->id)->where('status', 'draft')->whereNull('bordereau_id')->count(),
                'nb_ouvertes' => $ouvertes->count(),
                'reste_du' => round($ouvertes->sum(fn (InsuranceClaim $c) => $c->resteDu()), 2),
                'ecarts' => round($ouvertes->sum(fn (InsuranceClaim $c) => $c->ecartEnAttente()), 2),
                'plus_ancienne' => $ouvertes->min(fn (InsuranceClaim $c) => $c->submission_date ?? $c->created_at),
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
