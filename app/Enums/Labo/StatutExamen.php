<?php

namespace App\Enums\Labo;

/**
 * Cycle de vie d'UN examen d'une demande. L'ordre des cas est l'ordre de
 * progression : rang() sert à calculer le statut global d'une demande
 * (l'examen le moins avancé la « tire » vers l'arrière).
 */
enum StatutExamen: string
{
    case EN_ATTENTE_PRELEVEMENT = 'en_attente_prelevement';
    case PRELEVE = 'preleve';
    case RECU = 'recu';
    case EN_COURS = 'en_cours';
    case VALIDE_TECHNIQUE = 'valide_technique';
    case VALIDE_BIOLOGIQUE = 'valide_biologique';
    case PUBLIE = 'publie';
    case ANNULE = 'annule';

    public function rang(): int
    {
        return match ($this) {
            self::EN_ATTENTE_PRELEVEMENT => 1,
            self::PRELEVE => 2,
            self::RECU => 3,
            self::EN_COURS => 4,
            self::VALIDE_TECHNIQUE => 5,
            self::VALIDE_BIOLOGIQUE => 6,
            self::PUBLIE => 7,
            self::ANNULE => 99,
        };
    }

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE_PRELEVEMENT => 'À prélever',
            self::PRELEVE => 'Prélevé',
            self::RECU => 'Reçu au labo',
            self::EN_COURS => 'En analyse',
            self::VALIDE_TECHNIQUE => 'Validé (technique)',
            self::VALIDE_BIOLOGIQUE => 'Validé (biologiste)',
            self::PUBLIE => 'Publié',
            self::ANNULE => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EN_ATTENTE_PRELEVEMENT => 'secondary',
            self::PRELEVE, self::RECU => 'info',
            self::EN_COURS => 'warning',
            self::VALIDE_TECHNIQUE => 'primary',
            self::VALIDE_BIOLOGIQUE, self::PUBLIE => 'success',
            self::ANNULE => 'dark',
        };
    }

    /** Après validation biologique, un résultat est figé (rectificatif obligatoire). */
    public function estVerrouille(): bool
    {
        return $this === self::VALIDE_BIOLOGIQUE || $this === self::PUBLIE;
    }

    /**
     * Saisie ou correction possible. CORRIGÉ : n'incluait pas « validé technique »,
     * alors que la correction y est autorisée (elle annule la validation
     * technique) — le bouton « Résultats » disparaissait de la fiche demande.
     * Source unique utilisée par ResultatService, la fiche demande et la saisie.
     */
    public function permetSaisie(): bool
    {
        return in_array($this, [self::RECU, self::EN_COURS, self::VALIDE_TECHNIQUE], true);
    }
}
