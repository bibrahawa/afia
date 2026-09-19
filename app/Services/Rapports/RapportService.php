<?php

namespace App\Services\Rapports;

use App\Enums\Parcours\StatutVisite;
use App\Models\Consultation;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\Labo\LaboCreancePartenaire;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Paiement;
use App\Models\Parcours\Grossesse;
use App\Models\Parcours\Visite;
use App\Models\Patient;
use App\Models\Transaction;
use App\Services\Assurance\ReglementAssuranceService;
use App\Services\Parcours\StatistiquesParcoursService;
use App\Support\EtablissementContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rapports de l'établissement.
 *
 * Chaque rapport renvoie la MÊME structure — titre, indicateurs, colonnes,
 * lignes, totaux — que l'écran générique sait afficher, imprimer et exporter
 * en CSV. Ajouter un rapport = ajouter une méthode et une entrée au catalogue.
 *
 * Tous les chiffres sortent des données déjà saisies : aucun rapport ne
 * recalcule une règle métier de son côté, ils lisent les mêmes tables que les
 * écrans (soldes, réclamations, créances).
 */
class RapportService
{
    public function __construct(
        private StatistiquesParcoursService $parcours,
        private ReglementAssuranceService $reglements,
    ) {
    }

    /** @return array<string, array{titre: string, description: string, famille: string}> */
    public function catalogue(): array
    {
        $rapports = [
            'activite' => ['titre' => 'Activité de la clinique', 'description' => 'Patients reçus, consultations, attente, absences.', 'famille' => 'Parcours'],
            'recettes' => ['titre' => 'Recettes encaissées', 'description' => 'Encaissements par mode de paiement et par nature d\'acte.', 'famille' => 'Caisse'],
            'impayes' => ['titre' => 'Restes à payer des patients', 'description' => 'Factures dont la part patient n\'est pas soldée.', 'famille' => 'Caisse'],
            'assurance' => ['titre' => 'Créances des assurances', 'description' => 'Reste dû par organisme, écarts et ancienneté.', 'famille' => 'Assurance'],
            'medecins' => ['titre' => 'Activité par médecin', 'description' => 'Consultations, patients reçus et actes facturés.', 'famille' => 'Parcours'],
            'diagnostics' => ['titre' => 'Diagnostics et motifs', 'description' => 'Ce qui amène les patients, par fréquence.', 'famille' => 'Parcours'],
            'patients' => ['titre' => 'Patients', 'description' => 'Nouveaux patients, répartition par sexe et par âge.', 'famille' => 'Patients'],
            'grossesses' => ['titre' => 'Suivis de grossesse', 'description' => 'Grossesses en cours, termes et consultations à programmer.', 'famille' => 'Parcours'],
        ];

        if (Schema::hasTable('labo_demandes')) {
            $rapports['laboratoire'] = ['titre' => 'Activité du laboratoire', 'description' => 'Demandes, examens et délais de rendu.', 'famille' => 'Laboratoire'];
        }

        if (Schema::hasTable('labo_partenariats')) {
            $rapports['reseau'] = ['titre' => 'Laboratoire en réseau', 'description' => 'Demandes échangées et créances entre établissements.', 'famille' => 'Laboratoire'];
        }

        return $rapports;
    }

    public function existe(string $cle): bool
    {
        return array_key_exists($cle, $this->catalogue());
    }

