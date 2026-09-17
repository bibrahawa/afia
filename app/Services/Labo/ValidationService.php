<?php

namespace App\Services\Labo;

use App\Models\User;
use App\Enums\Labo\StatutExamen;
use App\Enums\Labo\TypeResultat;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboAlerteCritique;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboResultat;
use App\Support\Labo\ContexteLabo;
use Illuminate\Support\Facades\DB;

/**
 * Phase post-analytique : double validation, valeurs critiques, réouverture
 * pour rectification. Les permissions (qui PEUT valider) sont vérifiées par
 * les contrôleurs ; ce service vérifie ce qui est médicalement VALIDABLE.
 */
class ValidationService
{
    public function __construct(private DemandeService $demandes)
    {
    }

    public function validerTechnique(LaboDemandeExamen $ligne, User $technicien): void
    {
        ContexteLabo::verifierAppartenance($ligne);

        if ($ligne->statut !== StatutExamen::EN_COURS) {
            throw new OperationLaboImpossible('Seul un examen en cours d\'analyse peut être validé techniquement.');
        }

        $manquants = $this->parametresManquants($ligne);
        if ($manquants) {
            throw new OperationLaboImpossible('Résultats manquants : ' . implode(', ', $manquants) . '.');
        }

        DB::transaction(function () use ($ligne, $technicien) {
            $ligne->update([
                'statut' => StatutExamen::VALIDE_TECHNIQUE,
                'valide_technique_par' => $technicien->id,
                'valide_technique_le' => now(),
            ]);
            $this->demandes->rafraichirStatut($ligne->demande);
        });
    }

    public function validerBiologique(LaboDemandeExamen $ligne, User $biologiste, ?string $commentaire = null): void
    {
        ContexteLabo::verifierAppartenance($ligne);

        if ($ligne->statut !== StatutExamen::VALIDE_TECHNIQUE) {
            throw new OperationLaboImpossible('La validation technique doit précéder la validation biologique.');
        }

        $nonSignales = $this->critiquesNonSignales($ligne);
        if ($nonSignales->isNotEmpty()) {
            throw new OperationLaboImpossible(
                'Valeur(s) critique(s) non signalée(s) au prescripteur : '
                . $nonSignales->pluck('libelle')->implode(', ')
                . '. Enregistrez l\'appel avant de valider.'
            );
        }

        DB::transaction(function () use ($ligne, $biologiste, $commentaire) {
            $ligne->update([
                'statut' => StatutExamen::VALIDE_BIOLOGIQUE,
                'valide_biologique_par' => $biologiste->id,
                'valide_biologique_le' => now(),
                'commentaire_biologiste' => $commentaire ?? $ligne->commentaire_biologiste,
            ]);
            $this->demandes->rafraichirStatut($ligne->demande);

            // Maladie à déclaration obligatoire : ouverte (ou close) à chaque validation.
            app(DeclarationMdoService::class)->detecter($ligne->fresh());

            ContexteLabo::journaliser('validation_biologique', $ligne);
        });
    }

    /**
     * Petit labo : le biologiste fait souvent tout lui-même. Les deux
     * validations restent tracées séparément (même auteur, même horodatage).
     */
    public function validerTechniqueEtBiologique(LaboDemandeExamen $ligne, User $biologiste, ?string $commentaire = null): void
    {
        DB::transaction(function () use ($ligne, $biologiste, $commentaire) {
            if ($ligne->statut === StatutExamen::EN_COURS) {
                $this->validerTechnique($ligne, $biologiste);
                $ligne->refresh();
            }
            $this->validerBiologique($ligne, $biologiste, $commentaire);
        });
    }

    /** Renvoie un examen validé techniquement en saisie (erreur repérée par le biologiste). */
    public function refuserTechnique(LaboDemandeExamen $ligne, string $motif): void
    {
        ContexteLabo::verifierAppartenance($ligne);

        if ($ligne->statut !== StatutExamen::VALIDE_TECHNIQUE) {
            throw new OperationLaboImpossible('Seul un examen validé techniquement peut être renvoyé en saisie.');
        }

        $ligne->update(['statut' => StatutExamen::EN_COURS, 'valide_technique_par' => null, 'valide_technique_le' => null]);
        $this->demandes->rafraichirStatut($ligne->demande);
        ContexteLabo::journaliser('renvoi_saisie', $ligne, $motif);
    }

