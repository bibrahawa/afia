<?php

// app/Http/Controllers/MedicamentController.php
namespace App\Http\Controllers;

use App\Models\Medicament;
use Illuminate\Http\Request;

class MedicamentController extends Controller
{
    public function index() {
        $medicaments = Medicament::orderBy('nom')->get();
        return view('medicaments.index', compact('medicaments'));
    }

    public function create() {
        return view('medicaments.create');
    }

    public function store(Request $request) {
        $request->validate([
            'nom' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
        ], ['nom.required' => 'Indiquez le nom du médicament.']);

        Medicament::create($request->only(['nom', 'forme', 'dosage', 'frequence', 'duree', 'amount', 'instructions']));
        return redirect()->route('medicaments.index')->with('success', 'Médicament ajouté.');
    }

    public function edit(Medicament $medicament) {
        return view('medicaments.edit', compact('medicament'));
    }

    public function update(Request $request, Medicament $medicament) {
        $request->validate([
            'nom' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
        ], ['nom.required' => 'Indiquez le nom du médicament.']);

        $medicament->update($request->only(['nom', 'forme', 'dosage', 'frequence', 'duree', 'amount', 'instructions']));
        return redirect()->route('medicaments.index')->with('success', 'Médicament modifié.');
    }

    /** Lot S3 — Masquer / réafficher : plus proposé sur l'ordonnance, historique intact. */
    public function basculerVisibilite(Medicament $medicament)
    {
        $medicament->update(['actif' => ! $medicament->actif]);

        return back()->with('success', $medicament->actif
            ? "« {$medicament->nom} » est de nouveau proposé."
            : "« {$medicament->nom} » est masqué : il n'est plus proposé, les ordonnances passées restent intactes.");
    }

    public function destroy(Medicament $medicament) {
        // CORRIGÉ — un médicament déjà prescrit disparaissait des ordonnances et factures.
        if (\Illuminate\Support\Facades\DB::table('consultation_medicament')->where('medicament_id', $medicament->id)->exists()) {
            return redirect()->route('medicaments.index')->with('error', "« {$medicament->nom} » figure déjà sur des ordonnances : il ne peut pas être supprimé. Masquez-le : il ne sera plus proposé, les ordonnances restent intactes.");
        }

        $medicament->delete();
        return redirect()->route('medicaments.index')->with('success', 'Médicament supprimé.');
    }
}