    public function produire(string $cle, Carbon $debut, Carbon $fin): array
    {
        $debut = $debut->copy()->startOfDay();
        $fin = $fin->copy()->endOfDay();

        $rapport = match ($cle) {
            'activite' => $this->activite($debut, $fin),
            'recettes' => $this->recettes($debut, $fin),
            'impayes' => $this->impayes($debut, $fin),
            'assurance' => $this->assurance(),
            'medecins' => $this->medecins($debut, $fin),
            'diagnostics' => $this->diagnostics($debut, $fin),
            'patients' => $this->patients($debut, $fin),
            'grossesses' => $this->grossesses(),
            'laboratoire' => $this->laboratoire($debut, $fin),
            'reseau' => $this->reseau($debut, $fin),
            default => throw new \InvalidArgumentException('Rapport inconnu.'),
        };

        return $rapport + [
            'cle' => $cle,
            'titre' => $this->catalogue()[$cle]['titre'],
            'periode' => ['debut' => $debut, 'fin' => $fin],
            'indicateurs' => $rapport['indicateurs'] ?? [],
            'totaux' => $rapport['totaux'] ?? [],
            'note' => $rapport['note'] ?? null,
        ];
    }

    // ------------------------------------------------------------------ Parcours

    private function activite(Carbon $debut, Carbon $fin): array
    {
        $stats = $this->parcours->resume($debut, $fin);

        $lignes = [
            ['Patients reçus', $stats['visites']],
            ['Consultations terminées', $stats['terminees']],
            ['Encore en cours', $stats['en_cours']],
            ['Repartis sans consulter', $stats['parties']],
            ['Urgences', $stats['urgences']],
            ['Venues sans rendez-vous', $stats['sans_rendez_vous']],
            ['Attente moyenne (min)', $stats['attente_moyenne'] ?? '—'],
            ['Attente médiane (min)', $stats['attente_mediane'] ?? '—'],
            ['Attente maximum (min)', $stats['attente_max'] ?? '—'],
            ['Rendez-vous non honorés', $stats['rdv_absents']],
            ['Taux d\'absence (%)', $stats['taux_absence'] ?? '—'],
        ];

        return [
            'colonnes' => ['Indicateur', 'Valeur'],
            'lignes' => $lignes,
            'indicateurs' => [
                'Patients reçus' => $stats['visites'],
                'Attente médiane' => ($stats['attente_mediane'] ?? '—') . ' min',
                'Actes de consultation' => $this->gnf($stats['recette_actes']),
            ],
            'note' => 'L\'attente est comptée entre l\'arrivée et l\'appel du patient.',
        ];
    }

    private function medecins(Carbon $debut, Carbon $fin): array
    {
        $visites = Visite::whereBetween('arrivee_le', [$debut, $fin])->with('medecin')->get();

        $consultations = Consultation::whereBetween('created_at', [$debut, $fin])
            ->select('medecin_id', DB::raw('COUNT(*) as total'))
            ->groupBy('medecin_id')
            ->pluck('total', 'medecin_id');

        $lignes = $visites->groupBy('medecin_id')->map(function ($groupe, $medecinId) use ($consultations) {
            $medecin = $groupe->first()->medecin;

            return [
                $medecin?->nom_affiche ?? '—',
                $groupe->count(),
                $groupe->where('statut', StatutVisite::Terminee)->count(),
                (int) ($consultations[$medecinId] ?? 0),
                $groupe->where('urgence', true)->count(),
            ];
        })->sortByDesc(fn ($ligne) => $ligne[1])->values()->all();

        return [
            'colonnes' => ['Médecin', 'Patients reçus', 'Terminées', 'Consultations', 'Urgences'],
            'lignes' => $lignes,
            'totaux' => ['Total', array_sum(array_column($lignes, 1)), array_sum(array_column($lignes, 2)), array_sum(array_column($lignes, 3)), array_sum(array_column($lignes, 4))],
        ];
    }

