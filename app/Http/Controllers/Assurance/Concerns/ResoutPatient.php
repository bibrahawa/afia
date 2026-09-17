<?php

namespace App\Http\Controllers\Assurance\Concerns;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Même règle de confidentialité que la recherche patient : un patient déjà
 * suivi par l'établissement peut être choisi librement ; un patient d'une
 * autre clinique seulement si l'on a saisi son numéro de téléphone complet ou
 * son identifiant national de santé exact (champ « preuve_identite », rempli
 * par le sélecteur de patient). Empêche de rattacher n'importe quel patient
 * de la plateforme en modifiant un identifiant dans le formulaire.
 */
trait ResoutPatient
{
    protected function patientAutorise(Request $request, string $champ = 'patient_id'): Patient
    {
        $patient = Patient::with('comptesPatients')->find($request->integer($champ));

        if (! $patient) {
            throw ValidationException::withMessages([$champ => 'Patient introuvable.']);
        }

        if (Patient::suivisParEtablissement()->whereKey($patient->id)->exists()) {
            return $patient;
        }

        $preuve = trim((string) $request->input('preuve_identite'));

        $prouve = $preuve !== '' && (
            $preuve === (string) $patient->identifiant_national_sante
            || $patient->comptesPatients->contains(fn ($c) => $c->telephone === $preuve)
        );

        if (! $prouve) {
            throw ValidationException::withMessages([$champ => 'Ce patient n\'est pas suivi par l\'établissement : recherchez-le par son numéro de téléphone complet ou son identifiant national de santé.']);
        }

        return $patient;
    }
}
