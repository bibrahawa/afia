<?php

namespace App\Services\Labo;

use App\Models\User;
use App\Enums\Labo\StatutEchantillon;
use App\Enums\Labo\StatutExamen;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboEchantillon;
use App\Support\Labo\ContexteLabo;
use Illuminate\Support\Facades\DB;

/**
 * Phase pré-analytique : prélèvement, réception au labo, rejet et
 * re-prélèvement. Le statut d'un examen avance quand TOUS ses contenants
 * valides ont avancé.
 */
class PrelevementService
{
    public function __construct(private DemandeService $demandes)
    {
    }

    public function marquerPreleve(LaboEchantillon $echantillon, User $preleveur): void
    {
        ContexteLabo::verifierAppartenance($echantillon);
        $this->exigerDemandeActive($echantillon->demande);

        if ($echantillon->statut !== StatutEchantillon::ATTENDU) {
            throw new OperationLaboImpossible("Le contenant {$echantillon->code_barres} n'est pas en attente de prélèvement.");
        }

        DB::transaction(function () use ($echantillon, $preleveur) {
            $echantillon->update([
                'statut' => StatutEchantillon::PRELEVE,
                'preleve_par' => $preleveur->id,
                'preleve_le' => now(),
            ]);

            $this->avancerExamens($echantillon);
        });
    }

    /** Prélèvement + réception en une fois : cas du labo où le préleveur est à côté de la paillasse. */
    public function marquerPreleveEtRecu(LaboEchantillon $echantillon, User $utilisateur): void
    {
        if ($echantillon->statut === StatutEchantillon::ATTENDU) {
            $this->marquerPreleve($echantillon, $utilisateur);
            $echantillon->refresh();
        }

        $this->receptionner($echantillon, $utilisateur);
    }

    public function receptionner(LaboEchantillon $echantillon, User $receveur): void
    {
        ContexteLabo::verifierAppartenance($echantillon);
        $this->exigerDemandeActive($echantillon->demande);

        if ($echantillon->statut !== StatutEchantillon::PRELEVE) {
            throw new OperationLaboImpossible(match ($echantillon->statut) {
                StatutEchantillon::ATTENDU => "Le contenant {$echantillon->code_barres} n'a pas encore été prélevé.",
                StatutEchantillon::RECU => "Le contenant {$echantillon->code_barres} a déjà été réceptionné.",
                StatutEchantillon::REJETE => "Le contenant {$echantillon->code_barres} a été rejeté : utilisez le nouveau contenant.",
            });
        }

        DB::transaction(function () use ($echantillon, $receveur) {
            $echantillon->update([
                'statut' => StatutEchantillon::RECU,
                'recu_par' => $receveur->id,
                'recu_le' => now(),
            ]);

            $this->avancerExamens($echantillon);
        });
    }

    /**
     * Rejet pour non-conformité : crée AUTOMATIQUEMENT le contenant de
     * remplacement (nouveau code-barres) et remet les examens concernés
     * « à prélever ». Le contenant rejeté reste en base pour les
     * statistiques de non-conformité.
     */
    public function rejeter(LaboEchantillon $echantillon, string $motif, ?string $commentaire, User $auteur): LaboEchantillon
    {
        ContexteLabo::verifierAppartenance($echantillon);
        $this->exigerDemandeActive($echantillon->demande);

        if (! in_array($echantillon->statut, [StatutEchantillon::PRELEVE, StatutEchantillon::RECU], true)) {
            throw new OperationLaboImpossible('Seul un contenant prélevé ou reçu peut être rejeté.');
        }

        $examens = $echantillon->examens()->get();
        if ($examens->contains(fn (LaboDemandeExamen $e) => $e->statut->rang() >= StatutExamen::VALIDE_TECHNIQUE->rang() && $e->statut !== StatutExamen::ANNULE)) {
            throw new OperationLaboImpossible('Un examen de ce contenant est déjà validé : rejet impossible.');
        }

        return DB::transaction(function () use ($echantillon, $motif, $commentaire, $auteur, $examens) {
            $echantillon->update([
                'statut' => StatutEchantillon::REJETE,
                'rejete_par' => $auteur->id,
                'rejete_le' => now(),
                'motif_rejet' => $motif,
                'commentaire' => $commentaire,
            ]);

            $demande = $echantillon->demande;
            $index = $demande->echantillons()->count() + 1;

            $remplacant = LaboEchantillon::create([
                'etablissement_id' => $echantillon->etablissement_id,
                'demande_id' => $demande->id,
                'code_barres' => NumerotationService::codeBarres($demande->numero, $index),
                'type_echantillon' => $echantillon->type_echantillon,
                'tube' => $echantillon->tube,
                'statut' => StatutEchantillon::ATTENDU,
                'remplace_echantillon_id' => $echantillon->id,
            ]);

            $actifs = $examens->reject(fn ($e) => $e->statut === StatutExamen::ANNULE);
            $remplacant->examens()->attach($actifs->pluck('id'));

            foreach ($actifs as $examen) {
                $examen->update(['statut' => StatutExamen::EN_ATTENTE_PRELEVEMENT]);
            }

            $this->demandes->rafraichirStatut($demande);

            ContexteLabo::journaliser('echantillon_rejete', $echantillon, StatutEchantillon::motifsRejet()[$motif] ?? $motif, [
                'remplace_par' => $remplacant->code_barres,
            ]);

            return $remplacant;
        });
    }

    public function marquerEnvoiSousTraitance(LaboDemandeExamen $examen): void
    {
        ContexteLabo::verifierAppartenance($examen);

        if (! $examen->sous_traite || $examen->statut !== StatutExamen::RECU) {
            throw new OperationLaboImpossible('Seul un examen sous-traité, réceptionné et non encore analysé peut être envoyé.');
        }

        $examen->update(['envoye_sous_traitant_le' => now()]);
        ContexteLabo::journaliser('envoi_sous_traitance', $examen, $examen->laboratoire_sous_traitant);
    }

    private function avancerExamens(LaboEchantillon $echantillon): void
    {
        foreach ($echantillon->examens()->with('echantillons')->get() as $examen) {
            if (! in_array($examen->statut, [StatutExamen::EN_ATTENTE_PRELEVEMENT, StatutExamen::PRELEVE], true)) {
                continue;
            }

            $contenants = $examen->echantillons->reject(fn ($e) => $e->statut === StatutEchantillon::REJETE);

            $nouveau = match (true) {
                $contenants->every(fn ($e) => $e->statut === StatutEchantillon::RECU) => StatutExamen::RECU,
                $contenants->every(fn ($e) => in_array($e->statut, [StatutEchantillon::PRELEVE, StatutEchantillon::RECU], true)) => StatutExamen::PRELEVE,
                default => $examen->statut,
            };

            if ($nouveau !== $examen->statut) {
                $examen->update(['statut' => $nouveau]);
            }
        }

        $this->demandes->rafraichirStatut($echantillon->demande);
    }

    private function exigerDemandeActive(LaboDemande $demande): void
    {
        if ($demande->estAnnulee()) {
            throw new OperationLaboImpossible('Cette demande est annulée.');
        }
    }
}
