<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MotifRdv;
use Illuminate\Http\Request;

class MedecinMotifController extends Controller
{
    /**
     * Écran « motifs pratiqués par ce médecin ».
     *
     * Règle (voir Employee::peutPratiquerMotif) : un médecin pratique tous
     * les motifs de son département, sauf ceux explicitement décochés ici.
     * La durée personnalisée est indépendante et ne restreint rien.
     */
    public function gerer(Employee $employee)
    {
        $motifs = MotifRdv::where('department_id', $employee->department_id)->where('actif', true)->orderBy('ordre_affichage')->get();
        $associations = $employee->motifsAssocies()->get()->keyBy('id');

        return view('employees.motifs', compact('employee', 'motifs', 'associations'));
    }

    /**
     * CORRIGÉ 21/09/2026 :
     *  - une case décochée n'était pas envoyée par le navigateur, mais le
     *    champ durée l'était toujours : enregistrer l'écran une seule fois
     *    sans rien cocher créait une ligne actif = false pour CHAQUE motif et
     *    retirait au médecin tous ses rendez-vous en ligne ;
     *  - les identifiants de motif n'étaient pas vérifiés : on pouvait
     *    associer un motif d'un autre département ou d'un autre établissement.
     *
     * On ne stocke désormais une ligne que lorsqu'elle porte une information
     * (exclusion ou durée personnalisée) ; sinon la ligne est supprimée et la
     * règle par défaut s'applique.
     */
    public function synchroniser(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'motifs' => ['array'],
            'motifs.*.pratique' => ['required', 'boolean'],
            'motifs.*.duree_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ]);

        // Seuls les motifs du département du médecin (et donc de son
        // établissement, via le global scope de MotifRdv) sont acceptés.
        $motifsAutorises = MotifRdv::where('department_id', $employee->department_id)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $sync = [];
        foreach ($data['motifs'] ?? [] as $motifId => $valeurs) {
            if (! in_array((int) $motifId, $motifsAutorises, true)) {
                continue;
            }

            $pratique = (bool) $valeurs['pratique'];
            $duree = ! empty($valeurs['duree_minutes']) ? (int) $valeurs['duree_minutes'] : null;

            if ($pratique && $duree === null) {
                continue; // comportement par défaut : aucune ligne nécessaire
            }

            $sync[(int) $motifId] = ['actif' => $pratique, 'duree_minutes' => $duree];
        }

        // sync() retire aussi les lignes des motifs revenus au comportement par défaut.
        $employee->motifsAssocies()->sync($sync);

        \App\Support\CacheDisponibilite::invalider($employee->id);

        return back()->with('success', 'Motifs du médecin mis à jour.');
    }
}
