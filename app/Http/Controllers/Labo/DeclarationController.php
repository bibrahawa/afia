<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Models\Labo\LaboDeclarationMdo;
use App\Services\Labo\DeclarationMdoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeclarationController extends Controller
{
    public function index(Request $request)
    {
        $statut = $request->validate(['statut' => ['nullable', Rule::in(array_keys(LaboDeclarationMdo::STATUTS))]])['statut'] ?? LaboDeclarationMdo::A_DECLARER;

        $declarations = LaboDeclarationMdo::with(['demandeExamen.demande.patient', 'demandeExamen.demande.prescripteur', 'declareePar'])
            ->where('statut', $statut)
            ->orderByDesc('immediate')->oldest()
            ->paginate(30)->withQueryString();

        return view('labo.declarations.index', [
            'declarations' => $declarations,
            'statut' => $statut,
            'compteurs' => LaboDeclarationMdo::selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut'),
        ]);
    }

    public function marquerDeclaree(Request $request, LaboDeclarationMdo $laboDeclarationMdo, DeclarationMdoService $service)
    {
        $donnees = $request->validate([
            'destinataire' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'declaree_le' => ['nullable', 'date', 'before_or_equal:now'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->marquerDeclaree($laboDeclarationMdo, $donnees, $request->user());

        return back()->with('success', "Déclaration « {$laboDeclarationMdo->maladie} » enregistrée.");
    }
}
