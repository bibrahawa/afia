<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Models\Labo\LaboCreancePartenaire;
use App\Models\Labo\LaboPartenariat;
use App\Models\Labo\LaboRelevePartenaire;
use App\Models\Labo\LaboReglementPartenaire;
use App\Services\Labo\FacturationPartenaireService;
use App\Support\Etablissement\IdentiteDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Côté laboratoire : ce que les cliniques partenaires lui doivent. */
class CreancePartenaireController extends Controller
{
    public function __construct(private FacturationPartenaireService $facturation)
    {
    }

    public function index()
    {
        $lignes = LaboPartenariat::with('clinique')->get()
            ->map(fn (LaboPartenariat $p) => ['partenariat' => $p, 'resume' => $this->facturation->resume($p)])
            ->filter(fn ($ligne) => $ligne['partenariat']->mode_facturation_defaut === 'partenaire' || $ligne['resume']['reste_du'] > 0)
            ->values();

        return view('labo.creances.index', ['lignes' => $lignes]);
    }

    public function show(LaboPartenariat $laboPartenariat)
    {
        return view('labo.creances.show', [
            'partenariat' => $laboPartenariat->load('clinique'),
            'resume' => $this->facturation->resume($laboPartenariat),
            'aFacturer' => LaboCreancePartenaire::where('partenariat_id', $laboPartenariat->id)
                ->where('statut', LaboCreancePartenaire::A_FACTURER)->whereNull('releve_id')->with('demande.patient')->get(),
            'releves' => LaboRelevePartenaire::where('partenariat_id', $laboPartenariat->id)->with('creances')->latest('id')->get(),
            'creancesOuvertes' => $this->facturation->creancesOuvertes($laboPartenariat),
            'reglements' => LaboReglementPartenaire::where('partenariat_id', $laboPartenariat->id)->with('imputations')->latest('id')->limit(20)->get(),
            'modes' => LaboReglementPartenaire::MODES,
        ]);
    }

    public function preparerReleve(Request $request, LaboPartenariat $laboPartenariat)
    {
        $donnees = $request->validate([
            'periode_debut' => ['required', 'date'],
            'periode_fin' => ['required', 'date', 'after_or_equal:periode_debut'],
        ]);

        $releve = $this->facturation->preparerReleve(
            $laboPartenariat,
            Carbon::parse($donnees['periode_debut']),
            Carbon::parse($donnees['periode_fin']),
            $request->user()
        );

        return redirect()->route('labo.releves.show', $releve)->with('success', "Relevé {$releve->numero} préparé.");
    }

    public function enregistrerReglement(Request $request, LaboPartenariat $laboPartenariat)
    {
        $donnees = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'mode' => ['required', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
            'recu_le' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'imputations' => ['nullable', 'array'],
            'imputations.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $imputations = collect($donnees['imputations'] ?? [])->filter(fn ($m) => (float) $m > 0)->all();

        $this->facturation->enregistrerReglement($laboPartenariat, $donnees, $imputations, $request->user());

        return back()->with('success', 'Règlement enregistré et imputé.');
    }
}
