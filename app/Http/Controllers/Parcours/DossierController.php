<?php

namespace App\Http\Controllers\Parcours;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
            'limite' => ['nullable', 'integer', 'min:10', 'max:500'],
        ]);

        // Dossier médical : qui l'a ouvert, quand, reste tracé.
        ActivityLog::create([
            'causer_type' => \App\Models\User::class,
            'causer_id' => $request->user()?->id,
            'subject_type' => Patient::class,
            'subject_id' => $patient->id,
            'action' => 'dossier.consultation',
            'description' => 'Consultation du dossier patient',
            'ip_address' => $request->ip(),
        ]);

        $frise = $dossier->frise($patient, $filtres);

        return view('parcours.dossier.show', [
            'patient' => $patient,
            'evenements' => $frise['evenements'],
            'total' => $frise['total'],
            'limite' => $filtres['limite'] ?? \App\Services\Parcours\DossierPatientService::PAR_PAGE,
            'typesChoisis' => $filtres['types'] ?? array_keys(DossierPatientService::TYPES),
            'filtres' => $filtres,
            'grossesse' => $grossesses->enCours($patient),
        ]);
    }
}
