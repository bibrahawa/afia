<?php

namespace App\Services\Parcours;

use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Models\Consultation;
use App\Models\Parcours\Grossesse;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ouverture et suivi d'une grossesse. La DDR saisie à l'accueil suffit :
 * terme, date prévue d'accouchement et calendrier des CPN en découlent.
 */
class GrossesseService
{
    public function ouvrir(Patient $patient, Carbon $ddr, array $donnees = [], ?User $auteur = null): Grossesse
    {
        if ($ddr->isFuture()) {
            throw new OperationParcoursImpossible('La date des dernières règles ne peut pas être dans le futur.');
        }

        if ($ddr->diffInDays(today()) > 44 * 7) {
            throw new OperationParcoursImpossible('Cette date des dernières règles remonte à plus de 44 semaines : vérifiez la saisie.');
        }

        if ($this->enCours($patient)) {
            throw new OperationParcoursImpossible("Une grossesse est déjà suivie pour {$patient->full_name}. Clôturez-la d'abord.");
        }

        return Grossesse::create([
            'patient_id' => $patient->id,
            'medecin_id' => $donnees['medecin_id'] ?? null,
            'ddr' => $ddr->toDateString(),
            'dpa' => Grossesse::dpaDepuis($ddr)->toDateString(),
            'gestite' => $donnees['gestite'] ?? null,
            'parite' => $donnees['parite'] ?? null,
            'notes' => $donnees['notes'] ?? null,
            'statut' => Grossesse::EN_COURS,
            'ouverte_par' => $auteur?->id,
        ]);
    }

    public function enCours(Patient $patient): ?Grossesse
    {
        return Grossesse::where('patient_id', $patient->id)->where('statut', Grossesse::EN_COURS)->latest('id')->first();
    }

    /** Correction de la DDR (échographie de datation) : la DPA et le calendrier suivent. */
    public function corrigerDdr(Grossesse $grossesse, Carbon $ddr): Grossesse
    {
        if (! $grossesse->estEnCours()) {
            throw new OperationParcoursImpossible('Cette grossesse est clôturée.');
        }

        $grossesse->update(['ddr' => $ddr->toDateString(), 'dpa' => Grossesse::dpaDepuis($ddr)->toDateString()]);

        return $grossesse->fresh();
    }

    public function cloturer(Grossesse $grossesse, string $issue, Carbon $date, ?string $notes = null): Grossesse
    {
        if (! isset(Grossesse::ISSUES[$issue])) {
            throw new OperationParcoursImpossible('Issue de grossesse inconnue.');
        }

        if ($date->lt($grossesse->ddr)) {
            throw new OperationParcoursImpossible('La date de l\'issue est antérieure aux dernières règles.');
        }

        $grossesse->update([
            'statut' => $issue === 'accouchement' ? Grossesse::TERMINEE : Grossesse::INTERROMPUE,
            'issue' => $issue,
            'date_issue' => $date->toDateString(),
            'notes' => trim(($grossesse->notes ? $grossesse->notes . "\n" : '') . (string) $notes) ?: null,
        ]);

        return $grossesse->fresh();
    }

    /** Rattache la consultation au suivi en cours (appelé à l'ouverture d'une consultation). */
    public function rattacher(Consultation $consultation): ?Grossesse
    {
        if ($consultation->grossesse_id) {
            return $consultation->grossesse;
        }

        $grossesse = $consultation->patient ? $this->enCours($consultation->patient) : null;

        if ($grossesse) {
            $consultation->update(['grossesse_id' => $grossesse->id]);
        }

        return $grossesse;
    }

    /** Poids et tension relevés à chaque visite, pour la courbe de suivi. */
    public function mesures(Grossesse $grossesse): array
    {
        return DB::table('constantes')
            ->where('patient_id', $grossesse->patient_id)
            ->whereDate('mesure_le', '>=', $grossesse->ddr)
            ->when($grossesse->date_issue, fn ($q) => $q->whereDate('mesure_le', '<=', $grossesse->date_issue))
            ->orderBy('mesure_le')
            ->get(['mesure_le', 'poids_kg', 'tension_systolique', 'tension_diastolique'])
            ->map(function ($ligne) use ($grossesse) {
                $date = Carbon::parse($ligne->mesure_le);

                return [
                    'date' => $date,
                    'terme' => $grossesse->termeLisible($date),
                    'poids' => $ligne->poids_kg !== null ? (float) $ligne->poids_kg : null,
                    'tension' => $ligne->tension_systolique ? $ligne->tension_systolique . '/' . $ligne->tension_diastolique : null,
                ];
            })->all();
    }
}