    private function diagnostics(Carbon $debut, Carbon $fin): array
    {
        $diagnostics = Consultation::whereBetween('created_at', [$debut, $fin])
            ->whereNotNull('diagnostic')->where('diagnostic', '!=', '')->where('diagnostic', '!=', 'N/A')
            ->select('diagnostic', DB::raw('COUNT(*) as total'))
            ->groupBy('diagnostic')->orderByDesc('total')->limit(30)->get();

        $motifs = Visite::whereBetween('arrivee_le', [$debut, $fin])->get()
            ->groupBy('motif')->map->count()->sortDesc()->take(15);

        $lignes = $diagnostics->map(fn ($d) => ['Diagnostic', $d->diagnostic, $d->total])->all();

        foreach ($motifs as $motif => $total) {
            $lignes[] = ['Motif de visite', $motif ?: '—', $total];
        }

        return [
            'colonnes' => ['Type', 'Libellé', 'Nombre'],
            'lignes' => $lignes,
            'note' => 'Les 30 diagnostics et 15 motifs les plus fréquents de la période.',
        ];
    }

    private function patients(Carbon $debut, Carbon $fin): array
    {
        $nouveaux = Patient::suivisParEtablissement()
            ->whereBetween('patients.created_at', [$debut, $fin])
            ->get(['patients.id', 'gender', 'birth_date', 'patients.created_at']);

        $tranches = ['0-4 ans' => 0, '5-14 ans' => 0, '15-49 ans' => 0, '50 ans et plus' => 0, 'Âge inconnu' => 0];

        foreach ($nouveaux as $patient) {
            $naissance = $patient->dateNaissance();
            $age = $naissance ? $naissance->age : null;

            $cle = match (true) {
                $age === null => 'Âge inconnu',
                $age < 5 => '0-4 ans',
                $age < 15 => '5-14 ans',
                $age < 50 => '15-49 ans',
                default => '50 ans et plus',
            };

            $tranches[$cle]++;
        }

        $lignes = [];
        foreach ($nouveaux->groupBy('gender') as $sexe => $groupe) {
            $lignes[] = ['Sexe', $sexe ?: '—', $groupe->count()];
        }
        foreach ($tranches as $tranche => $total) {
            $lignes[] = ['Âge', $tranche, $total];
        }

        return [
            'colonnes' => ['Répartition', 'Valeur', 'Nombre'],
            'lignes' => $lignes,
            'indicateurs' => ['Nouveaux patients' => $nouveaux->count()],
        ];
    }

    private function grossesses(): array
    {
        $suivis = Grossesse::where('statut', Grossesse::EN_COURS)->with(['patient', 'medecin'])->orderBy('dpa')->get();

        $lignes = $suivis->map(function (Grossesse $g) {
            $prochain = $g->prochainContact();

            return [
                $g->patient?->full_name ?? '—',
                $g->termeLisible(),
                $g->dpa->format('d/m/Y'),
                $prochain ? $prochain['semaines'] . ' SA le ' . $prochain['date_cible']->format('d/m/Y') : '—',
                $g->medecin?->nom_affiche ?? '—',
            ];
        })->all();

        return [
            'colonnes' => ['Patiente', 'Terme', 'Accouchement prévu', 'Prochaine CPN', 'Médecin'],
            'lignes' => $lignes,
            'indicateurs' => [
                'Grossesses suivies' => $suivis->count(),
                'Termes dépassés' => $suivis->filter(fn (Grossesse $g) => $g->dpa->isPast())->count(),
            ],
            'note' => 'État du jour, indépendant de la période choisie.',
        ];
    }

    // ------------------------------------------------------------------ Caisse

