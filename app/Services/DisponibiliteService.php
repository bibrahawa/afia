<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\MotifRdv;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Calcule à la volée les intervalles réellement libres d'un médecin, puis
 * ne retient que les points de départ assez longs pour le motif demandé
 * (durée + marge tampon). Aucune grille pré-générée à maintenir.
 *
 * RÉVISION 21/09/2026 :
 *  - Jour même : les créneaux restent sur la grille normale (08:00, 08:15…)
 *    et on retire seulement ceux déjà passés, avec une tolérance de
 *    TOLERANCE_MINUTES. Avant, la fenêtre démarrait à now() non arrondi
 *    (15:07:23) : le créneau affiché n'existait plus une minute plus tard
 *    et la réservation échouait presque toujours.
 *  - Congés : un congé REFUSÉ ne bloque plus l'agenda (un congé en attente
 *    bloque toujours — DoctorLeaveService annule déjà les rdv dès la saisie).
 *  - Performance : toutes les données de la période sont chargées en une
 *    fois (5 requêtes au lieu de ~8 par jour). joursDisponibles() sur 60
 *    jours ne coûte plus ~500 requêtes.
 */
class DisponibiliteService
{
    /** Un créneau commencé depuis moins de X minutes reste réservable (patient au guichet, latence réseau). */
    public const TOLERANCE_MINUTES = 5;

    /** Durée attribuée à un rdv historique sans durée enregistrée, et à un blocage manuel legacy. */
    public const DUREE_PAR_DEFAUT = 15;

    public const STATUTS_OCCUPANTS = ['pending', 'confirmed'];

