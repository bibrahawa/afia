<?php

namespace App\Http\Controllers\Parcours;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Parcours\CroissanceService;

class CroissanceController extends Controller
{
    public function show(int $patientId, CroissanceService $croissance)
    {
        $patient = Patient::suivisParEtablissement()->findOrFail($patientId);

        return view('parcours.croissance.show', [
            'patient' => $patient,
            'mesures' => $croissance->mesures($patient),
            'normesDisponibles' => $croissance->normesDisponibles(),
            'croissance' => $croissance,
            'naissance' => $croissance->naissance($patient),
        ]);
    }
}
