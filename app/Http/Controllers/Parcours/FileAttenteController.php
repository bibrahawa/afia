<?php

namespace App\Http\Controllers\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Http\Controllers\Concerns\InteractsWithAuthenticatedEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Parcours\Visite;
use App\Services\Parcours\AccueilService;
use Illuminate\Http\Request;

/** File d'attente du médecin : un clic pour prendre le patient suivant. */
class FileAttenteController extends Controller
{
    use InteractsWithAuthenticatedEmployee;

    public function __construct(private AccueilService $accueil)
    {
    }

    public function index()
    {
        $medecinId = $this->authenticatedEmployeeId();

        $visites = Visite::duJour()
            ->where('medecin_id', $medecinId)
            ->with(['patient', 'derniereConstante', 'consultation.transaction', 'appointment'])
            ->ordreFile()
            ->get()
            ->groupBy(fn (Visite $v) => $v->statut->value);

        return view('parcours.file.index', [
            'enConsultation' => $visites->get(StatutVisite::EnConsultation->value, collect()),
            'enAttente' => $visites->get(StatutVisite::EnAttente->value, collect()),
            'terminees' => $visites->get(StatutVisite::Terminee->value, collect()),
        ]);
    }

    public function appeler(Request $request, Visite $visite)
    {
        $medecin = Employee::findOrFail($this->authenticatedEmployeeId());
        $this->accueil->appeler($visite, $medecin, (bool) $request->user()?->hasRole(['admin', 'super-admin']));

        return redirect()->route('parcours.consultation.show', $visite->consultation);
    }

    public function terminer(Request $request, Visite $visite)
    {
        abort_unless((int) $visite->medecin_id === $this->authenticatedEmployeeId() || $request->user()?->hasRole(['admin', 'super-admin']), 403);

        $this->accueil->terminer($visite);

        return redirect()->route('parcours.file.index')->with('success', 'Consultation terminée.');
    }
}
