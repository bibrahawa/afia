<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Models\Labo\LaboCreancePartenaire;
use App\Models\Labo\LaboRelevePartenaire;
use App\Services\Labo\FacturationPartenaireService;
use App\Support\Etablissement\IdentiteDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RelevePartenaireController extends Controller
{
    public function __construct(private FacturationPartenaireService $facturation)
    {
    }

    public function show(LaboRelevePartenaire $laboReleve)
    {
        return view('labo.releves.show', [
            'releve' => $laboReleve->load(['partenariat.clinique', 'creances.demande.patient', 'creances.demande.examens']),
        ]);
    }

    public function imprimer(LaboRelevePartenaire $laboReleve)
    {
        return view('labo.releves.imprimer', [
            'releve' => $laboReleve->load(['partenariat.clinique', 'creances.demande.patient', 'creances.demande.examens']),
            'identite' => IdentiteDocument::courante(),
        ]);
    }

    public function envoyer(Request $request, LaboRelevePartenaire $laboReleve)
    {
        $donnees = $request->validate(['date_envoi' => ['nullable', 'date', 'before_or_equal:today']]);

        $this->facturation->envoyerReleve($laboReleve, ! empty($donnees['date_envoi']) ? Carbon::parse($donnees['date_envoi']) : null);

        return back()->with('success', 'Relevé marqué envoyé : les montants sont figés.');
    }

    public function rouvrir(LaboRelevePartenaire $laboReleve)
    {
        $this->facturation->rouvrirReleve($laboReleve);

        return back()->with('success', 'Relevé rouvert : vous pouvez le corriger puis le renvoyer.');
    }

    public function retirer(LaboCreancePartenaire $laboCreance)
    {
        $releve = $this->facturation->retirerDuReleve($laboCreance);

        return back()->with('success', 'Analyse retirée du relevé ' . $releve->numero . '.');
    }
}
