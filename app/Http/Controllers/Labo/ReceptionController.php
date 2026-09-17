<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Enums\Labo\StatutDemande;
use App\Enums\Labo\StatutEchantillon;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboEchantillon;
use App\Services\Labo\PrelevementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReceptionController extends Controller
{
    public function __construct(private PrelevementService $prelevements)
    {
    }

    public function index()
    {
        $enAttente = LaboEchantillon::with(['demande.patient', 'examens', 'preleveur'])
            ->whereIn('statut', [StatutEchantillon::PRELEVE->value, StatutEchantillon::ATTENDU->value])
            ->whereHas('demande', fn ($q) => $q->where('statut', '!=', StatutDemande::ANNULEE->value))
            ->orderByRaw("FIELD(statut, 'preleve', 'attendu')")
            ->oldest()->limit(100)->get();

        $recusAujourdhui = LaboEchantillon::with('demande.patient')
            ->whereDate('recu_le', today())->latest('recu_le')->limit(30)->get();

        $rejetsAujourdhui = LaboEchantillon::with('demande.patient')
            ->whereDate('rejete_le', today())->latest('rejete_le')->get();

        // Examens sous-traités reçus mais pas encore partis au labo partenaire.
        $sousTraitance = LaboDemandeExamen::with('demande.patient')
            ->where('sous_traite', true)->whereNull('envoye_sous_traitant_le')
            ->whereIn('statut', ['recu', 'en_cours'])->get();

        return view('labo.reception.index', [
            'enAttente' => $enAttente,
            'recusAujourdhui' => $recusAujourdhui,
            'rejetsAujourdhui' => $rejetsAujourdhui,
            'sousTraitance' => $sousTraitance,
            'motifsRejet' => StatutEchantillon::motifsRejet(),
        ]);
    }

    /** Douchette code-barres : le lecteur « tape » le code puis Entrée. */
    public function scanner(Request $request)
    {
        $code = trim($request->validate(['code_barres' => ['required', 'string', 'max:30']])['code_barres']);

        $echantillon = LaboEchantillon::where('code_barres', $code)->first(); // scopé : un code d'un autre établissement est « inconnu »
        if (! $echantillon) {
            return back()->with('error', "Code-barres {$code} inconnu dans cet établissement.");
        }

        $this->prelevements->receptionner($echantillon, $request->user());

        return back()->with('success', "Reçu : {$echantillon->libelleContenant()} — {$echantillon->demande->numero}.");
    }

    public function recu(Request $request, LaboEchantillon $laboEchantillon)
    {
        $this->prelevements->receptionner($laboEchantillon, $request->user());

        return back()->with('success', "Contenant {$laboEchantillon->code_barres} réceptionné.");
    }

    public function rejeter(Request $request, LaboEchantillon $laboEchantillon)
    {
        $donnees = $request->validate([
            'motif' => ['required', Rule::in(array_keys(StatutEchantillon::motifsRejet()))],
            'commentaire' => ['nullable', 'required_if:motif,autre', 'string', 'max:500'],
        ], ['commentaire.required_if' => 'Précisez le motif du rejet.']);

        $remplacement = $this->prelevements->rejeter($laboEchantillon, $donnees['motif'], $donnees['commentaire'] ?? null, $request->user());

        return back()->with('success', "Échantillon rejeté. Nouveau contenant à prélever : {$remplacement->code_barres}.");
    }

    public function envoiSousTraitance(LaboDemandeExamen $laboLigne)
    {
        $this->prelevements->marquerEnvoiSousTraitance($laboLigne);

        return back()->with('success', "« {$laboLigne->examen_nom} » envoyé à {$laboLigne->laboratoire_sous_traitant}.");
    }
}
