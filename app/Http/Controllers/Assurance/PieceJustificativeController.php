<?php

namespace App\Http\Controllers\Assurance;

use App\Http\Controllers\Controller;
use App\Models\Assurance\PieceJustificative;
use App\Models\InsuranceClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PieceJustificativeController extends Controller
{
    public function store(Request $request, InsuranceClaim $insuranceClaim)
    {
        $donnees = $request->validate([
            'type' => ['required', Rule::in(array_keys(PieceJustificative::TYPES))],
            'libelle' => ['nullable', 'string', 'max:255'],
            'fichier' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ], [
            'fichier.required' => 'Choisissez le fichier à joindre.',
            'fichier.mimes' => 'Formats acceptés : PDF, JPG, PNG, WEBP.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $fichier = $request->file('fichier');
        $chemin = $fichier->store('assurance/pieces/' . $insuranceClaim->etablissement_id . '/' . $insuranceClaim->id, PieceJustificative::DISQUE);

        PieceJustificative::create([
            'insurance_claim_id' => $insuranceClaim->id,
            'type' => $donnees['type'],
            'libelle' => $donnees['libelle'] ?? null,
            'chemin' => $chemin,
            'nom_original' => mb_substr($fichier->getClientOriginalName(), 0, 255),
            'mime' => $fichier->getMimeType(),
            'taille' => (int) $fichier->getSize(),
            'ajoute_par' => $request->user()?->id,
        ]);

        return back()->with('success', 'Pièce jointe à la réclamation.');
    }

    /** {assurancePiece} est résolu via le global scope : une pièce d'une autre clinique donne un 404. */
    public function telecharger(PieceJustificative $assurancePiece)
    {
        abort_unless(Storage::disk(PieceJustificative::DISQUE)->exists($assurancePiece->chemin), 404, 'Fichier introuvable.');

        return Storage::disk(PieceJustificative::DISQUE)->response($assurancePiece->chemin, $assurancePiece->nom_original);
    }

    public function destroy(PieceJustificative $assurancePiece)
    {
        $assurancePiece->delete();

        return back()->with('success', 'Pièce supprimée.');
    }
}
