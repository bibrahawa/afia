<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Enums\Labo\StatutDemande;
use App\Enums\Labo\StatutEchantillon;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboEchantillon;
use App\Services\Labo\PrelevementService;
use App\Support\Labo\ContexteLabo;
use Illuminate\Http\Request;

class PrelevementController extends Controller
{
    public function __construct(private PrelevementService $prelevements)
    {
    }

    /** File d'attente du préleveur : demandes ayant au moins un contenant attendu, urgences en tête. */
    public function index()
    {
        $demandes = LaboDemande::with(['patient', 'echantillons' => fn ($q) => $q->where('statut', StatutEchantillon::ATTENDU->value), 'echantillons.examens'])
            ->whereHas('echantillons', fn ($q) => $q->where('statut', StatutEchantillon::ATTENDU->value))
            ->where('statut', '!=', StatutDemande::ANNULEE->value)
            ->orderByDesc('urgence')->oldest()
            ->paginate(30);

        return view('labo.prelevements.index', ['demandes' => $demandes]);
    }

    /**
     * « Prélevé » simple (salle de prélèvement distincte du plateau) ou
     * « prélevé et reçu » en un clic (petit labo où la même personne fait les deux).
     */
    public function preleve(Request $request, LaboEchantillon $laboEchantillon)
    {
        if ($request->boolean('et_recu') && $request->user()->can('labo.reception')) {
            $this->prelevements->marquerPreleveEtRecu($laboEchantillon, $request->user());

            return back()->with('success', "Contenant {$laboEchantillon->code_barres} prélevé et réceptionné.");
        }

        $this->prelevements->marquerPreleve($laboEchantillon, $request->user());

        return back()->with('success', "Contenant {$laboEchantillon->code_barres} prélevé.");
    }

    /** Planche d'étiquettes code-barres (une par contenant non rejeté), pour impression thermique ou A4. */
    public function etiquettes(LaboDemande $laboDemande)
    {
        ContexteLabo::verifierAppartenance($laboDemande);

        $laboDemande->load(['patient', 'echantillons' => fn ($q) => $q->where('statut', '!=', StatutEchantillon::REJETE->value), 'echantillons.examens']);

        ContexteLabo::journaliser('etiquettes_imprimees', $laboDemande);

        return view('labo.etiquettes', [
            'demande' => $laboDemande,
            'ageTexte' => ContexteLabo::ageTexte(ContexteLabo::ageEnJours($laboDemande->patient)),
        ]);
    }
}
