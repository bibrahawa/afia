<?php

namespace App\Enums\Parcours;

enum StatutVisite: string
{
    case EnAttente = 'en_attente';
    case EnConsultation = 'en_consultation';
    case Terminee = 'terminee';
    case Partie = 'partie';
    case Annulee = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::EnConsultation => 'En consultation',
            self::Terminee => 'Terminée',
            self::Partie => 'Parti(e) sans consulter',
            self::Annulee => 'Annulée',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::EnConsultation => 'primary',
            self::Terminee => 'success',
            self::Partie, self::Annulee => 'secondary',
        };
    }

    public function estActive(): bool
    {
        return in_array($this, [self::EnAttente, self::EnConsultation], true);
    }
}
