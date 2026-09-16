<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\MotifRdv;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Remplace la logique historique de `appointment_slots` (grille pré-générée
 * à pas fixe). Ici, on calcule à la volée les intervalles réellement libres
 * du médecin, puis on ne retient que ceux assez longs pour le motif demandé
 * (durée + marge tampon). Aucune table à régénérer, aucun risque de
 * désynchronisation quand une durée de motif change.
 */
class DisponibiliteService
{
    protected const JOURS_FR = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
    ];

    /**
     * @return Collection<int, array{debut: Carbon, fin: Carbon}>
     */
    /**
     * $pasMinutes : null par défaut → utilise la durée du motif elle-même,
     * pour produire des créneaux qui s'enchaînent proprement (08:00-08:15,
     * 08:15-08:30...) plutôt que des propositions qui se chevauchent. Ne
     * passer une valeur explicite que si tu veux vraiment un pas plus fin
     * que la durée du rendez-vous (rare, à réserver à un usage avancé).
     */
    public function creneauxDisponibles(Employee $medecin, Carbon $date, MotifRdv $motif, ?int $pasMinutes = null, ?int $exclureRdvId = null): Collection
    {
        if (! $medecin->peutPratiquerMotif($motif)) {
            return collect();
        }

        $duree = $motif->dureePour($medecin);
        $pasMinutes = $pasMinutes ?? $duree;
        $marge = $motif->marge_tampon_minutes;

        $libres = $this->fenetresTravail($medecin, $date);

        if ($libres->isEmpty()) {
            return collect();
        }

        $occupes = $this->intervallesOccupes($medecin, $date, $marge, $exclureRdvId);

        foreach ($occupes as $occupe) {
            $libres = $libres->flatMap(fn ($f) => $this->soustraire($f, $occupe));
        }

        return $libres
            ->flatMap(fn ($fenetre) => $this->decouper($fenetre, $duree, $pasMinutes))
            ->values();
    }

    /**
     * Plages de travail du médecin ce jour-là, à partir de son planning
     * hebdomadaire (employee_availabilities). Une clinique peut définir
     * plusieurs plages le même jour (matin + après-midi) — toutes sont
     * prises en compte indépendamment.
     */
    protected function fenetresTravail(Employee $medecin, Carbon $date): Collection
    {
        $jour = self::JOURS_FR[$date->dayOfWeekIso];

        return $medecin->availabilities()
            ->where('day_of_week', $jour)
            ->where('is_active', true)
            ->get()
            ->map(function ($dispo) use ($date) {
                return [
                    'debut' => $date->copy()->setTimeFromTimeString($dispo->start_time->format('H:i:s')),
                    'fin' => $date->copy()->setTimeFromTimeString($dispo->end_time->format('H:i:s')),
                ];
            })
            // BUG CORRIGÉ : si $date est aujourd'hui, une fenêtre qui a
            // commencé plus tôt dans la journée proposait quand même des
            // horaires déjà passés (ex. 08:00 alors qu'il est 15:00). On
            // ramène le début au maximum entre l'heure de début déclarée
            // et maintenant — et on retire la fenêtre entièrement si elle
            // est déjà terminée.
            ->map(function (array $fenetre) use ($date) {
                if ($date->isToday() && $fenetre['debut']->lt(now())) {
                    $fenetre['debut'] = now()->copy();
                }
                return $fenetre;
            })
            ->filter(fn (array $fenetre) => $fenetre['debut']->lt($fenetre['fin']))
            ->values();
    }

    /**
     * Tout ce qui rend le médecin indisponible sur des sous-intervalles de
     * la journée : pauses récurrentes, congé (même partiel si les dates
     * portent une heure), rendez-vous déjà pris (avec leur vraie durée,
     * gonflés de la marge tampon du motif demandé de part et d'autre), et
     * les blocages manuels ponctuels hérités (`appointment_slots`).
     */
    protected function intervallesOccupes(Employee $medecin, Carbon $date, int $marge, ?int $exclureRdvId = null): array
    {
        $occupes = [];

        $jour = self::JOURS_FR[$date->dayOfWeekIso];

        foreach ($medecin->breaks()->forDay($jour)->active()->get() as $pause) {
            $occupes[] = [
                'debut' => $date->copy()->setTimeFromTimeString($pause->start_time->format('H:i:s')),
                'fin' => $date->copy()->setTimeFromTimeString($pause->end_time->format('H:i:s')),
            ];
        }

        foreach ($medecin->leaves()->get() as $conge) {
            $debutConge = Carbon::parse($conge->start_date);
            $finConge = Carbon::parse($conge->end_date);

            if ($debutConge->lte($date->copy()->endOfDay()) && $finConge->gte($date->copy()->startOfDay())) {
                $occupes[] = [
                    'debut' => $debutConge->max($date->copy()->startOfDay()),
                    'fin' => $finConge->min($date->copy()->endOfDay()),
                ];
            }
        }

        // NOUVEAU : exclut le rdv qu'on est en train de reprogrammer — sans
        // ça, un rendez-vous se bloquerait lui-même son propre nouveau
        // créneau, puisqu'il compterait comme "déjà occupé" à son horaire
        // d'origine.
        foreach ($medecin->appointments()
            ->whereDate('appointment_date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($exclureRdvId, fn ($q) => $q->where('id', '!=', $exclureRdvId))
            ->get() as $rdv) {

            $debut = $date->copy()->setTimeFromTimeString($rdv->appointment_time->format('H:i:s'));
            $fin = $debut->copy()->addMinutes($rdv->duree_minutes ?? 15);

            // Gonflé de la marge tampon du NOUVEAU motif demandé, de part
            // et d'autre — garantit un battement minimum avant/après,
            // sans avoir besoin d'avoir stocké la marge de l'ancien rdv.
            $occupes[] = [
                'debut' => $debut->copy()->subMinutes($marge),
                'fin' => $fin->copy()->addMinutes($marge),
            ];
        }

        foreach ($medecin->slots()
            ->where('date', $date->toDateString())
            ->where('is_available', false)
            ->get() as $blocage) {

            $debut = $date->copy()->setTimeFromTimeString($blocage->time->format('H:i:s'));
            $occupes[] = ['debut' => $debut, 'fin' => $debut->copy()->addMinutes(15)];
        }

        return $occupes;
    }

    /**
     * Soustrait un intervalle occupé d'un intervalle libre : renvoie 0, 1
     * ou 2 intervalles libres résultants selon le chevauchement.
     */
    protected function soustraire(array $libre, array $occupe): array
    {
        if ($occupe['fin']->lte($libre['debut']) || $occupe['debut']->gte($libre['fin'])) {
            return [$libre]; // pas de chevauchement
        }

        $resultats = [];

        if ($occupe['debut']->gt($libre['debut'])) {
            $resultats[] = ['debut' => $libre['debut'], 'fin' => $occupe['debut']];
        }

        if ($occupe['fin']->lt($libre['fin'])) {
            $resultats[] = ['debut' => $occupe['fin'], 'fin' => $libre['fin']];
        }

        return $resultats;
    }

    /**
     * Découpe un intervalle libre en points de départ proposables, au pas
     * choisi — granularité purement ergonomique pour l'affichage, elle ne
     * structure aucune donnée stockée.
     */
    protected function decouper(array $fenetre, int $duree, int $pas): array
    {
        $creneaux = [];
        $curseur = CarbonImmutable::parse($fenetre['debut']);
        $fin = CarbonImmutable::parse($fenetre['fin']);

        while ($curseur->addMinutes($duree)->lte($fin)) {
            $creneaux[] = ['debut' => Carbon::parse($curseur), 'fin' => Carbon::parse($curseur->addMinutes($duree))];
            $curseur = $curseur->addMinutes($pas);
        }

        return $creneaux;
    }
}
