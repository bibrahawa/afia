<?php

namespace App\Services\Labo;

use App\Enums\Labo\ModeFacturation;
use App\Enums\Labo\StatutExamen;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboCreancePartenaire;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboPartenariat;
use App\Models\Labo\LaboRelevePartenaire;
use App\Models\Labo\LaboReglementImputation;
use App\Models\Labo\LaboReglementPartenaire;
use App\Models\User;
use App\Support\Labo\ContexteLabo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Facturation entre établissements : le laboratoire facture la clinique
 * prescriptrice au lieu d'encaisser le patient.
 *
 *   demande (mode partenaire) → créance → relevé de période → règlement
 *
 * Règles :
 *  - une créance par demande, au prix négocié du partenariat ;
 *  - tant que le relevé n'est pas envoyé, la créance suit les examens annulés ;
 *  - un relevé envoyé fige les montants, comme un bordereau d'assurance ;
 *  - un règlement s'impute sur les créances les plus anciennes, ou à la main.
 */
class FacturationPartenaireService
{
    public function __construct(private NumerotationService $numerotation)
    {
    }

    /** Créance d'une demande envoyée par une clinique partenaire (idempotent). */
    public function enregistrerCreance(LaboDemande $demande): ?LaboCreancePartenaire
    {
        if ($demande->mode_facturation !== ModeFacturation::PARTENAIRE || ! $demande->partenariat_id) {
            return null;
        }

        $existante = LaboCreancePartenaire::where('demande_id', $demande->id)->first();

        if ($existante) {
            return $this->recalculer($demande);
        }

        return LaboCreancePartenaire::create([
            'partenariat_id' => $demande->partenariat_id,
            'demande_id' => $demande->id,
            'montant' => $this->montant($demande),
            'statut' => LaboCreancePartenaire::A_FACTURER,
        ]);
    }

    /** Examen annulé ou ajouté : le montant suit, sauf si le relevé est parti. */
    public function recalculer(LaboDemande $demande): ?LaboCreancePartenaire
    {
        $creance = LaboCreancePartenaire::where('demande_id', $demande->id)->first();

        if (! $creance || $creance->estFigee()) {
            return $creance;
        }

        $creance->update(['montant' => $this->montant($demande)]);

        return $creance->fresh();
    }

    /** Demande annulée : la créance disparaît des montants dus, sauf si déjà facturée. */
    public function annulerCreance(LaboDemande $demande): void
    {
        $creance = LaboCreancePartenaire::where('demande_id', $demande->id)->first();

        if (! $creance) {
            return;
        }

        if ($creance->estFigee()) {
            throw new OperationLaboImpossible(
                'Cette demande figure sur le relevé ' . ($creance->releve?->numero ?? '—') . ' déjà envoyé à la clinique : réglez l\'écart avec elle avant d\'annuler.'
            );
        }

        $creance->update(['statut' => LaboCreancePartenaire::ANNULEE, 'montant' => 0]);
    }

    /** Somme des examens non annulés, au prix négocié du partenariat. */
    public function montant(LaboDemande $demande): float
    {
        $demande->loadMissing('examens', 'partenariat');
        $partenariat = $demande->partenariat;

        // Prix négocié ligne par ligne, comme le catalogue affiché à la clinique :
        // arrondir le total donnait un écart de quelques francs avec le devis.
        return $demande->examens
            ->reject(fn ($ligne) => $ligne->statut === StatutExamen::ANNULE)
            ->sum(fn ($ligne) => $partenariat
                ? $partenariat->prixNegocie((float) $ligne->prix_applique)
                : round((float) $ligne->prix_applique));
    }

    // ------------------------------------------------------------------ Relevés

