<?php

namespace App\Services\Labo;

use App\Enums\Labo\FlagResultat;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboDeclarationMdo;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\User;
use App\Support\Labo\ContexteLabo;

/**
 * Maladies à déclaration obligatoire.
 *
 * Appelé à chaque validation biologique. Un examen dont le catalogue porte une
 * maladie (`mdo_maladie`) et dont au moins un résultat est anormal (flag autre
 * que N) ouvre une déclaration « à déclarer ». Si une rectification rend le
 * résultat normal, une déclaration non encore faite passe « sans objet » ;
 * une déclaration déjà faite n'est jamais modifiée automatiquement.
 *
 * Le logiciel ne transmet rien à l'autorité sanitaire : il rappelle la
 * déclaration et trace qui l'a faite, quand et sous quelle référence.
 */
class DeclarationMdoService
{
    /** Résultats « anormaux » qui ne sont pas des résultats positifs (test à refaire). */
    private const NON_CONCLUANT = ['invalide', 'indéterminé', 'indetermine', 'non interprétable', 'ininterprétable'];

    public function detecter(LaboDemandeExamen $ligne): ?LaboDeclarationMdo
    {
        $ligne->loadMissing(['examen', 'resultats', 'germesIsoles']);
        $maladie = $ligne->examen?->mdo_maladie;

        if (! $maladie) {
            return null;
        }

        $positif = $ligne->resultats->contains(fn ($r) => $r->flag !== null && $r->flag !== FlagResultat::NORMAL
                && ! in_array(mb_strtolower(trim((string) $r->valeur_texte)), self::NON_CONCLUANT, true))
            || $ligne->germesIsoles->isNotEmpty();

        $existante = LaboDeclarationMdo::where('demande_examen_id', $ligne->id)->first();

        if (! $positif) {
            if ($existante?->statut === LaboDeclarationMdo::A_DECLARER) {
                $existante->update(['statut' => LaboDeclarationMdo::SANS_OBJET, 'commentaire' => 'Résultat rectifié : plus de résultat positif.']);
                ContexteLabo::journaliser('mdo_sans_objet', $existante, $maladie);
            }

            return $existante;
        }

        if ($existante) {
            if ($existante->statut === LaboDeclarationMdo::SANS_OBJET) {
                $existante->update(['statut' => LaboDeclarationMdo::A_DECLARER, 'commentaire' => null]);
            }

            return $existante;
        }

        $declaration = LaboDeclarationMdo::create([
            'etablissement_id' => $ligne->etablissement_id,
            'demande_examen_id' => $ligne->id,
            'maladie' => $maladie,
            'immediate' => (bool) $ligne->examen->mdo_immediate,
            'statut' => LaboDeclarationMdo::A_DECLARER,
        ]);

        ContexteLabo::journaliser('mdo_a_declarer', $declaration, $maladie, ['immediate' => $declaration->immediate]);

        return $declaration;
    }

    public function marquerDeclaree(LaboDeclarationMdo $declaration, array $donnees, User $auteur): void
    {
        ContexteLabo::verifierAppartenance($declaration);

        if ($declaration->statut === LaboDeclarationMdo::DECLAREE) {
            throw new OperationLaboImpossible('Cette déclaration est déjà enregistrée.');
        }

        $declaration->update([
            'statut' => LaboDeclarationMdo::DECLAREE,
            'declaree_par' => $auteur->id,
            'declaree_le' => $donnees['declaree_le'] ?? now(),
            'destinataire' => $donnees['destinataire'],
            'reference' => $donnees['reference'] ?? null,
            'commentaire' => $donnees['commentaire'] ?? null,
        ]);

        ContexteLabo::journaliser('mdo_declaree', $declaration, "{$declaration->maladie} → {$donnees['destinataire']}");
    }
}
