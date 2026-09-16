<?php

namespace App\Services;

use App\Enums\PorteeAcces;
use App\Enums\TypeRelationFamiliale;
use App\Models\Consentement;
use App\Models\DemandeAcces;
use App\Models\Etablissement;
use App\Models\Patient;
use App\Models\User;
use App\Support\EtablissementContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * TOUTE lecture de données de santé au-delà de ce qu'un établissement a
 * lui-même produit doit passer par ce service. Ne JAMAIS réimplémenter une
 * vérification de consentement ad hoc dans un contrôleur — c'est exactement
 * le genre de duplication qui finit par oublier un cas et créer une fuite.
 */
class AccesDossierSanteService
{
    public function __construct(protected \App\Services\SmsService $sms, protected \App\Services\OtpService $otp)
    {
    }

    /**
     * Niveau 1 (données produites par l'établissement courant lui-même)
     * n'est PAS géré ici — c'est le scope normal des modèles opérationnels
     * (BelongsToEtablissement). Cette méthode couvre le Niveau 2 : accès à
     * l'historique produit par d'AUTRES établissements.
     */
    public function peutVoir(User $utilisateur, Patient $patient, PorteeAcces $portee): bool
    {
        // Tutelle légale d'un mineur : accès par défaut, sans consentement
        // explicite à re-demander à chaque fois.
        // La tutelle légale n'est jamais vérifiée ici : voir estTuteurLegal()
        // ci-dessous pour l'explication — elle ne s'applique pas à ce
        // contexte (accès du PERSONNEL), la vraie vérification vit dans
        // PortailPatientController::autoriserAcces().

        return Consentement::where('patient_id', $patient->id)
            ->where(function ($q) use ($utilisateur) {
                $q->where(fn ($q2) => $q2->where('beneficiaire_type', User::class)->where('beneficiaire_id', $utilisateur->id))
                  ->orWhere(fn ($q2) => $q2->where('beneficiaire_type', Etablissement::class)->where('beneficiaire_id', $utilisateur->etablissement_id));
            })
            ->where('statut', 'actif')
            ->get()
            ->contains(fn (Consentement $c) => $c->couvre($portee));
    }

    /**
     * SUPPRIMÉE (voir peutVoir() ci-dessus) — cette méthode vérifiait la
     * tutelle légale pour un `User` (personnel), ce qui n'a pas de sens :
     * un membre du personnel n'est jamais le tuteur d'un patient, seul un
     * autre PATIENT (via son ComptePatient) peut l'être. La vraie règle de
     * tutelle est appliquée dans PortailPatientController::autoriserAcces(),
     * qui vérifie Patient::estMineur() avant d'accorder un accès portail
     * automatique via le rôle 'tuteur' de compte_patient.
     */

    /**
     * Crée une demande d'accès et déclenche l'envoi du code de confirmation
     * au patient (SMS par défaut — voir Nimba SMS déjà utilisé ailleurs
     * dans la plateforme).
     */
    public function demanderAcces(
        Patient $patient,
        User|Etablissement $demandeur,
        array $portees,
        ?string $motif = null
    ): DemandeAcces {
        $codeEnClair = $this->otp->generer();

        $demande = DemandeAcces::create([
            'patient_id' => $patient->id,
            'demandeur_type' => get_class($demandeur),
            'demandeur_id' => $demandeur->id,
            'etablissement_id' => EtablissementContext::id(),
            'portee_demandee' => array_map(fn (PorteeAcces $p) => $p->value, $portees),
            'canal_confirmation' => 'sms',
            'code_confirmation' => $this->otp->hacher($codeEnClair), // haché en base, jamais en clair — voir OtpService
            'motif' => $motif,
            'statut' => 'en_attente',
            'expire_le' => now()->addMinutes(30),
        ]);

        // Attribut non persisté : uniquement pour que CETTE réponse HTTP
        // (création de la demande) puisse encore afficher le code de
        // secours au staff. Il redevient inaccessible dès la requête
        // suivante — la base ne contient que le hash.
        $demande->codeEnClair = $codeEnClair;

        $this->envoyerNotificationDemande($demande, $patient, $demandeur, $codeEnClair);

        return $demande;
    }

