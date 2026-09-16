<?php

namespace App\Http\Controllers;

use App\Models\ComptePatient;
use App\Models\Patient;
use Illuminate\Http\Request;

class ComptePatientController extends Controller
{
    public function getIndex()
    {
        $comptes = ComptePatient::with('patients')->latest()->get();

        return view('comptes_patients.index', compact('comptes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'string', 'max:20', 'unique:comptes_patients,telephone'],
            'email' => ['nullable', 'email', 'unique:comptes_patients,email'],
        ]);

        $data['statut'] = 'actif';

        ComptePatient::create($data);

        return back()->with('success', 'Compte patient créé. Rattache-lui un dossier patient ci-dessous.');
    }

    public function update(Request $request, ComptePatient $comptePatient)
    {
        $data = $request->validate([
            'telephone' => ['required', 'string', 'max:20', 'unique:comptes_patients,telephone,' . $comptePatient->id],
            'email' => ['nullable', 'email', 'unique:comptes_patients,email,' . $comptePatient->id],
            'statut' => ['required', 'in:actif,suspendu'],
        ]);

        $comptePatient->update($data);

        return back()->with('success', 'Compte mis à jour.');
    }

    public function delete(ComptePatient $comptePatient)
    {
        // Ne supprime jamais les dossiers `Patient` liés (le dossier
        // clinique survit toujours à la suppression d'un compte de
        // connexion) — seule la liaison pivot disparaît, via cascadeOnDelete
        // sur compte_patient.
        $comptePatient->delete();

        return back()->with('success', 'Compte supprimé (les dossiers patients associés sont conservés).');
    }

    /**
     * Rattache un dossier patient existant à ce compte. Recherche par
     * identifiant national santé plutôt que par id interne — c'est
     * l'identifiant que le staff a réellement sous les yeux (carte/QR).
     */
    /**
     * CORRIGÉ — le rôle 'tuteur' donne un accès complet et immédiat au
     * dossier (voir PortailPatientController::autoriserAcces(), qui ne
     * distingue pas titulaire/tuteur). Sans garde-fou, le personnel
     * pouvait attacher n'importe quel patient ADULTE comme "tuteur" d'un
     * compte, ce qui revient à contourner tout le mécanisme de
     * consentement construit par ailleurs — la tutelle n'a de sens que
     * pour un mineur. Un adulte qui veut déléguer la gestion de son
     * dossier à un proche doit passer par le circuit de consentement
     * normal (demande + confirmation SMS), pas par ce raccourci.
     */
    public function attacherPatient(Request $request, ComptePatient $comptePatient)
    {
        $data = $request->validate([
            'identifiant_national_sante' => ['required', 'exists:patients,identifiant_national_sante'],
            'role' => ['required', 'in:titulaire,tuteur'],
        ]);

        $patient = Patient::where('identifiant_national_sante', $data['identifiant_national_sante'])->firstOrFail();

        if ($data['role'] === 'tuteur' && $patient->estMineur() === false) {
            return back()->with('error',
                "{$patient->getFullName()} est enregistré comme majeur — la tutelle ne s'applique qu'aux mineurs. " .
                "Pour qu'un proche adulte gère ce dossier, utilisez la demande de consentement plutôt que ce rattachement."
            );
        }

        $comptePatient->patients()->syncWithoutDetaching([
            $patient->id => ['role' => $data['role']],
        ]);

        return back()->with('success', "Dossier de {$patient->getFullName()} rattaché à ce compte.");
    }

    public function detacherPatient(ComptePatient $comptePatient, Patient $patient)
    {
        $comptePatient->patients()->detach($patient->id);

        return back()->with('success', 'Dossier détaché du compte.');
    }
}
