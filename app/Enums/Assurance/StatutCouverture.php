<?php

namespace App\Enums\Assurance;

enum StatutCouverture: string
{
    case Active = 'active';
    case Suspendue = 'suspendue';
    case Resiliee = 'resiliee';

    public function libelle(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspendue => 'Suspendue',
            self::Resiliee => 'Résiliée',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspendue => 'warning',
            self::Resiliee => 'secondary',
        };
    }
}