    public function preparerReleve(LaboPartenariat $partenariat, Carbon $debut, Carbon $fin, ?User $auteur = null): LaboRelevePartenaire
    {
        ContexteLabo::verifierAppartenance($partenariat);

        return DB::transaction(function () use ($partenariat, $debut, $fin, $auteur) {
            // Verrou : deux comptables préparant un relevé en même temps se
            // seraient partagé les mêmes créances.
            $creances = LaboCreancePartenaire::where('partenariat_id', $partenariat->id)
                ->where('statut', LaboCreancePartenaire::A_FACTURER)
                ->whereNull('releve_id')
                ->lockForUpdate()
                ->whereHas('demande', fn ($q) => $q->whereBetween('created_at', [$debut->copy()->startOfDay(), $fin->copy()->endOfDay()]))
                ->get();

            if ($creances->isEmpty()) {
                throw new OperationLaboImpossible('Aucune analyse à facturer pour cette période.');
            }

            $releve = LaboRelevePartenaire::create([
                'etablissement_id' => ContexteLabo::etablissementId(),
                'partenariat_id' => $partenariat->id,
                'numero' => $this->numeroReleve(),
                'periode_debut' => $debut->toDateString(),
                'periode_fin' => $fin->toDateString(),
                'echeance' => $partenariat->delai_paiement_jours ? $fin->copy()->addDays($partenariat->delai_paiement_jours)->toDateString() : null,
                'montant_total' => 0,
                'statut' => LaboRelevePartenaire::BROUILLON,
                'cree_par' => $auteur?->id,
            ]);

            LaboCreancePartenaire::whereIn('id', $creances->pluck('id'))->update(['releve_id' => $releve->id]);

            return $this->rafraichirTotal($releve);
        });
    }

    public function retirerDuReleve(LaboCreancePartenaire $creance): LaboRelevePartenaire
    {
        $releve = $creance->releve;

        abort_unless($releve, 404);
        ContexteLabo::verifierAppartenance($releve);

        if ($releve->estEnvoye()) {
            throw new OperationLaboImpossible('Ce relevé est déjà envoyé : rouvrez-le d\'abord.');
        }

        $creance->update(['releve_id' => null]);

        return $this->rafraichirTotal($releve->fresh());
    }

    public function envoyerReleve(LaboRelevePartenaire $releve, ?Carbon $date = null): LaboRelevePartenaire
    {
        ContexteLabo::verifierAppartenance($releve);

        if ($releve->estEnvoye()) {
            throw new OperationLaboImpossible('Ce relevé est déjà envoyé.');
        }

        return DB::transaction(function () use ($releve, $date) {
            $this->rafraichirTotal($releve);

            $releve->update(['statut' => LaboRelevePartenaire::ENVOYE, 'date_envoi' => $date ?? now()]);
            LaboCreancePartenaire::where('releve_id', $releve->id)
                ->where('statut', LaboCreancePartenaire::A_FACTURER)
                ->update(['statut' => LaboCreancePartenaire::FACTUREE]);

            ContexteLabo::journaliser('releve_envoye', $releve, "Relevé {$releve->numero}");

            return $releve->fresh();
        });
    }

    /** Erreur découverte avant tout règlement : le relevé redevient modifiable. */
    public function rouvrirReleve(LaboRelevePartenaire $releve): LaboRelevePartenaire
    {
        ContexteLabo::verifierAppartenance($releve);

        if ($releve->montantRegle() > 0) {
            throw new OperationLaboImpossible('Un règlement est déjà imputé sur ce relevé.');
        }

        $releve->update(['statut' => LaboRelevePartenaire::BROUILLON, 'date_envoi' => null]);
        LaboCreancePartenaire::where('releve_id', $releve->id)
            ->where('statut', LaboCreancePartenaire::FACTUREE)
            ->update(['statut' => LaboCreancePartenaire::A_FACTURER]);

        return $releve->fresh();
    }

    // ------------------------------------------------------------------ Règlements

