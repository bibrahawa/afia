<?php

namespace App\Services\Cloche;

use App\Models\User;
use App\Support\EtablissementContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Lot S4 — La cloche : uniquement ce qui demande une action.
 *
 * Aucune table de notifications : chaque alerte est CALCULÉE à partir de l'état
 * réel et disparaît d'elle-même quand le problème est réglé (valeur signalée,
 * patient appelé, écart transféré). Rien à « marquer comme lu », rien qui
 * s'accumule. Chaque source n'est montrée qu'aux personnes qui peuvent agir.
 *
 * Résultat mis en cache 60 s par utilisateur : la cloche est dans la barre du
 * haut de TOUTES les pages.
 */
class ClocheService
{
    public const ATTENTE_MINUTES = 60;
    private const LIMITE_PAR_SOURCE = 8;

    /** @return Collection<int, array{cle:string, niveau:string, icone:string, titre:string, detail:string, lien:?string, quand:?\Carbon\CarbonInterface}> */
    public function alertes(?User $utilisateur): Collection
    {
        $etablissementId = EtablissementContext::id();
        if (! $utilisateur || ! $etablissementId) {
            return collect();   // administrateur plateforme hors établissement : rien à traiter ici
        }

        return Cache::remember("cloche:{$utilisateur->id}:{$etablissementId}", 60, function () use ($utilisateur) {
            $etablissement = EtablissementContext::current();

            return collect()
                ->concat($this->source('critiques', fn () => $this->valeursCritiques($utilisateur, $etablissement)))
                ->concat($this->source('attente', fn () => $this->attenteLongue($utilisateur, $etablissement)))
                ->concat($this->source('rejets', fn () => $this->reclamationsRejetees($utilisateur, $etablissement)))
                ->sortBy(fn ($a) => [$a['niveau'] === 'danger' ? 0 : 1, -($a['quand']?->getTimestamp() ?? 0)])
                ->values();
        });
    }

    /** Une source en panne ne doit jamais casser la barre du haut. */
    private function source(string $nom, callable $calcul): Collection
    {
        try {
            return collect($calcul());
        } catch (\Throwable $e) {
            Log::warning("Cloche : source « {$nom} » indisponible", ['error' => $e->getMessage()]);

            return collect();
        }
    }

    private function module($etablissement, string $code): bool
    {
        return ! $etablissement || ! method_exists($etablissement, 'aModule') || $etablissement->aModule($code);
    }

    // ---------------------------------------------------------------- 1. Labo

    /** Valeur critique (LL/HH) sans signalement postérieur à sa saisie : patient en danger. */
    private function valeursCritiques(User $u, $etablissement): Collection
    {
        if (! $this->module($etablissement, 'laboratoire')
            || ! ($u->can('labo.validation.technique') || $u->can('labo.validation.biologique') || $u->can('labo.tableau_bord'))
            || ! class_exists(\App\Models\Labo\LaboResultat::class)) {
            return collect();
        }

        return \App\Models\Labo\LaboResultat::with(['demandeExamen.demande.patient', 'parametre'])
            ->whereIn('flag', ['LL', 'HH'])
            ->whereDoesntHave('alertes', fn ($q) => $q->whereColumn('labo_alertes_critiques.signale_le', '>=', 'labo_resultats.saisi_le'))
            ->whereHas('demandeExamen', fn ($q) => $q->where('statut', '!=', \App\Enums\Labo\StatutExamen::ANNULE->value))
            ->latest('saisi_le')
            ->limit(self::LIMITE_PAR_SOURCE)
            ->get()
            ->map(function ($r) {
                $demande = $r->demandeExamen?->demande;

                return [
                    'cle' => 'critique-' . $r->id,
                    'niveau' => 'danger',
                    'icone' => 'fa-exclamation-triangle',
                    'titre' => 'Valeur critique à signaler',
                    'detail' => trim(($demande?->patient?->full_name ?? 'Patient') . ' · ' . ($r->parametre?->libelle ?? 'résultat') . ' ' . ($r->flag === 'HH' ? 'très élevé' : 'très bas')),
                    'lien' => $demande && Route::has('labo.demandes.show') ? route('labo.demandes.show', $demande) : null,
                    'quand' => $r->saisi_le,
                ];
            });
    }

