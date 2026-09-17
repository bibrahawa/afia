<?php

namespace App\Services\Labo;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboRemise;
use App\Models\User;
use App\Support\Labo\ContexteLabo;
use Illuminate\Support\Facades\DB;

/**
 * Remise d'un compte rendu en main propre, au guichet.
 *
 * Règles :
 * - on remet toujours la DERNIÈRE version publiée ;
 * - part patient non réglée + résultats retenus : remise refusée, sauf pour
 *   un utilisateur ayant « labo.facturation », avec un motif obligatoire ;
 * - la remise au prescripteur n'est jamais bloquée par le paiement ;
 * - un représentant doit être nommé, avec son lien au patient et la pièce présentée.
 */
class RemiseService
{
    public function remettre(LaboDemande $demande, array $donnees, User $agent): LaboRemise
    {
        ContexteLabo::verifierAppartenance($demande);

        return DB::transaction(function () use ($demande, $donnees, $agent) {
            $demande = LaboDemande::whereKey($demande->id)->lockForUpdate()->firstOrFail();

            $compteRendu = $demande->comptesRendus()->first(); // relation triée par version décroissante
            if (! $compteRendu) {
                throw new OperationLaboImpossible('Aucun compte rendu publié : rien à remettre.');
            }

            $beneficiaire = $donnees['beneficiaire'];
            $bloqueParPaiement = $beneficiaire !== 'prescripteur' && ! $demande->peutEtreRemisAuPatient();

            if ($bloqueParPaiement) {
                if (! $agent->can('labo.facturation')) {
                    throw new OperationLaboImpossible('Part patient non réglée : encaissez avant de remettre les résultats, ou demandez une autorisation à la facturation.');
                }
                if (blank($donnees['motif_derogation'] ?? null)) {
                    throw new OperationLaboImpossible('Remise avant règlement : indiquez le motif (urgence, prise en charge…).');
                }
            }

            if ($beneficiaire === 'representant' && (blank($donnees['lien_patient'] ?? null) || blank($donnees['piece_justificative'] ?? null))) {
                throw new OperationLaboImpossible('Remise à un représentant : lien avec le patient et pièce présentée obligatoires.');
            }

            $nom = $beneficiaire === 'patient'
                ? ($donnees['nom_beneficiaire'] ?? null) ?: $demande->patient->full_name
                : $donnees['nom_beneficiaire'];

            $remise = LaboRemise::create([
                'etablissement_id' => $demande->etablissement_id,
                'demande_id' => $demande->id,
                'compte_rendu_id' => $compteRendu->id,
                'version' => $compteRendu->version,
                'beneficiaire' => $beneficiaire,
                'nom_beneficiaire' => $nom,
                'lien_patient' => $beneficiaire === 'representant' ? $donnees['lien_patient'] : null,
                'piece_justificative' => $donnees['piece_justificative'] ?? null,
                'avant_reglement' => $bloqueParPaiement,
                'motif_derogation' => $bloqueParPaiement ? $donnees['motif_derogation'] : null,
                'remis_par' => $agent->id,
                'remis_le' => now(),
            ]);

            ContexteLabo::journaliser(
                $bloqueParPaiement ? 'remise_avant_reglement' : 'compte_rendu_remis',
                $remise,
                "Version {$compteRendu->version} remise à {$nom}",
                ['beneficiaire' => $beneficiaire, 'motif' => $remise->motif_derogation]
            );

            return $remise;
        });
    }
}