    /**
     * @param array $imputations [creance_id => montant] ; vide = imputation automatique
     */
    public function enregistrerReglement(LaboPartenariat $partenariat, array $donnees, array $imputations, ?User $auteur = null): LaboReglementPartenaire
    {
        ContexteLabo::verifierAppartenance($partenariat);

        $montant = round((float) ($donnees['montant'] ?? 0));

        if ($montant < 1) {
            throw new OperationLaboImpossible('Le montant reçu doit être supérieur à zéro.');
        }

        return DB::transaction(function () use ($partenariat, $donnees, $imputations, $auteur, $montant) {
            $reglement = LaboReglementPartenaire::create([
                'etablissement_id' => ContexteLabo::etablissementId(),
                'partenariat_id' => $partenariat->id,
                'montant' => $montant,
                'mode' => array_key_exists($donnees['mode'] ?? '', LaboReglementPartenaire::MODES) ? $donnees['mode'] : 'virement',
                'reference' => $donnees['reference'] ?? null,
                'recu_le' => $donnees['recu_le'] ?? today()->toDateString(),
                'notes' => $donnees['notes'] ?? null,
                'enregistre_par' => $auteur?->id,
            ]);

            $lignes = $imputations
                ? $this->imputationsManuelles($partenariat, $imputations, $montant)
                : $this->imputationsAutomatiques($partenariat, $montant);

            foreach ($lignes as $creanceId => $part) {
                $creance = LaboCreancePartenaire::findOrFail($creanceId);

                LaboReglementImputation::create([
                    'reglement_id' => $reglement->id,
                    'creance_id' => $creance->id,
                    'montant' => $part,
                ]);

                $creance->update([
                    'montant_regle' => round((float) $creance->montant_regle + $part),
                    'statut' => round((float) $creance->montant_regle + $part) >= round((float) $creance->montant)
                        ? LaboCreancePartenaire::REGLEE
                        : $creance->statut,
                ]);

                if ($creance->releve_id) {
                    $this->rafraichirStatutReleve($creance->fresh()->releve);
                }
            }

            ContexteLabo::journaliser('reglement_partenaire', $reglement, 'Règlement ' . $partenariat->clinique?->nom);

            return $reglement->fresh('imputations');
        });
    }

    /**
     * Annulation d'un règlement (virement rejeté, double saisie) : les
     * imputations sont défaites, les créances et les relevés reviennent à leur
     * état antérieur. Le règlement reste au dossier avec son motif.
     */
    public function annulerReglement(LaboReglementPartenaire $reglement, string $motif, ?User $auteur = null): LaboReglementPartenaire
    {
        ContexteLabo::verifierAppartenance($reglement);

        if ($reglement->estAnnule()) {
            throw new OperationLaboImpossible('Ce règlement est déjà annulé.');
        }

        return DB::transaction(function () use ($reglement, $motif, $auteur) {
            foreach ($reglement->imputations()->with('creance')->get() as $imputation) {
                $creance = $imputation->creance;

                if ($creance) {
                    $creance->update([
                        'montant_regle' => max(0, round((float) $creance->montant_regle - (float) $imputation->montant)),
                        'statut' => $creance->releve_id ? LaboCreancePartenaire::FACTUREE : LaboCreancePartenaire::A_FACTURER,
                    ]);

                    $this->rafraichirStatutReleve($creance->fresh()->releve);
                }

                $imputation->delete();
            }

            $reglement->update([
                'annule_le' => now(),
                'motif_annulation' => mb_substr(trim($motif), 0, 255) ?: 'Sans motif',
                'annule_par' => $auteur?->id,
            ]);

            ContexteLabo::journaliser('reglement_partenaire_annule', $reglement, $motif);

            return $reglement->fresh();
        });
    }

    /**
     * Ancienneté des créances non réglées : l'outil de recouvrement classique.
     * Le découpage se fait sur la date d'envoi du relevé, à défaut sur la demande.
     */
    public function anciennete(LaboPartenariat $partenariat): array
    {
        $tranches = ['0-30' => 0.0, '30-60' => 0.0, '60-90' => 0.0, '90+' => 0.0];

        $creances = LaboCreancePartenaire::where('partenariat_id', $partenariat->id)
            ->ouvertes()
            ->with(['releve', 'demande'])
            ->get();

        foreach ($creances as $creance) {
            $reste = $creance->resteDu();

            if ($reste < 1) {
                continue;
            }

            $reference = $creance->releve?->date_envoi ?? $creance->demande?->created_at ?? now();
            $jours = $reference->diffInDays(now());

            $cle = match (true) {
                $jours <= 30 => '0-30',
                $jours <= 60 => '30-60',
                $jours <= 90 => '60-90',
                default => '90+',
            };

            $tranches[$cle] += $reste;
        }

        return $tranches;
    }

