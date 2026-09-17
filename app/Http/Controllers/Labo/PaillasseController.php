<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Enums\Labo\StatutExamen;
use App\Models\Labo\LaboAntibiogramme;
use App\Models\Labo\LaboAntibiotique;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboGerme;
use App\Models\Labo\LaboSection;
use App\Services\Labo\ResultatService;
use App\Services\Labo\ValidationService;
use App\Support\Labo\ContexteLabo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaillasseController extends Controller
{
    public function __construct(private ResultatService $resultats)
    {
    }

    /** Liste de travail par section (paillasse) : examens reçus, en cours ou renvoyés par le biologiste. */
    public function index(Request $request)
    {
        $sectionId = $request->integer('section') ?: null;

        $lignes = LaboDemandeExamen::with(['demande.patient', 'examen.section', 'echantillons'])
            ->whereIn('labo_demande_examens.statut', [StatutExamen::RECU->value, StatutExamen::EN_COURS->value])
            ->where(fn ($q) => $q->where('labo_demande_examens.sous_traite', false)->orWhereNotNull('labo_demande_examens.envoye_sous_traitant_le'))
            ->when($sectionId, fn ($q, $id) => $q->whereHas('examen', fn ($e) => $e->where('section_id', $id)))
            ->join('labo_demandes', 'labo_demandes.id', '=', 'labo_demande_examens.demande_id')
            ->orderByDesc('labo_demandes.urgence')->orderBy('labo_demande_examens.created_at')
            ->select('labo_demande_examens.*')
            ->paginate(40)->withQueryString();

        return view('labo.paillasse.index', [
            'lignes' => $lignes,
            'sections' => LaboSection::where('actif', true)->orderBy('ordre')->get(),
            'sectionId' => $sectionId,
        ]);
    }

    public function saisie(LaboDemandeExamen $laboLigne, ValidationService $validation)
    {
        $laboLigne->load([
            'demande.patient', 'examen.parametres.valeursReference', 'resultats.alertes',
            'echantillons', 'germesIsoles.antibiogramme',
        ]);

        $bacterio = $laboLigne->examen->estBacteriologie();

        return view('labo.paillasse.saisie', [
            'ligne' => $laboLigne,
            'demande' => $laboLigne->demande,
            'resultats' => $laboLigne->resultats->keyBy('parametre_id'),
            'anteriorites' => $this->resultats->anteriorites($laboLigne),
            'ageTexte' => ContexteLabo::ageTexte(ContexteLabo::ageEnJours($laboLigne->demande->patient, $laboLigne->demande->created_at)),
            'critiquesNonSignales' => $validation->critiquesNonSignales($laboLigne),
            'germes' => $bacterio ? LaboGerme::where('actif', true)->orderBy('nom')->get() : collect(),
            'antibiotiques' => $bacterio ? LaboAntibiotique::where('actif', true)->orderBy('nom')->get() : collect(),
            'interpretations' => LaboAntibiogramme::INTERPRETATIONS,
        ]);
    }

    public function enregistrer(Request $request, LaboDemandeExamen $laboLigne, ValidationService $validation)
    {
        $saisies = $request->validate([
            'valeurs' => ['array'],
            'valeurs.*' => ['nullable', 'string', 'max:500'],
        ])['valeurs'] ?? [];

        $critiques = $this->resultats->enregistrer($laboLigne, $saisies, $request->user());

        if ($request->boolean('valider_technique') && $request->user()->can('labo.validation.technique')) {
            $validation->validerTechnique($laboLigne->fresh(), $request->user());
            $message = 'Résultats enregistrés et validés techniquement.';
        } else {
            $message = 'Résultats enregistrés.';
        }

        $redirection = redirect()->route('labo.paillasse.saisie', $laboLigne)->with('success', $message);

        return $critiques->isNotEmpty()
            ? $redirection->with('error', 'VALEUR(S) CRITIQUE(S) : ' . $critiques->pluck('libelle')->implode(', ') . ' — prévenez le prescripteur et tracez l\'appel.')
            : $redirection;
    }

    public function enregistrerBacteriologie(Request $request, LaboDemandeExamen $laboLigne)
    {
        $donnees = $request->validate([
            'germes' => ['array', 'max:5'],
            'germes.*.germe_id' => ['nullable', 'integer'],
            'germes.*.numeration' => ['nullable', 'string', 'max:100'],
            'germes.*.antibiogramme' => ['array'],
            'germes.*.antibiogramme.*.interpretation' => ['nullable', Rule::in(array_keys(LaboAntibiogramme::INTERPRETATIONS))],
            'germes.*.antibiogramme.*.valeur' => ['nullable', 'string', 'max:30'],
            // Paramètres « classiques » d'un ECBU (cytologie, aspect…) saisis sur la même page.
            'valeurs' => ['array'],
            'valeurs.*' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($donnees['valeurs'])) {
            $this->resultats->enregistrer($laboLigne, $donnees['valeurs'], $request->user());
        }

        $this->resultats->enregistrerBacteriologie($laboLigne->fresh(), $donnees['germes'] ?? [], $request->user());

        return redirect()->route('labo.paillasse.saisie', $laboLigne)->with('success', 'Bactériologie enregistrée.');
    }
}
