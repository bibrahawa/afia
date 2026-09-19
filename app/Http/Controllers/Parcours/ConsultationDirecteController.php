<?php

namespace App\Http\Controllers\Parcours;

use App\Http\Controllers\Assurance\Concerns\ResoutPatient;
use App\Http\Controllers\Concerns\InteractsWithAuthenticatedEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\MotifRdv;
use App\Models\Service;
use App\Services\Parcours\AccueilService;
use Illuminate\Http\Request;

/**
 * Consultation directe : le médecin reçoit un patient sans passer par l'accueil.
 *
 * Aussi rapide que l'ancien formulaire « Nouvelle consultation » (un patient,
 * un motif, et l'écran de consultation s'ouvre), mais par le même chemin que
 * l'accueil : une visite est créée et prise en charge dans la foulée. Le
 * patient apparaît donc dans la file, les statistiques d'attente, la caisse
 * (acte facturé) et son dossier, et le médecin retrouve ses modèles et ses
 * suggestions sur l'écran de consultation rapide.
 */
class ConsultationDirecteController extends Controller
{
    use InteractsWithAuthenticatedEmployee, ResoutPatient;

    public function __construct(private AccueilService $accueil)
    {
    }

    public function create()
    {
        return view('parcours.consultation.nouvelle', [
            'medecin' => Employee::findOrFail($this->authenticatedEmployeeId()),
            'motifs' => MotifRdv::where('actif', true)->orderBy('ordre_affichage')->get(),
            'services' => Service::actifs()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'patient_id' => ['required', 'integer'],
            'motif_rdv_id' => ['nullable', 'exists_etablissement:motifs_rdv,id'],
            'motif' => ['nullable', 'string', 'max:255'],
            'service_id' => ['nullable', 'exists_etablissement:services,id'],
            'urgence' => ['nullable', 'boolean'],
        ], ['patient_id.required' => 'Choisissez le patient.']);

        $medecin = Employee::findOrFail($this->authenticatedEmployeeId());

        // Même règle que l'accueil : médecin actif, pas de doublon dans la journée,
        // acte facturé à l'arrivée si un acte est choisi.
        $visite = $this->accueil->arriveeSansRendezVous($this->patientAutorise($request), $medecin, $donnees, $request->user());
        $this->accueil->appeler($visite, $medecin);

        return redirect()->route('parcours.consultation.show', $visite->consultation);
    }
}