    private function recettes(Carbon $debut, Carbon $fin): array
    {
        $paiements = Paiement::whereBetween('created_at', [$debut, $fin])->with('transaction')->get();

        $lignes = [];

        foreach ($paiements->groupBy('type') as $type => $groupe) {
            foreach ($groupe->groupBy(fn ($p) => $p->source ?: 'Non précisé') as $source => $parSource) {
                $lignes[] = [
                    $type === 'remboursement' ? 'Règlement assurance' : ($type === 'remise' ? 'Remise' : 'Encaissement patient'),
                    $source,
                    $parSource->count(),
                    round($parSource->sum('montant')),
                ];
            }
        }

        $parNature = $paiements->groupBy(fn ($p) => $p->transaction?->transactionable_type ?: 'inconnu')
            ->map(fn ($groupe) => round($groupe->sum('montant')));

        foreach ($parNature as $nature => $montant) {
            $lignes[] = ['Par nature', $this->libelleNature($nature), '', $montant];
        }

        return [
            'colonnes' => ['Catégorie', 'Détail', 'Nombre', 'Montant (GNF)'],
            'lignes' => $lignes,
            'indicateurs' => [
                'Total encaissé' => $this->gnf($paiements->where('type', '!=', 'remise')->sum('montant')),
                'Dont assurances' => $this->gnf($paiements->where('type', 'remboursement')->sum('montant')),
                'Remises accordées' => $this->gnf($paiements->where('type', 'remise')->sum('montant')),
            ],
            'note' => 'Les paiements annulés sont exclus. Le détail par nature reprend les mêmes montants, vus autrement.',
        ];
    }

    private function impayes(Carbon $debut, Carbon $fin): array
    {
        $transactions = Transaction::whereBetween('created_at', [$debut, $fin])
            ->where('status', '!=', 'cancel')
            ->with(['patient', 'invoice'])
            ->get();

        $lignes = [];
        $total = 0.0;

        foreach ($transactions as $transaction) {
            $solde = \App\Support\Facturation\SoldeTransaction::pour($transaction);
            $reste = round($solde->resteDuPatient());

            if ($reste < 1) {
                continue;
            }

            $lignes[] = [
                $transaction->created_at->format('d/m/Y'),
                $transaction->invoice_no ?? $transaction->id,
                $transaction->patient?->full_name ?? '—',
                $this->libelleNature($transaction->transactionable_type),
                round($solde->partPatient),
                $reste,
            ];
            $total += $reste;
        }

        return [
            'colonnes' => ['Date', 'Facture', 'Patient', 'Nature', 'Part patient (GNF)', 'Reste dû (GNF)'],
            'lignes' => $lignes,
            'totaux' => ['', '', '', 'Total', '', round($total)],
            'indicateurs' => ['Factures non soldées' => count($lignes), 'Reste à encaisser' => $this->gnf($total)],
        ];
    }

    // ------------------------------------------------------------------ Assurance

    private function assurance(): array
    {
        $lignes = [];
        $resteTotal = 0.0;

        foreach (InsuranceCompany::orderBy('name')->get() as $organisme) {
            $ouvertes = $this->reglements->reclamationsOuvertes($organisme);

            if ($ouvertes->isEmpty()) {
                continue;
            }

            $anciennete = $this->reglements->anciennete($organisme);
            $reste = round($ouvertes->sum(fn (InsuranceClaim $c) => $c->resteDu()));
            $resteTotal += $reste;

            $lignes[] = [
                $organisme->name,
                $ouvertes->count(),
                $reste,
                round($ouvertes->sum(fn (InsuranceClaim $c) => $c->ecartEnAttente())),
                round($anciennete['0-30']),
                round($anciennete['30-60'] + $anciennete['60-90']),
                round($anciennete['90+']),
            ];
        }

        return [
            'colonnes' => ['Organisme', 'Réclamations', 'Reste dû (GNF)', 'Écarts (GNF)', '0-30 j', '30-90 j', 'Plus de 90 j'],
            'lignes' => $lignes,
            'totaux' => ['Total', array_sum(array_column($lignes, 1)), round($resteTotal), '', '', '', ''],
            'indicateurs' => ['Reste dû par les assurances' => $this->gnf($resteTotal)],
            'note' => 'État du jour. L\'ancienneté part de l\'envoi du bordereau.',
        ];
    }

    // ------------------------------------------------------------------ Laboratoire

