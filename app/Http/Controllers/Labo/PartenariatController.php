<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Models\Labo\LaboPartenariat;
use App\Services\Labo\PartenariatService;
use Illuminate\Http\Request;

/** Côté laboratoire : les cliniques autorisées à lui envoyer des demandes. */
class PartenariatController extends Controller
{
    public function __construct(private PartenariatService $partenariats)
    {
    }

    public function index()
    {
        $lignes = LaboPartenariat::with('clinique')->orderBy('statut')->get()
            ->map(fn (LaboPartenariat $p) => ['partenariat' => $p, 'activite' => $this->partenariats->activite($p)]);

        return view('labo.partenariats.index', [
            'lignes' => $lignes,
            'candidats' => $this->partenariats->candidats(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'clinique_id' => ['required', 'integer'],
            'mode_facturation_defaut' => ['required', 'in:patient,partenaire'],
            'clinique_facture_patient' => ['nullable', 'boolean'],
            'remise_pourcentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'delai_paiement_jours' => ['nullable', 'integer', 'min:0', 'max:180'],
            'contact_nom' => ['nullable', 'string', 'max:255'],
            'contact_telephone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['clinique_id.required' => 'Choisissez la clinique partenaire.']);

        $clinique = Etablissement::withoutGlobalScopes()->findOrFail($donnees['clinique_id']);
        $partenariat = $this->partenariats->creer($clinique, $donnees, $request->user());

        return back()->with('success', "Partenariat ouvert avec {$partenariat->clinique->nom} : elle peut désormais envoyer des demandes.");
    }

    public function update(Request $request, LaboPartenariat $laboPartenariat)
    {
        $donnees = $request->validate([
            'mode_facturation_defaut' => ['required', 'in:patient,partenaire'],
            'clinique_facture_patient' => ['nullable', 'boolean'],
            'remise_pourcentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'delai_paiement_jours' => ['nullable', 'integer', 'min:0', 'max:180'],
            'contact_nom' => ['nullable', 'string', 'max:255'],
            'contact_telephone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->partenariats->modifier($laboPartenariat, $donnees);

        return back()->with('success', 'Partenariat mis à jour.');
    }

    public function basculer(LaboPartenariat $laboPartenariat)
    {
        $partenariat = $this->partenariats->basculerStatut($laboPartenariat);

        return back()->with('success', $partenariat->estActif()
            ? 'Partenariat réactivé.'
            : 'Partenariat suspendu : la clinique ne peut plus envoyer de nouvelle demande.');
    }
}
