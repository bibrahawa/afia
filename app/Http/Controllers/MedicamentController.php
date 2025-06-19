<?php

// app/Http/Controllers/MedicamentController.php
namespace App\Http\Controllers;

use App\Models\Medicament;
use Illuminate\Http\Request;

class MedicamentController extends Controller
{
    public function index() {
        $medicaments = Medicament::all();
        return view('medicaments.index', compact('medicaments'));
    }

    public function create() {
        return view('medicaments.create');
    }

    public function store(Request $request) {
        $request->validate([
            'nom' => 'required|string|max:255',
        ]);

        Medicament::create($request->all());
        return redirect()->route('medicaments.index')->with('success', 'Médicament ajouté.');
    }

    public function edit(Medicament $medicament) {
        return view('medicaments.edit', compact('medicament'));
    }

    public function update(Request $request, Medicament $medicament) {
        $request->validate([
            'nom' => 'required|string|max:255',
        ]);

        $medicament->update($request->all());
        return redirect()->route('medicaments.index')->with('success', 'Médicament modifié.');
    }

    public function destroy(Medicament $medicament) {
        $medicament->delete();
        return redirect()->route('medicaments.index')->with('success', 'Médicament supprimé.');
    }
}
