<?php

namespace App\Services\Parcours;

use App\Models\Consultation;
use App\Models\Employee;
use App\Models\Medicament;
use App\Models\Patient;
use App\Models\Test;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ce que le médecin écrit le plus souvent, pour le lui proposer en un clic.
 *
 * Tout est déduit de son propre historique : aucune saisie de paramétrage,
 * les propositions s'améliorent d'elles-mêmes au fil des consultations.
 */
class SuggestionsConsultationService
{
    public const LIMITE = 8;

    /** Les habitudes récentes seulement : plus pertinent, et la requête reste rapide. */
    public const MOIS_HISTORIQUE = 12;

    /** Diagnostics les plus posés par ce médecin, en priorité pour ce motif. */
    public function diagnostics(Employee $medecin, ?int $motifRdvId = null, int $limite = self::LIMITE): Collection
    {
        $requete = fn (?int $motif) => Consultation::query()
            ->where('medecin_id', $medecin->id)
            ->where('created_at', '>=', now()->subMonths(self::MOIS_HISTORIQUE))
            ->whereNotNull('diagnostic')
            ->where('diagnostic', '!=', '')
            ->where('diagnostic', '!=', 'N/A')
            ->when($motif, fn ($q) => $q->whereHas('visite', fn ($v) => $v->where('motif_rdv_id', $motif)))
            ->select('diagnostic', DB::raw('COUNT(*) as total'))
            ->groupBy('diagnostic')
            ->orderByDesc('total')
            ->limit($limite)
            ->pluck('diagnostic');

        $pourMotif = $motifRdvId ? $requete($motifRdvId) : collect();

        return $pourMotif->merge($requete(null))->unique()->take($limite)->values();
    }

    /** Médicaments les plus prescrits, avec la posologie la plus récente. */
    public function medicaments(Employee $medecin, int $limite = self::LIMITE): Collection
    {
        $lignes = DB::table('consultation_medicament as cm')
            ->join('consultations as c', 'c.id', '=', 'cm.consultation_id')
            ->where('c.medecin_id', $medecin->id)
            ->where('c.created_at', '>=', now()->subMonths(self::MOIS_HISTORIQUE))
            ->select('cm.medicament_id', DB::raw('COUNT(*) as total'), DB::raw('MAX(cm.id) as derniere'))
            ->groupBy('cm.medicament_id')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        if ($lignes->isEmpty()) {
            return collect();
        }

        $posologies = DB::table('consultation_medicament')->whereIn('id', $lignes->pluck('derniere'))->get()->keyBy('id');
        $medicaments = Medicament::actifs()->whereIn('id', $lignes->pluck('medicament_id'))->get()->keyBy('id');

        return $lignes->map(function ($ligne) use ($posologies, $medicaments) {
            $medicament = $medicaments->get($ligne->medicament_id);
            $derniere = $posologies->get($ligne->derniere);

            return $medicament ? [
                'id' => $medicament->id,
                'nom' => $medicament->nom,
                'prix' => (float) $medicament->amount,
                'quantite' => (int) ($derniere->quantity ?? 1),
                'dose' => $derniere->dose ?? $medicament->dosage,
                'frequence' => $derniere->frequence ?? $medicament->frequence,
                'duree' => $derniere->duree ?? $medicament->duree,
                'instructions' => $derniere->instructions ?? $medicament->instructions,
            ] : null;
        })->filter()->values();
    }

    /** Examens les plus prescrits par ce médecin. */
    public function examens(Employee $medecin, int $limite = self::LIMITE): Collection
    {
        $ids = DB::table('consultation_test as ct')
            ->join('consultations as c', 'c.id', '=', 'ct.consultation_id')
            ->where('c.medecin_id', $medecin->id)
            ->where('c.created_at', '>=', now()->subMonths(self::MOIS_HISTORIQUE))
            ->select('ct.test_id', DB::raw('COUNT(*) as total'))
            ->groupBy('ct.test_id')
            ->orderByDesc('total')
            ->limit($limite)
            ->pluck('ct.test_id');

        return Test::whereIn('id', $ids)->get()
            ->sortBy(fn ($t) => $ids->search($t->id))
            ->map(fn ($t) => ['id' => $t->id, 'nom' => $t->name, 'prix' => (float) $t->amount])
            ->values();
    }

    /** Dernière ordonnance du patient : renouvellement en un clic pour un traitement au long cours. */
    public function derniereOrdonnance(Patient $patient, ?int $exclureConsultationId = null): array
    {
        $consultation = Consultation::where('patient_id', $patient->id)
            ->when($exclureConsultationId, fn ($q) => $q->where('id', '!=', $exclureConsultationId))
            ->whereHas('medicaments')
            ->with('medicaments')
            ->latest('id')
            ->first();

        if (! $consultation) {
            return ['date' => null, 'lignes' => []];
        }

        return [
            'date' => $consultation->created_at?->format('d/m/Y'),
            'lignes' => $consultation->medicaments->map(fn ($m) => [
                'id' => $m->id,
                'nom' => $m->nom,
                'prix' => (float) $m->amount,
                'quantite' => (int) ($m->pivot->quantity ?: 1),
                'dose' => $m->pivot->dose ?: $m->dosage,
                'frequence' => $m->pivot->frequence ?: $m->frequence,
                'duree' => $m->pivot->duree ?: $m->duree,
                'instructions' => $m->pivot->instructions ?: $m->instructions,
            ])->values()->all(),
        ];
    }
}
