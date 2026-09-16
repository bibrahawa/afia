<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MotifRdv;
use Illuminate\Http\Request;

class MedecinMotifController extends Controller
{
    /**
     * Écran "quels motifs ce médecin pratique" — n'affecte le comportement
     * réel que si la clinique choisit explicitement d'en configurer au
     * moins un pour ce département (voir Employee::peutPratiquerMotif()) ;
     * sinon, tous les motifs du département restent ouverts par défaut.
     */
    public function gerer(Employee $employee)
    {
        $motifs = MotifRdv::where('department_id', $employee->department_id)->where('actif', true)->get();
        $associations = $employee->motifsAssocies()->get()->keyBy('id');

        return view('employees.motifs', compact('employee', 'motifs', 'associations'));
    }

    public function synchroniser(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'motifs' => ['array'],
            'motifs.*.actif' => ['sometimes', 'boolean'],
            'motifs.*.duree_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ]);

        $sync = [];
        foreach ($data['motifs'] ?? [] as $motifId => $valeurs) {
            $sync[$motifId] = [
                'actif' => (bool) ($valeurs['actif'] ?? false),
                'duree_minutes' => $valeurs['duree_minutes'] ?: null,
            ];
        }

        $employee->motifsAssocies()->sync($sync);

        return back()->with('success', 'Association des motifs mise à jour.');
    }
}