    /** Créances ouvertes d'un partenaire, les plus anciennes d'abord (relevés envoyés en premier). */
    public function creancesOuvertes(LaboPartenariat $partenariat): Collection
    {
        return LaboCreancePartenaire::where('partenariat_id', $partenariat->id)
            ->ouvertes()
            ->with(['demande', 'releve'])
            ->get()
            ->filter(fn (LaboCreancePartenaire $c) => $c->resteDu() > 0)
            // Les créances déjà facturées (relevé envoyé) se règlent en premier.
            ->sortBy(fn (LaboCreancePartenaire $c) => [$c->statut === LaboCreancePartenaire::FACTUREE ? 0 : 1, $c->id])
            ->values();
    }

    /** Vue d'ensemble par partenaire, pour l'écran des créances. */
    public function resume(LaboPartenariat $partenariat): array
    {
        $creances = LaboCreancePartenaire::where('partenariat_id', $partenariat->id)->ouvertes()->get();
        $releves = LaboRelevePartenaire::where('partenariat_id', $partenariat->id)->get();

        return [
            'a_facturer' => round($creances->where('statut', LaboCreancePartenaire::A_FACTURER)->sum('montant')),
            'facture' => round($creances->where('statut', LaboCreancePartenaire::FACTUREE)->sum(fn ($c) => $c->resteDu())),
            'reste_du' => round($creances->sum(fn ($c) => $c->resteDu())),
            'anciennete' => $this->anciennete($partenariat),
            'releves_en_attente' => $releves->where('statut', LaboRelevePartenaire::ENVOYE)->count(),
            'en_retard' => $releves->filter(fn (LaboRelevePartenaire $r) => $r->enRetard())->count(),
        ];
    }

    // ------------------------------------------------------------------ Interne

    private function imputationsAutomatiques(LaboPartenariat $partenariat, float $montant): array
    {
        $restant = $montant;
        $lignes = [];

        foreach ($this->creancesOuvertes($partenariat) as $creance) {
            if ($restant < 1) {
                break;
            }

            $part = min($creance->resteDu(), $restant);
            $lignes[$creance->id] = $part;
            $restant -= $part;
        }

        if ($restant >= 1) {
            throw new OperationLaboImpossible('Le montant reçu dépasse de ' . number_format($restant, 0, ',', ' ') . ' GNF les analyses dues par cette clinique.');
        }

        return $lignes;
    }

    private function imputationsManuelles(LaboPartenariat $partenariat, array $imputations, float $montant): array
    {
        $lignes = [];
        $total = 0;

        foreach ($imputations as $creanceId => $part) {
            $part = round((float) $part);

            if ($part <= 0) {
                continue;
            }

            $creance = LaboCreancePartenaire::where('partenariat_id', $partenariat->id)->find($creanceId);

            if (! $creance) {
                throw new OperationLaboImpossible('Une créance sélectionnée n\'appartient pas à cette clinique.');
            }

            if ($part > $creance->resteDu()) {
                throw new OperationLaboImpossible("Le montant imputé à la demande {$creance->demande?->numero} dépasse ce qui reste dû.");
            }

            $lignes[$creance->id] = $part;
            $total += $part;
        }

        if (round($total) !== round($montant)) {
            throw new OperationLaboImpossible('La répartition (' . number_format($total, 0, ',', ' ') . ' GNF) doit être égale au montant reçu.');
        }

        return $lignes;
    }

    private function rafraichirTotal(LaboRelevePartenaire $releve): LaboRelevePartenaire
    {
        $releve->update(['montant_total' => round((float) LaboCreancePartenaire::where('releve_id', $releve->id)->sum('montant'))]);

        return $releve->fresh();
    }

    private function rafraichirStatutReleve(?LaboRelevePartenaire $releve): void
    {
        if (! $releve) {
            return;
        }

        $releve->update(['statut' => $releve->resteDu() < 1 ? LaboRelevePartenaire::SOLDE : LaboRelevePartenaire::ENVOYE]);
    }

    private function numeroReleve(): string
    {
        $annee = now()->year;
        $sequence = $this->numerotation->suivant(ContexteLabo::etablissementId(), 'releve-partenaire-' . $annee);

        return sprintf('REL-%d-%04d', $annee, $sequence);
    }
}
