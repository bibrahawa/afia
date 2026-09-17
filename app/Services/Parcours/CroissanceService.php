<?php

namespace App\Services\Parcours;

use App\Models\Parcours\Constante;
use App\Models\Parcours\NormeCroissance;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Croissance de l'enfant : mesures relevées à chaque visite, et z-scores OMS
 * quand les tables de référence ont été importées (voir la commande
 * aprosafe:importer-normes-oms). Sans ces tables, les mesures restent
 * affichées — on ne calcule jamais un percentile sur des valeurs inventées.
 */
class CroissanceService
{
    public const AGE_MAX_MOIS = 60;

    public function normesDisponibles(): bool
    {
        return NormeCroissance::query()->exists();
    }

    /**
     * @return Collection<int, array{date: Carbon, mois: ?int, poids: ?float, taille: ?float, imc: ?float, z: array}>
     */
    public function mesures(Patient $patient): Collection
    {
        $naissance = $this->naissance($patient);

        return Constante::where('patient_id', $patient->id)
            ->where(fn ($q) => $q->whereNotNull('poids_kg')->orWhereNotNull('taille_cm'))
            ->orderBy('mesure_le')
            ->get()
            ->map(function (Constante $c) use ($patient, $naissance) {
                $mois = $naissance ? (int) $naissance->diffInMonths($c->mesure_le) : null;
                $poids = $c->poids_kg !== null ? (float) $c->poids_kg : null;
                $taille = $c->taille_cm !== null ? (float) $c->taille_cm : null;
                $imc = $c->imc();

                return [
                    'date' => $c->mesure_le,
                    'mois' => $mois,
                    'poids' => $poids,
                    'taille' => $taille,
                    'imc' => $imc,
                    'z' => [
                        'poids' => $this->zScore(NormeCroissance::POIDS_AGE, $patient, $mois, $poids),
                        'taille' => $this->zScore(NormeCroissance::TAILLE_AGE, $patient, $mois, $taille),
                        'imc' => $this->zScore(NormeCroissance::IMC_AGE, $patient, $mois, $imc),
                    ],
                ];
            });
    }

    /** Z-score LMS de l'OMS : z = ((valeur / M)^L − 1) / (L × S). */
    public function zScore(string $indicateur, Patient $patient, ?int $mois, ?float $valeur): ?float
    {
        if ($mois === null || $valeur === null || $valeur <= 0 || $mois > self::AGE_MAX_MOIS) {
            return null;
        }

        $norme = NormeCroissance::where('indicateur', $indicateur)
            ->where('sexe', $patient->gender)
            ->where('mois', $mois)
            ->first();

        if (! $norme || $norme->m <= 0 || $norme->s == 0.0) {
            return null;
        }

        $z = $norme->l == 0.0
            ? log($valeur / $norme->m) / $norme->s
            : ((($valeur / $norme->m) ** $norme->l) - 1) / ($norme->l * $norme->s);

        return round($z, 2);
    }

    /** Lecture clinique d'un z-score, pour éviter l'interprétation à l'œil. */
    public function interpretation(?float $z): ?array
    {
        if ($z === null) {
            return null;
        }

        return match (true) {
            $z < -3 => ['libelle' => 'Très en dessous de la norme (< −3 z)', 'niveau' => 'danger'],
            $z < -2 => ['libelle' => 'En dessous de la norme (< −2 z)', 'niveau' => 'warning'],
            $z > 3 => ['libelle' => 'Très au-dessus de la norme (> +3 z)', 'niveau' => 'danger'],
            $z > 2 => ['libelle' => 'Au-dessus de la norme (> +2 z)', 'niveau' => 'warning'],
            default => ['libelle' => 'Dans la norme', 'niveau' => 'success'],
        };
    }

    public function naissance(Patient $patient): ?Carbon
    {
        return $patient->dateNaissance();
    }

    public function estEnfant(Patient $patient): bool
    {
        $naissance = $this->naissance($patient);

        return $naissance !== null && $naissance->diffInMonths(today()) <= self::AGE_MAX_MOIS;
    }
}
