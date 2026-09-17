<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Enums\Labo\StatutExamen;
use App\Models\Labo\LaboAlerteCritique;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboResultat;
use App\Services\Labo\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ValidationController extends Controller
{
    public function __construct(private ValidationService $validation)
    {
    }

    /**
     * Deux files : à valider techniquement (résultats saisis) et à valider
     * biologiquement. Le biologiste voit TOUT le dossier du patient, pas un
     * examen isolé : c'est la cohérence d'ensemble qu'il valide.
     */
    public function index(Request $request)
    {
        $charger = ['demande.patient', 'examen.section', 'resultats.alertes', 'germesIsoles.antibiogramme'];

        $aValiderTechnique = LaboDemandeExamen::with($charger)
            ->where('statut', StatutExamen::EN_COURS->value)
            ->oldest('updated_at')->limit(50)->get();

        $aValiderBiologique = $request->user()->can('labo.validation.biologique')
            ? LaboDemandeExamen::with($charger)->where('statut', StatutExamen::VALIDE_TECHNIQUE->value)
                ->oldest('valide_technique_le')->limit(100)->get()->groupBy('demande_id')
            : collect();

        return view('labo.validation.index', [
            'aValiderTechnique' => $aValiderTechnique,
            'aValiderBiologique' => $aValiderBiologique,
            'moyens' => LaboAlerteCritique::MOYENS,
        ]);
    }

    public function technique(Request $request, LaboDemandeExamen $laboLigne)
    {
        $this->validation->validerTechnique($laboLigne, $request->user());

        return back()->with('success', "« {$laboLigne->examen_nom} » validé techniquement.");
    }

    public function biologique(Request $request, LaboDemandeExamen $laboLigne)
    {
        $this->validation->validerBiologique($laboLigne, $request->user(), $this->commentaire($request));

        return back()->with('success', "« {$laboLigne->examen_nom} » validé biologiquement — prêt à publier.");
    }

    /** Petit labo : le biologiste est aussi le technicien. Les deux signatures restent tracées. */
    public function complete(Request $request, LaboDemandeExamen $laboLigne)
    {
        $this->validation->validerTechniqueEtBiologique($laboLigne, $request->user(), $this->commentaire($request));

        return back()->with('success', "« {$laboLigne->examen_nom} » validé (technique + biologique).");
    }

    public function renvoyer(Request $request, LaboDemandeExamen $laboLigne)
    {
        $motif = $request->validate(['motif' => ['required', 'string', 'max:255']])['motif'];
        $this->validation->refuserTechnique($laboLigne, $motif);

        return back()->with('success', "« {$laboLigne->examen_nom} » renvoyé à la paillasse.");
    }

    public function rouvrir(Request $request, LaboDemandeExamen $laboLigne)
    {
        $motif = $request->validate(['motif' => ['required', 'string', 'min:5', 'max:255']])['motif'];
        $this->validation->rouvrirPourRectification($laboLigne, $motif, $request->user());

        return redirect()->route('labo.paillasse.saisie', $laboLigne)
            ->with('success', 'Examen rouvert. Après correction et nouvelle validation, publiez : un compte rendu RECTIFICATIF sera émis.');
    }

    public function alerteCritique(Request $request, LaboResultat $laboResultat)
    {
        $donnees = $request->validate([
            'personne_contactee' => ['required', 'string', 'max:255'],
            'moyen' => ['required', Rule::in(array_keys(LaboAlerteCritique::MOYENS))],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        $this->validation->signalerCritique($laboResultat, $donnees, $request->user());

        return back()->with('success', "Appel tracé pour « {$laboResultat->libelle} ».");
    }

    private function commentaire(Request $request): ?string
    {
        return $request->validate(['commentaire' => ['nullable', 'string', 'max:2000']])['commentaire'] ?? null;
    }
}
