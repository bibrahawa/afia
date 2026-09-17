<?php

namespace App\Http\Controllers\Parcours;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Parcours\DossierPatientService;
use App\Services\Parcours\GrossesseService;
use Illuminate\Http\Request;

/** Dossier du patient en frise : consultations, hospitalisations, analyses, rendez-vous, grossesses. */
class DossierController extends Controller
{
    public function show(Request $request, int $patientId, DossierPatientService $dossier, GrossesseService $grossesses)
    {
        $patient = Patient::suivisParEtablissement()->with('antecedant')->findOrFail($patientId);

        $filtres = $request->validate([
            'types' => ['nullable', 'array'],
            'types.*' => ['string'],
            'depuis' => ['nullable', 'date'],
            'jusqu_a' => ['nullable', 'date', 'after_or_equal:depuis'],
        ]);

        return view('parcours.dossier.show', [
            'patient' => $patient,
            'evenements' => $dossier->frise($patient, $filtres),
            'typesChoisis' => $filtres['types'] ?? array_keys(DossierPatientService::TYPES),
            'filtres' => $filtres,
            'grossesse' => $grossesses->enCours($patient),
        ]);
    }
}