    private function laboratoire(Carbon $debut, Carbon $fin): array
    {
        $demandes = LaboDemande::whereBetween('created_at', [$debut, $fin])->with('examens')->get();

        $lignes = [];

        foreach ($demandes->groupBy(fn (LaboDemande $d) => $d->statut->libelle()) as $statut => $groupe) {
            $lignes[] = ['Statut', $statut, $groupe->count()];
        }

        foreach ($demandes->groupBy(fn (LaboDemande $d) => $d->origine->libelle()) as $origine => $groupe) {
            $lignes[] = ['Origine', $origine, $groupe->count()];
        }

        $examens = LaboDemandeExamen::whereHas('demande', fn ($q) => $q->whereBetween('created_at', [$debut, $fin]))
            ->select('examen_nom', DB::raw('COUNT(*) as total'))
            ->groupBy('examen_nom')->orderByDesc('total')->limit(20)->get();

        foreach ($examens as $examen) {
            $lignes[] = ['Examen', $examen->examen_nom, $examen->total];
        }

        $delais = $demandes->filter(fn (LaboDemande $d) => $d->premiere_publication_le)
            ->map(fn (LaboDemande $d) => $d->created_at->diffInHours($d->premiere_publication_le));

        return [
            'colonnes' => ['Type', 'Libellé', 'Nombre'],
            'lignes' => $lignes,
            'indicateurs' => [
                'Demandes' => $demandes->count(),
                'Publiées' => $demandes->filter(fn (LaboDemande $d) => $d->premiere_publication_le)->count(),
                'Délai moyen de rendu' => $delais->isEmpty() ? '—' : round($delais->avg()) . ' h',
            ],
        ];
    }

    private function reseau(Carbon $debut, Carbon $fin): array
    {
        $etablissementId = EtablissementContext::id();

        $recues = LaboDemande::whereBetween('created_at', [$debut, $fin])->whereNotNull('partenariat_id')->count();

        $envoyees = LaboDemande::withoutGlobalScopes()
            ->whereBetween('created_at', [$debut, $fin])
            ->where('etablissement_prescripteur_id', $etablissementId)
            ->count();

        $lignes = [];

        foreach (LaboPartenariat::with('clinique')->get() as $partenariat) {
            $creances = LaboCreancePartenaire::where('partenariat_id', $partenariat->id)->ouvertes()->get();

            $lignes[] = [
                'Clinique partenaire',
                $partenariat->clinique?->nom ?? '—',
                $partenariat->libelleStatut(),
                LaboDemande::where('partenariat_id', $partenariat->id)->whereBetween('created_at', [$debut, $fin])->count(),
                round($creances->sum(fn (LaboCreancePartenaire $c) => $c->resteDu())),
            ];
        }

        foreach (LaboPartenariat::withoutGlobalScopes()->where('clinique_id', $etablissementId)->with('laboratoire')->get() as $partenariat) {
            $lignes[] = [
                'Laboratoire partenaire',
                $partenariat->laboratoire?->nom ?? '—',
                $partenariat->libelleStatut(),
                LaboDemande::withoutGlobalScopes()->where('partenariat_id', $partenariat->id)
                    ->where('etablissement_prescripteur_id', $etablissementId)
                    ->whereBetween('created_at', [$debut, $fin])->count(),
                '',
            ];
        }

        return [
            'colonnes' => ['Rôle', 'Établissement', 'Statut', 'Demandes sur la période', 'Reste dû (GNF)'],
            'lignes' => $lignes,
            'indicateurs' => ['Demandes reçues' => $recues, 'Demandes envoyées' => $envoyees],
            'note' => 'Le reste dû ne concerne que les cliniques que vous facturez.',
        ];
    }

    // ------------------------------------------------------------------ Outils

    private function libelleNature(?string $type): string
    {
        return match ($type) {
            'consultation' => 'Consultations',
            'hospitalisation' => 'Hospitalisations',
            'labo_demande' => 'Laboratoire',
            null, '' => 'Non précisé',
            default => ucfirst(str_replace('_', ' ', class_basename((string) $type))),
        };
    }

    private function gnf(float|int|null $montant): string
    {
        return number_format((float) $montant, 0, ',', ' ') . ' GNF';
    }
}