    protected function envoyerNotificationDemande(DemandeAcces $demande, Patient $patient, User|Etablissement $demandeur, string $codeEnClair): void
    {
        // Ta table `patients` actuelle n'a pas de colonne téléphone propre au
        // patient (seulement `relative_phone`, celui d'un proche) — le
        // numéro fiable est celui du ComptePatient une fois l'auth portail
        // en place. Tant que ce n'est pas généralisé, on retombe sur le
        // contact d'un proche à défaut, mais c'est une solution de repli,
        // pas la cible : il faudra collecter le téléphone du patient
        // lui-même à l'accueil pour que ce flux fonctionne pour de vrai.
        $telephone = $patient->comptesPatients()->first()?->telephone
            ?: $patient->relative_phone
            ?: null;

        if (! $telephone) {
            return; // pas de canal connu — la demande reste visible en "en_attente" pour un suivi manuel
        }

        $lien = URL::temporarySignedRoute(
            'consentement.confirmer',
            now()->addMinutes(30),
            ['demande' => $demande->id]
        );

        $nomDemandeur = $demandeur instanceof Etablissement ? $demandeur->nom : ($demandeur->name ?? 'Un médecin');

        $this->sms->sendSms(
            $telephone,
            "{$nomDemandeur} demande à consulter votre dossier medical. "
            . "Confirmez ici : {$lien} ou donnez ce code a l'accueil : {$codeEnClair}"
        );
    }

    /**
     * Le patient confirme (via SMS entrant ou dans l'app) → transforme la
     * demande en consentement actif. Durée par défaut volontairement
     * limitée : jamais d'autorisation illimitée accordée automatiquement.
     */
    public function confirmerDemande(DemandeAcces $demande, int $dureeJours = 90): Consentement
    {
        return DB::transaction(function () use ($demande, $dureeJours) {
            $demande->update(['statut' => 'acceptee']);

            return Consentement::create([
                'patient_id' => $demande->patient_id,
                'beneficiaire_type' => $demande->demandeur_type,
                'beneficiaire_id' => $demande->demandeur_id,
                'portee' => $demande->portee_demandee,
                'statut' => 'actif',
                'accorde_le' => now(),
                'expire_le' => now()->addDays($dureeJours),
                'demande_acces_id' => $demande->id,
            ]);
        });
    }

    public function revoquer(Consentement $consentement): void
    {
        $consentement->update(['statut' => 'revoque', 'revoque_le' => now()]);
    }

    /**
     * Accès "bris de glace". Ne retourne QUE les informations vitales
     * minimales (jamais le carnet complet), et journalise systématiquement
     * avec un motif obligatoire — cet accès doit rester rare et traçable,
     * pas devenir une porte dérobée pratique pour éviter le circuit normal.
     */
    public function accesUrgence(User $utilisateur, Patient $patient, string $motif): array
    {
        \App\Models\ActivityLog::create([
            'etablissement_id' => EtablissementContext::id(),
            'causer_type' => User::class,
            'causer_id' => $utilisateur->id,
            'subject_type' => Patient::class,
            'subject_id' => $patient->id,
            'action' => 'acces_urgence_sante',
            'description' => $motif,
        ]);

        return $patient->antecedant ? [
            'groupe_sanguin' => $patient->blood_group,
            'allergies' => $patient->antecedant->allergies,
            'antecedents_medicaux' => $patient->antecedant->antecedents_medicaux,
            'traitements_en_cours' => $patient->antecedant->traitements_cours,
        ] : [
            'groupe_sanguin' => $patient->blood_group,
        ];
    }
}
