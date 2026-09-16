<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\MotifRdv;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MotifRdvController extends Controller
{
    public function getIndex()
    {
        $departments = Department::with(['motifsRdv' => fn ($q) => $q->orderBy('ordre_affichage')])->get();

        return view('motifs_rdv.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'nom' => ['required', 'string', 'max:255'],
            'duree_minutes_defaut' => ['required', 'integer', 'min:1', 'max:240'],
            'marge_tampon_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'couleur' => ['nullable', 'string', 'max:7'],
        ]);

        $data['code'] = Str::slug($data['nom']);
        $data['couleur'] = $data['couleur'] ?: '#3B82F6';

        MotifRdv::create($data);

        return back()->with('success', 'Motif créé.');
    }

    public function update(Request $request, MotifRdv $motifRdv)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'duree_minutes_defaut' => ['required', 'integer', 'min:1', 'max:240'],
            'marge_tampon_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'couleur' => ['nullable', 'string', 'max:7'],
            'actif' => ['sometimes', 'boolean'],
        ]);

        // Le département n'est volontairement pas modifiable après coup :
        // un motif qui change de département change de sens (pédiatrie ->
        // gynéco n'est pas une "édition", c'est une autre entité).
        $motifRdv->update($data);

        return back()->with('success', 'Motif mis à jour.');
    }

    public function delete(MotifRdv $motifRdv)
    {
        // Pas de vérification applicative ici : la contrainte
        // restrictOnDelete() côté base est le vrai filet de sécurité —
        // c'est elle qui empêche de perdre la traçabilité d'un rendez-vous
        // existant si un motif utilisé est supprimé par erreur.
        try {
            $motifRdv->delete();

            return back()->with('success', 'Motif supprimé.');
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "Impossible de supprimer : des rendez-vous existants utilisent ce motif. Désactive-le plutôt.");
        }
    }

    public function toggle(MotifRdv $motifRdv)
    {
        $motifRdv->update(['actif' => ! $motifRdv->actif]);

        return back()->with('success', $motifRdv->actif ? 'Motif réactivé.' : 'Motif désactivé.');
    }
}