    // ------------------------------------------------------------- 2. Attente

    /** Patient arrivé aujourd'hui, toujours en attente depuis plus d'une heure. */
    private function attenteLongue(User $u, $etablissement): Collection
    {
        $accueil = $u->can('parcours.accueil');
        $medecin = $u->can('parcours.file') && $u->employee?->type === 'Doctor';
        if (! ($accueil || $medecin) || ! class_exists(\App\Models\Parcours\Visite::class)) {
            return collect();
        }

        return \App\Models\Parcours\Visite::with(['patient', 'medecin'])
            ->where('statut', \App\Enums\Parcours\StatutVisite::EnAttente->value)
            ->where('arrivee_le', '>=', today())
            ->where('arrivee_le', '<=', now()->subMinutes(self::ATTENTE_MINUTES))
            // Un médecin (sans rôle d'accueil) ne voit que SA file.
            ->when(! $accueil, fn ($q) => $q->where('medecin_id', $u->employee->id))
            ->orderBy('arrivee_le')
            ->limit(self::LIMITE_PAR_SOURCE)
            ->get()
            ->map(function ($v) use ($accueil) {
                $minutes = $v->minutesAttente();
                $duree = $minutes >= 120 ? intdiv($minutes, 60) . ' h ' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT) : $minutes . ' min';
                $route = $accueil ? 'parcours.accueil.index' : 'parcours.file.index';

                return [
                    'cle' => 'attente-' . $v->id,
                    // Rouge : plus de 2 h, ou patient signalé urgent.
                    'niveau' => ($minutes >= 120 || $v->urgence) ? 'danger' : 'alerte',
                    'icone' => 'fa-hourglass-half',
                    'titre' => "Attend depuis {$duree}",
                    'detail' => trim(($v->patient?->full_name ?? 'Patient') . ($v->medecin ? ' · ' . $v->medecin->nom_affiche : '') . ($v->urgence ? ' · URGENT' : '')),
                    'lien' => Route::has($route) ? route($route) : null,
                    'quand' => $v->arrivee_le,
                ];
            });
    }

    // ------------------------------------------------------------ 3. Assurance

    /** Réclamation rejetée (totalement ou en partie) dont l'écart n'est ni transféré ni soldé. */
    private function reclamationsRejetees(User $u, $etablissement): Collection
    {
        if (! $this->module($etablissement, 'assurance') || ! $u->can('assurance.reclamation.gerer')) {
            return collect();
        }

        return \App\Models\InsuranceClaim::with(['patient'])
            ->whereIn('status', ['rejected', 'approved'])
            ->whereNotNull('approved_amount')
            ->whereRaw('approved_amount + COALESCE(montant_transfere_patient, 0) < claimed_amount - 0.01')
            ->latest('updated_at')
            ->limit(self::LIMITE_PAR_SOURCE)
            ->get()
            // Contrôle fin (règlements avec remise déjà passés) : écart réellement en attente.
            ->filter(fn ($c) => $c->ecartEnAttente() >= 1)
            ->map(function ($c) {
                $ecart = number_format($c->ecartEnAttente(), 0, ',', ' ');

                return [
                    'cle' => 'rejet-' . $c->id,
                    'niveau' => 'alerte',
                    'icone' => 'fa-file-invoice-dollar',
                    'titre' => $c->status === 'rejected' ? 'Réclamation rejetée' : 'Réclamation acceptée en partie',
                    'detail' => trim(($c->patient?->full_name ?? 'Patient') . " · {$ecart} GNF à régler"),
                    'lien' => Route::has('assurance.reclamations.show') ? route('assurance.reclamations.show', $c) : null,
                    'quand' => $c->updated_at,
                ];
            })
            ->values();
    }
}
