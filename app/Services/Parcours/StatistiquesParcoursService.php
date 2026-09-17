<?php

namespace App\Services\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Parcours\Visite;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres du parcours sur une période : activité, attente, absences,
 * diagnostics et motifs les plus fréquents. Tout se déduit des visites et des
 * consultations déjà saisies, sans écran de saisie supplémentaire.
 */
class StatistiquesParcoursService
{
    public function resume(Carbon $debut, Carbon $fin, ?int $medecinId = null): array
    {
        $debut = $debut->copy()->startOfDay();
        $fin = $fin->copy()->endOfDay();

        $visites = Visite::whereBetween('arrivee_le', [$debut, $fin])
            ->when($medecinId, fn ($q) => $q->where('medecin_id', $medecinId))
            ->get(['id', 'medecin_id', 'motif', 'motif_rdv_id', 'statut', 'urgence', 'appointment_id', 'arrivee_le', 'appele_le', 'terminee_le']);

        $attentes = $visites->filter(fn (Visite $v) => $v->appele_le)
            ->map(fn (Visite $v) => (int) $v->arrivee_le->diffInMinutes($v->appele_le, true))
            ->sort()
            ->values();

        $rdv = Appointment::whereBetween('appointment_date', [$debut->toDateString(), $fin->toDateString()])
            ->when($medecinId, fn ($q) => $q->where('employee_id', $medecinId))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $rdvPasses = (int) ($rdv['completed'] ?? 0) + (int) ($rdv['no_show'] ?? 0);

        return [
            'periode' => ['debut' => $debut, 'fin' => $fin],
            'visites' => $visites->count(),
            'terminees' => $visites->where('statut', StatutVisite::Terminee)->count(),
            'en_cours' => $visites->filter(fn (Visite $v) => $v->statut->estActive())->count(),
            'parties' => $visites->where('statut', StatutVisite::Partie)->count(),
            'urgences' => $visites->where('urgence', true)->count(),
            'sans_rendez_vous' => $visites->whereNull('appointment_id')->count(),
            'attente_moyenne' => $attentes->isEmpty() ? null : (int) round($attentes->avg()),
            'attente_mediane' => $attentes->isEmpty() ? null : (int) $attentes[intdiv($attentes->count(), 2)],
            'attente_max' => $attentes->max(),
            'rdv_absents' => (int) ($rdv['no_show'] ?? 0),
            'taux_absence' => $rdvPasses > 0 ? round(($rdv['no_show'] ?? 0) * 100 / $rdvPasses, 1) : null,
            'par_medecin' => $this->parMedecin($debut, $fin, $medecinId),
            'motifs' => $visites->groupBy('motif')->map->count()->sortDesc()->take(8),
            'diagnostics' => $this->diagnostics($debut, $fin, $medecinId),
            'recette_actes' => $this->recette($debut, $fin, $medecinId),
            // Détail utile à la direction : qui doit payer quoi sur ces actes.
            'recette_parts' => $this->parts($debut, $fin, $medecinId),
        ];
    }

    private function parMedecin(Carbon $debut, Carbon $fin, ?int $medecinId)
    {
        return Visite::whereBetween('arrivee_le', [$debut, $fin])
            ->when($medecinId, fn ($q) => $q->where('medecin_id', $medecinId))
            ->with('medecin')
            ->get()
            ->groupBy('medecin_id')
            ->map(fn ($groupe) => [
                'medecin' => $groupe->first()->medecin,
                'visites' => $groupe->count(),
                'terminees' => $groupe->where('statut', StatutVisite::Terminee)->count(),
            ])
            ->sortByDesc('visites')
            ->values();
    }

    private function diagnostics(Carbon $debut, Carbon $fin, ?int $medecinId)
    {
        return Consultation::whereBetween('created_at', [$debut, $fin])
            ->when($medecinId, fn ($q) => $q->where('medecin_id', $medecinId))
            ->whereNotNull('diagnostic')
            ->where('diagnostic', '!=', '')
            ->where('diagnostic', '!=', 'N/A')
            ->select('diagnostic', DB::raw('COUNT(*) as total'))
            ->groupBy('diagnostic')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'diagnostic');
    }

    /** Part patient et part assurance des mêmes factures de consultation. */
    private function parts(Carbon $debut, Carbon $fin, ?int $medecinId): array
    {
        $ligne = DB::table('invoices as i')
            ->join('transactions as t', 't.id', '=', 'i.transaction_id')
            ->join('consultations as c', function ($jointure) {
                $jointure->on('c.id', '=', 't.transactionable_id')
                    ->whereIn('t.transactionable_type', \App\Support\Facturation\TypesFacturables::variantes(Consultation::class));
            })
            ->whereBetween('c.created_at', [$debut, $fin])
            ->when($medecinId, fn ($q) => $q->where('c.medecin_id', $medecinId))
            ->where('t.status', '!=', 'cancel')
            ->when(\App\Support\EtablissementContext::id(), fn ($q, $etablissementId) => $q->where('t.etablissement_id', $etablissementId))
            ->selectRaw('COALESCE(SUM(i.patient_amount), 0) as patient, COALESCE(SUM(i.insurance_amount), 0) as assurance')
            ->first();

        return ['patient' => (float) ($ligne->patient ?? 0), 'assurance' => (float) ($ligne->assurance ?? 0)];
    }

    /** Montant facturé pour les consultations de la période (hors hospitalisation et laboratoire). */
    private function recette(Carbon $debut, Carbon $fin, ?int $medecinId): float
    {
        return (float) DB::table('transactions as t')
            ->join('consultations as c', function ($jointure) {
                $jointure->on('c.id', '=', 't.transactionable_id')
                    ->whereIn('t.transactionable_type', \App\Support\Facturation\TypesFacturables::variantes(Consultation::class));
            })
            ->whereBetween('c.created_at', [$debut, $fin])
            ->when($medecinId, fn ($q) => $q->where('c.medecin_id', $medecinId))
            ->where('t.status', '!=', 'cancel')
            ->when(\App\Support\EtablissementContext::id(), fn ($q, $etablissementId) => $q->where('t.etablissement_id', $etablissementId))
            ->sum('t.total');
    }
}
