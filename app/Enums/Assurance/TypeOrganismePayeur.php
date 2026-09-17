<?php

namespace App\Enums\Assurance;

enum TypeOrganismePayeur: string
{
    case Assureur = 'assureur';
    case Mutuelle = 'mutuelle';
    case Entreprise = 'entreprise';
    case Etat = 'etat';

    public function libelle(): string
    {
        return match ($this) {
            self::Assureur => 'Compagnie d\'assurance',
            self::Mutuelle => 'Mutuelle',
            self::Entreprise => 'Entreprise (convention directe)',
            self::Etat => 'Organisme public',
        };
    }
}
