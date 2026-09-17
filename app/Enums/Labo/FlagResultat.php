<?php

namespace App\Enums\Labo;

enum FlagResultat: string
{
    case NORMAL = 'N';
    case BAS = 'L';
    case HAUT = 'H';
    case CRITIQUE_BAS = 'LL';
    case CRITIQUE_HAUT = 'HH';
    case ANORMAL = 'A';

    public function estCritique(): bool
    {
        return $this === self::CRITIQUE_BAS || $this === self::CRITIQUE_HAUT;
    }

    public function estAnormal(): bool
    {
        return $this !== self::NORMAL;
    }

    /** Symbole imprimé sur le compte rendu, à côté de la valeur. */
    public function symbole(): string
    {
        return match ($this) {
            self::NORMAL => '',
            self::BAS => '↓',
            self::HAUT => '↑',
            self::CRITIQUE_BAS => '↓↓',
            self::CRITIQUE_HAUT => '↑↑',
            self::ANORMAL => '*',
        };
    }

    public function classeCss(): string
    {
        return match ($this) {
            self::NORMAL => '',
            self::BAS, self::HAUT, self::ANORMAL => 'labo-flag-anormal',
            self::CRITIQUE_BAS, self::CRITIQUE_HAUT => 'labo-flag-critique',
        };
    }
}