    /**
     * Rouvre un examen validé biologiquement (voire déjà publié) pour
     * rectification. Le compte rendu déjà émis n'est PAS modifié : la
     * prochaine publication produira une version rectificative.
     */
    public function rouvrirPourRectification(LaboDemandeExamen $ligne, string $motif, User $biologiste): void
    {
        ContexteLabo::verifierAppartenance($ligne);

        if (! $ligne->statut->estVerrouille()) {
            throw new OperationLaboImpossible('Cet examen n\'est pas encore validé : corrigez-le directement.');
        }

        DB::transaction(function () use ($ligne, $motif, $biologiste) {
            $etaitPublie = $ligne->statut === StatutExamen::PUBLIE;

            // Le changement de statut de la LIGNE déverrouille ses résultats
            // (le garde de LaboResultat lit ce statut).
            $ligne->update([
                'statut' => StatutExamen::EN_COURS,
                'valide_technique_par' => null,
                'valide_technique_le' => null,
                'valide_biologique_par' => null,
                'valide_biologique_le' => null,
                'nombre_rectifications' => $etaitPublie ? $ligne->nombre_rectifications + 1 : $ligne->nombre_rectifications,
                'motif_derniere_rectification' => $motif,
            ]);
            $this->demandes->rafraichirStatut($ligne->demande);

            ContexteLabo::journaliser('reouverture_rectification', $ligne, $motif, [
                'etait_publie' => $etaitPublie,
                'biologiste' => $biologiste->id,
            ]);
        });
    }

    public function signalerCritique(LaboResultat $resultat, array $donnees, User $auteur): LaboAlerteCritique
    {
        ContexteLabo::verifierAppartenance($resultat);

        if (! $resultat->flag?->estCritique()) {
            throw new OperationLaboImpossible('Ce résultat n\'est pas une valeur critique.');
        }

        $alerte = LaboAlerteCritique::create([
            'etablissement_id' => $resultat->etablissement_id,
            'resultat_id' => $resultat->id,
            'signale_par' => $auteur->id,
            'personne_contactee' => $donnees['personne_contactee'],
            'moyen' => $donnees['moyen'],
            'commentaire' => $donnees['commentaire'] ?? null,
            'signale_le' => $donnees['signale_le'] ?? now(),
        ]);

        ContexteLabo::journaliser('valeur_critique_signalee', $resultat, $donnees['personne_contactee']);

        return $alerte;
    }

    /** @return string[] libellés des paramètres obligatoires sans valeur */
    public function parametresManquants(LaboDemandeExamen $ligne): array
    {
        $ligne->load(['examen.parametres', 'resultats', 'germesIsoles']);

        $resultats = $ligne->resultats->keyBy('parametre_id');

        if ($ligne->examen->estBacteriologie() && $resultats->filter->aUneValeur()->isEmpty() && $ligne->germesIsoles->isEmpty()) {
            return ['aucun résultat de bactériologie'];
        }

        return $ligne->examen->parametres
            ->filter(fn ($p) => $p->obligatoire && $p->type_resultat !== TypeResultat::CALCULE)
            ->reject(fn ($p) => $resultats->get($p->id)?->aUneValeur())
            ->pluck('libelle')
            ->all();
    }

    public function critiquesNonSignales(LaboDemandeExamen $ligne)
    {
        return $ligne->resultats()
            ->whereIn('flag', ['LL', 'HH'])
            // Une alerte ne couvre que la valeur saisie AVANT elle : si le
            // résultat est corrigé ensuite, il faut un nouveau signalement.
            ->whereDoesntHave('alertes', fn ($q) => $q->whereColumn('labo_alertes_critiques.signale_le', '>=', 'labo_resultats.saisi_le'))
            ->get();
    }
}
