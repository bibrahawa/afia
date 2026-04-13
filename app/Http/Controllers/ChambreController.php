<?php

// app/Http/Controllers/ChambreController.php
namespace App\Http\Controllers;

use App\Models\Chambre;
use Illuminate\Http\Request;

class ChambreController extends Controller
{
    public function index()
    {
        $chambres = Chambre::latest()->get();
        return view('chambres.index', compact('chambres'));
    }

    public function create()
    {
        return view('chambres.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero' => 'required|unique:chambres',
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
            'numero' => 'required|unique:chambres,numero,' . $chambre->id,
            'type' => 'required|string',
            'prix_par_jour' => 'required|numeric|min:0',
            'statut' => 'required|in:Libre,Occupée,En maintenance',
        ]);

        $chambre->update($request->all());

        return redirect()->route('chambres.index')->with('success', 'Chambre mise à jour.');
    }

    public function destroy(Chambre $chambre)
    {
        $chambre->delete();
        $chambre->hospitalisations()->delete();
        return redirect()->route('chambres.index')->with('success', 'Chambre supprimée.');
    }
}
