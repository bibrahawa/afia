<?php

namespace App\Services\Parcours;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Hospitalisation;
use App\Models\Labo\LaboDemande;
use App\Models\Parcours\Grossesse;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier du patient en frise : tout ce qui le concerne dans l'établissement,
 * du plus récent au plus ancien, sans avoir à ouvrir cinq écrans.
 */
class DossierPatientService
{
    public const TYPES = [
        'consultation' => 'Consultations',
        'hospitalisation' => 'Hospitalisations',
        'laboratoire' => 'Laboratoire',
        'rendez_vous' => 'Rendez-vous',
        'grossesse' => 'Grossesses',
    ];

    /**
     * @param array $filtres types[], depuis, jusqu_a
     * @return Collection<int, array{date: Carbon, type: string, titre: string, details: array, lien: ?string}>
     */
    public const PAR_PAGE = 50;

    public function frise(Patient $patient, array $filtres = []): Collection
    {
        $limite = max(10, (int) ($filtres['limite'] ?? self::PAR_PAGE));
        $types = array_filter((array) ($filtres['types'] ?? array_keys(self::TYPES)), fn ($t) => isset(self::TYPES[$t]));
        $depuis = ! empty($filtres['depuis']) ? Carbon::parse($filtres['depuis'])->startOfDay() : null;
        $jusqua = ! empty($filtres['jusqu_a']) ? Carbon::parse($filtres['jusqu_a'])->endOfDay() : null;

        $evenements = collect();

        if (in_array('consultation', $types, true)) {
            $evenements = $evenements->merge($this->consultations($patient));
        }
        if (in_array('hospitalisation', $types, true)) {
            $evenements = $evenements->merge($this->hospitalisations($patient));
        }
        if (in_array('laboratoire', $types, true) && Schema::hasTable('labo_demandes')) {
            $evenements = $evenements->merge($this->laboratoire($patient));
        }
        if (in_array('rendez_vous', $types, true)) {
            $evenements = $evenements->merge($this->rendezVous($patient));
        }
        if (in_array('grossesse', $types, true)) {
            $evenements = $evenements->merge($this->grossesses($patient));
        }

        $filtres = $evenements
            ->filter(fn ($e) => (! $depuis || $e['date']->gte($depuis)) && (! $jusqua || $e['date']->lte($jusqua)))
            ->sortByDesc(fn ($e) => $e['date']->timestamp)
            ->values();

        // Un patient suivi depuis des années peut avoir des centaines d'événements :
        // on renvoie une page, avec le total pour proposer « voir plus ».
        return collect(['total' => $filtres->count(), 'evenements' => $filtres->take($limite)]);
    }

    private function consultations(Patient $patient): Collection
    {
        return Consultation::where('patient_id', $patient->id)
            ->with(['medecin', 'visite', 'tests', 'medicaments', 'transaction'])
            ->get()
            ->map(fn (Consultation $c) => [
                'date' => $c->created_at,
                'type' => 'consultation',
                'titre' => $c->diagnostic ?: ($c->motif ?: 'Consultation'),
                'details' => array_filter([
                    'Motif' => $c->motif,
                    'Médecin' => $c->medecin ? 'Dr ' . $c->medecin->full_name : null,
                    'Examens' => $c->tests->pluck('name')->join(', ') ?: null,
                    'Ordonnance' => $c->medicaments->pluck('nom')->join(', ') ?: null,
                    'Statut' => $c->statut === Consultation::EN_COURS ? 'En cours' : null,
                ]),
                'lien' => route('consultation.show', $c),
            ]);
    }

    private function hospitalisations(Patient $patient): Collection
    {
        return Hospitalisation::where('patient_id', $patient->id)->with('chambre')->get()
            ->map(fn (Hospitalisation $h) => [
                'date' => Carbon::parse($h->date_entree),
                'type' => 'hospitalisation',
                'titre' => 'Hospitalisation' . ($h->chambre ? ' — chambre ' . $h->chambre->numero : ''),
                'details' => array_filter([
                    'Sortie' => $h->date_sortie_effective ? Carbon::parse($h->date_sortie_effective)->format('d/m/Y') : 'en cours',
                    'Durée' => $h->nombre_jours ? $h->nombre_jours . ' jour(s)' : null,
                    'Observation' => $h->observation,
                ]),
                'lien' => null,
            ]);
    }

    private function laboratoire(Patient $patient): Collection
    {
        return LaboDemande::where('patient_id', $patient->id)->with('examens')->get()
            ->map(fn (LaboDemande $d) => [
                'date' => $d->created_at,
                'type' => 'laboratoire',
                'titre' => 'Analyses — ' . $d->numero,
                'details' => array_filter([
                    'Examens' => $d->examens->pluck('examen_nom')->join(', ') ?: null,
                    'Statut' => $d->statut?->libelle() ?? null,
                ]),
                'lien' => null,
            ]);
    }

    private function rendezVous(Patient $patient): Collection
    {
        return Appointment::where('patient_id', $patient->id)->with(['employee', 'motifRdv'])->get()
            ->map(fn (Appointment $a) => [
                'date' => $a->appointment_datetime ?? $a->created_at,
                'type' => 'rendez_vous',
                'titre' => 'Rendez-vous — ' . ($a->motifRdv?->nom ?? 'consultation'),
                'details' => array_filter([
                    'Médecin' => $a->employee ? 'Dr ' . $a->employee->full_name : null,
                    'Statut' => match ($a->status) {
                        'pending' => 'À confirmer', 'confirmed' => 'Confirmé', 'completed' => 'Honoré',
                        'cancelled' => 'Annulé', 'no_show' => 'Absent', default => $a->status,
                    },
                ]),
                'lien' => null,
            ]);
    }

    private function grossesses(Patient $patient): Collection
    {
        return Grossesse::where('patient_id', $patient->id)->get()
            ->map(fn (Grossesse $g) => [
                'date' => $g->created_at,
                'type' => 'grossesse',
                'titre' => 'Suivi de grossesse — DPA ' . $g->dpa->format('d/m/Y'),
                'details' => array_filter([
                    'DDR' => $g->ddr->format('d/m/Y'),
                    'Terme' => $g->estEnCours() ? $g->termeLisible() : null,
                    'Issue' => $g->issue ? (Grossesse::ISSUES[$g->issue] ?? $g->issue) . ' le ' . $g->date_issue?->format('d/m/Y') : null,
                ]),
                'lien' => route('parcours.grossesses.show', $g),
            ]);
    }
}
