<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\MotifRdv;
use App\Services\DisponibiliteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DisponibiliteController extends Controller
{
    public function __construct(protected DisponibiliteService $disponibilite)
    {
    }

    /**
     * Endpoint JSON consommé par l'écran de prise de rdv : le patient a
     * déjà choisi un motif (donc une durée) et un médecin, on renvoie les
     * créneaux réellement proposables pour la date demandée.
     *
     * `$etablissement` est injecté par la route publique
     * (/rdv/{etablissement}/api/...) — filtré explicitement ici en plus du
     * global scope, même principe de défense en profondeur qu'ailleurs.
     */
    public function creneaux(Etablissement $etablissement, Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists_etablissement:employees,id'],
            'motif_rdv_id' => ['required', 'exists_etablissement:motifs_rdv,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $medecin = Employee::where('etablissement_id', $etablissement->id)->findOrFail($data['employee_id']);
        $motif = MotifRdv::where('etablissement_id', $etablissement->id)->findOrFail($data['motif_rdv_id']);

        // Cache court (30s) : voir le raisonnement détaillé dans
        // AppointmentController::getAvailableDates — la vérification qui
        // fait réellement foi contre une double réservation reste celle,
        // non cachée, d'AppointmentBookingService au moment de réserver.
        $cle = "dispo:creneaux:{$medecin->id}:{$motif->id}:{$data['date']}";
        $creneaux = Cache::remember($cle, 30, fn () => $this->disponibilite->creneauxDisponibles($medecin, Carbon::parse($data['date']), $motif));

        return response()->json(
            $creneaux->map(fn ($c) => [
                'debut' => $c['debut']->format('H:i'),
                'fin' => $c['fin']->format('H:i'),
            ])
        );
    }

    /**
     * "Premier disponible" : fusionne les créneaux de tous les médecins
     * compatibles avec le motif (département + peutPratiquerMotif), triés
     * par heure.
     */
    public function creneauxTousMedecins(Etablissement $etablissement, Request $request)
    {
        $data = $request->validate([
            'motif_rdv_id' => ['required', 'exists_etablissement:motifs_rdv,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $motif = MotifRdv::where('etablissement_id', $etablissement->id)->findOrFail($data['motif_rdv_id']);
        $date = Carbon::parse($data['date']);

        $cle = "dispo:creneaux-tous:{$motif->id}:{$data['date']}";
        $resultats = Cache::remember($cle, 30, function () use ($motif, $etablissement, $date) {
            return Employee::where('department_id', $motif->department_id)
                ->where('etablissement_id', $etablissement->id)
                ->where('type', 'Doctor')
                ->where('is_active', true)
                ->get()
                ->filter(fn (Employee $m) => $m->peutPratiquerMotif($motif))
                ->flatMap(function (Employee $medecin) use ($motif, $date) {
                    return $this->disponibilite->creneauxDisponibles($medecin, $date, $motif)
                        ->map(fn ($c) => [
                            'employee_id' => $medecin->id,
                            'medecin' => $medecin->full_name,
                            'debut' => $c['debut']->format('H:i'),
                            'fin' => $c['fin']->format('H:i'),
                        ]);
                })
                ->sortBy('debut')
                ->values();
        });

        return response()->json($resultats);
    }
}
