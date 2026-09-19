<?php

// app/Http/Controllers/ChambreController.php
namespace App\Http\Controllers;

use App\Models\Chambre;
use Illuminate\Http\Request;

class ChambreController extends Controller
{
    public function index()
    {
        // Avec l'occupant éventuel : le plan des chambres affiche qui est dedans.
        $chambres = Chambre::with(['hospitalisations' => fn ($q) => $q->where('statut', 'En cours')->with('patient')])
            ->orderBy('numero')
            ->get();

        return view('chambres.index', compact('chambres'));
    }

    public function create()
    {
        return view('chambres.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero' => 'required|unique_etablissement:chambres,numero',
            'type' => 'required|string',
            'prix_par_jour' => 'required|numeric|min:0',
            'statut' => 'required|in:Libre,Occupée,En maintenance',
        ]);

        Chambre::create($request->all());

        return redirect()->route('chambres.index')->with('success', 'Chambre ajoutée avec succès.');
    }

    public function edit(Chambre $chambre)
    {
        return view('chambres.edit', compact('chambre'));
    }

    public function update(Request $request)
    {
        $chambre = Chambre::find($request->id);
        $request->validate([
            'numero' => 'required|unique_etablissement:chambres,numero,' . $chambre->id,
            'type' => 'required|string',
            'prix_par_jour' => 'required|numeric|min:0',
            'statut' => 'required|in:Libre,Occupée,En maintenance',
        ]);

        $chambre->update($request->all());

        return redirect()->route('chambres.index')->with('success', 'Chambre mise à jour.');
    }

    public function destroy(Chambre $chambre)
    {
        // CORRIGÉ — supprimait aussi TOUTES les hospitalisations passées de la
        // chambre (historique des patients et base de leur facturation).
        if ($chambre->hospitalisations()->exists()) {
            return redirect()->route('chambres.index')->with('error',
                'Cette chambre a déjà accueilli des patients : elle ne peut pas être supprimée. Passez-la « En maintenance » pour ne plus l\'attribuer.');
        }

        $chambre->delete();

        return redirect()->route('chambres.index')->with('success', 'Chambre supprimée.');
    }
}
