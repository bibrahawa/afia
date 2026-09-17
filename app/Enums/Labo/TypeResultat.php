<?php

namespace App\Enums\Labo;

enum TypeResultat: string
{
    case NUMERIQUE = 'numerique';
    case QUALITATIF = 'qualitatif';     // liste fermée avec une valeur attendue (Négatif / Positif)
    case SEMI_QUANTITATIF = 'semi_quanti'; // Négatif, Traces, +, ++, +++
    case LISTE = 'liste';               // liste fermée sans valeur « normale » (groupe sanguin)
    case TEXTE = 'texte';               // examen microscopique, commentaire
    case CALCULE = 'calcule';           // formule à partir d'autres paramètres

    public function libelle(): string
    {
        return match ($this) {
            self::NUMERIQUE => 'Numérique',
            self::QUALITATIF => 'Qualitatif (valeur attendue)',
            self::SEMI_QUANTITATIF => 'Semi-quantitatif',
            self::LISTE => 'Liste de choix',
            self::TEXTE => 'Texte libre',
            self::CALCULE => 'Calculé (formule)',
        };
    }

    public function estNumerique(): bool
    {
        return $this === self::NUMERIQUE || $this === self::CALCULE;
    }

    public function utiliseOptions(): bool
    {
        return in_array($this, [self::QUALITATIF, self::SEMI_QUANTITATIF, self::LISTE], true);
    }
}
