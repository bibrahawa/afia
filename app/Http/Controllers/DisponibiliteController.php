<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\MotifRdv;
use App\Services\DisponibiliteService;
use App\Support\CacheDisponibilite;
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

        // Cache court (30 s), clé versionnée par médecin : invalidée dès qu'un
        // rdv, congé ou pause de ce médecin change. On met en cache le tableau
        // déjà formaté (pas des objets Carbon). La vérification qui fait foi
        // reste celle, non cachée, d'AppointmentBookingService.
        $date = Carbon::parse($data['date'])->toDateString();
        $cle = CacheDisponibilite::prefixe($medecin->id) . ":creneaux:{$motif->id}:{$date}";

        $creneaux = Cache::remember($cle, 30, fn () => $this->disponibilite
            ->creneauxDisponibles($medecin, Carbon::parse($date), $motif)
            ->map(fn ($c) => ['debut' => $c['debut']->format('H:i'), 'fin' => $c['fin']->format('H:i')])
            ->values()
            ->all());

        return response()->json($creneaux);
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
        $date = Carbon::parse($data['date'])->startOfDay();

        $medecins = Employee::where('department_id', $motif->department_id)
            ->where('etablissement_id', $etablissement->id)
            ->where('type', 'Doctor')
            ->where('is_active', true)
            ->get()
            ->filter(fn (Employee $m) => $m->peutPratiquerMotif($motif));

        // La clé combine les versions de cache de TOUS les médecins concernés :
        // un changement chez l'un d'eux invalide la liste fusionnée.
        $versions = $medecins->map(fn (Employee $m) => CacheDisponibilite::prefixe($m->id))->implode('|');
        $cle = 'dispo:creneaux-tous:' . $motif->id . ':' . $date->toDateString() . ':' . md5($versions);

        $resultats = Cache::remember($cle, 30, function () use ($motif, $medecins, $date) {
            return $medecins
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
                ->values()
                ->all();
        });

        return response()->json($resultats);
    }
}
