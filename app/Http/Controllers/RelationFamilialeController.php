<?php

namespace App\Http\Controllers;

use App\Enums\TypeRelationFamiliale;
use App\Models\Patient;
use App\Models\RelationFamiliale;
use Illuminate\Http\Request;

class RelationFamilialeController extends Controller
{
    /**
     * Ajoute un lien familial. Rappel de conception : ceci ne donne AUCUN
     * accès aux données de santé — c'est un fait déclaratif ("qui est lié
     * à qui"), pas une autorisation. L'accès passe uniquement par
     * AccesDossierSanteService / la table `consentements`, sauf le cas de
     * tutelle légale d'un mineur (voir TypeRelationFamiliale::impliqueTutelleSiMineur()).
     */
    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'identifiant_national_sante' => ['required', 'exists:patients,identifiant_national_sante'],
            'type_relation' => ['required', 'string', 'in:' . implode(',', array_column(TypeRelationFamiliale::cases(), 'value'))],
        ]);

        $personneLiee = Patient::where('identifiant_national_sante', $data['identifiant_national_sante'])->firstOrFail();

        if ($personneLiee->id === $patient->id) {
            return back()->with('error', 'Un patient ne peut pas être lié à lui-même.');
        }

        RelationFamiliale::firstOrCreate([
            'patient_id' => $patient->id,
            'personne_liee_id' => $personneLiee->id,
            'type_relation' => $data['type_relation'],
        ]);

        return back()->with('success', "Lien familial ajouté avec {$personneLiee->getFullName()}.");
    }

    public function delete(RelationFamiliale $relationFamiliale)
    {
        $relationFamiliale->delete();

        return back()->with('success', 'Lien familial supprimé.');
    }
}