    protected const JOURS_FR = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
    ];

    /**
     * Créneaux proposables pour UNE date.
     *
     * $pasMinutes : null → pas = durée du motif (créneaux qui s'enchaînent).
     * $exclureRdvId : rdv ignoré dans le calcul (reprogrammation de lui-même).
     *
     * @return Collection<int, array{debut: Carbon, fin: Carbon}>
     */
    public function creneauxDisponibles(Employee $medecin, Carbon $date, MotifRdv $motif, ?int $pasMinutes = null, ?int $exclureRdvId = null): Collection
    {
        return $this->creneauxSurPeriode($medecin, $date, 1, $motif, $pasMinutes, $exclureRdvId)
            ->get($date->toDateString(), collect());
    }

    /**
     * Dates (Y-m-d) ayant au moins un créneau libre sur la période.
     *
     * @return Collection<int, string>
     */
    public function joursDisponibles(Employee $medecin, Carbon $debut, int $nbJours, MotifRdv $motif): Collection
    {
        return $this->creneauxSurPeriode($medecin, $debut, $nbJours, $motif)
            ->filter(fn (Collection $creneaux) => $creneaux->isNotEmpty())
            ->keys()
            ->values();
    }

    /**
     * @return Collection<string, Collection> créneaux indexés par date Y-m-d
     */
    protected function creneauxSurPeriode(Employee $medecin, Carbon $debut, int $nbJours, MotifRdv $motif, ?int $pasMinutes = null, ?int $exclureRdvId = null): Collection
    {
        if ($nbJours < 1 || ! $medecin->peutPratiquerMotif($motif)) {
            return collect();
        }

        $duree = $motif->dureePour($medecin);
        $pas = max(1, $pasMinutes ?? $duree);
        $marge = (int) $motif->marge_tampon_minutes;

        $premierJour = $debut->copy()->startOfDay();
        $dernierJour = $premierJour->copy()->addDays($nbJours - 1);

        $donnees = $this->chargerPeriode($medecin, $premierJour, $dernierJour, $exclureRdvId);
        $limite = now()->subMinutes(self::TOLERANCE_MINUTES);

        $resultat = collect();

        for ($i = 0; $i < $nbJours; $i++) {
            $jour = $premierJour->copy()->addDays($i);
            $cle = $jour->toDateString();

            if ($jour->copy()->endOfDay()->lt($limite)) {
                $resultat->put($cle, collect());
                continue;
            }

            $libres = $this->fenetresTravail($donnees, $jour);

            foreach ($this->intervallesOccupes($donnees, $jour, $marge) as $occupe) {
                $libres = $libres->flatMap(fn ($fenetre) => $this->soustraire($fenetre, $occupe));
            }

            $creneaux = $libres
                ->flatMap(fn ($fenetre) => $this->decouper($fenetre, $duree, $pas))
                ->filter(fn ($creneau) => $creneau['debut']->gte($limite))
                ->sortBy(fn ($creneau) => $creneau['debut']->timestamp)
                ->values();

            $resultat->put($cle, $creneaux);
        }

        return $resultat;
    }

    /**
     * Toutes les données de la période en 5 requêtes, regroupées par jour.
     */
    protected function chargerPeriode(Employee $medecin, Carbon $premierJour, Carbon $dernierJour, ?int $exclureRdvId): array
    {
        return [
            'plannings' => $medecin->availabilities()
                ->where('is_active', true)
                ->get()
                ->groupBy('day_of_week'),

            'pauses' => $medecin->breaks()
                ->active()
                ->get()
                ->groupBy('day_of_week'),

            'conges' => $medecin->leaves()
                ->where('status', '!=', 'rejected')
                ->where('start_date', '<=', $dernierJour->copy()->endOfDay())
                ->where('end_date', '>=', $premierJour->copy()->startOfDay())
                ->get(),

            'rdvs' => Appointment::where('employee_id', $medecin->id)
                ->whereBetween('appointment_date', [$premierJour->toDateString(), $dernierJour->toDateString()])
                ->whereIn('status', self::STATUTS_OCCUPANTS)
                ->when($exclureRdvId, fn ($q) => $q->where('id', '!=', $exclureRdvId))
                ->get()
                ->groupBy(fn (Appointment $rdv) => $rdv->appointment_date->toDateString()),

            'blocages' => $medecin->slots()
                ->whereBetween('date', [$premierJour->toDateString(), $dernierJour->toDateString()])
                ->where('is_available', false)
                ->get()
                ->groupBy(fn ($slot) => Carbon::parse($slot->date)->toDateString()),
        ];
    }

    /**
     * Plages de travail du jour (plusieurs plages possibles : matin + après-midi).
     */
    protected function fenetresTravail(array $donnees, Carbon $jour): Collection
    {
        $nomJour = self::JOURS_FR[$jour->dayOfWeekIso];

        return collect($donnees['plannings']->get($nomJour, []))
            ->map(fn ($dispo) => [
                'debut' => $jour->copy()->setTimeFromTimeString($dispo->start_time->format('H:i:s')),
                'fin' => $jour->copy()->setTimeFromTimeString($dispo->end_time->format('H:i:s')),
            ])
            ->filter(fn (array $fenetre) => $fenetre['debut']->lt($fenetre['fin']))
            ->values();
    }

    /**
     * Pauses récurrentes, congés (même partiels), rendez-vous actifs gonflés
     * de la marge tampon du motif demandé, blocages manuels legacy.
     */
    protected function intervallesOccupes(array $donnees, Carbon $jour, int $marge): array
    {
        $occupes = [];
        $nomJour = self::JOURS_FR[$jour->dayOfWeekIso];
        $debutJour = $jour->copy()->startOfDay();
        $finJour = $jour->copy()->endOfDay();

        foreach ($donnees['pauses']->get($nomJour, []) as $pause) {
            $occupes[] = [
                'debut' => $jour->copy()->setTimeFromTimeString($pause->start_time->format('H:i:s')),
                'fin' => $jour->copy()->setTimeFromTimeString($pause->end_time->format('H:i:s')),
            ];
        }

        foreach ($donnees['conges'] as $conge) {
            $debutConge = Carbon::parse($conge->start_date);
            $finConge = Carbon::parse($conge->end_date);

            if ($debutConge->lte($finJour) && $finConge->gte($debutJour)) {
                $occupes[] = [
                    'debut' => $debutConge->gt($debutJour) ? $debutConge : $debutJour->copy(),
                    'fin' => $finConge->lt($finJour) ? $finConge : $finJour->copy(),
                ];
            }
        }

        foreach ($donnees['rdvs']->get($jour->toDateString(), []) as $rdv) {
            $debut = $jour->copy()->setTimeFromTimeString($rdv->appointment_time->format('H:i:s'));
            $fin = $debut->copy()->addMinutes($rdv->duree_minutes ?: self::DUREE_PAR_DEFAUT);

            $occupes[] = [
                'debut' => $debut->copy()->subMinutes($marge),
                'fin' => $fin->copy()->addMinutes($marge),
            ];
        }

        foreach ($donnees['blocages']->get($jour->toDateString(), []) as $blocage) {
            $debut = $jour->copy()->setTimeFromTimeString(Carbon::parse($blocage->time)->format('H:i:s'));
            $occupes[] = ['debut' => $debut, 'fin' => $debut->copy()->addMinutes(self::DUREE_PAR_DEFAUT)];
        }

        return $occupes;
    }

    /**
     * Soustrait un intervalle occupé d'un intervalle libre : 0, 1 ou 2 résultats.
     */
    protected function soustraire(array $libre, array $occupe): array
    {
        if ($occupe['fin']->lte($libre['debut']) || $occupe['debut']->gte($libre['fin'])) {
            return [$libre];
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
     * Découpe un intervalle libre en points de départ proposables, au pas choisi.
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
