<?php

namespace App\Enums\Assurance;

/**
 * En Guinée, l'employeur ne paie jamais les soins : il souscrit le contrat
 * (voir Entreprise), seul l'organisme et le patient se partagent la facture.
 */
enum TypeOrganismePayeur: string
{
    case Assureur = 'assureur';
    case Mutuelle = 'mutuelle';
    case Etat = 'etat';

    public function libelle(): string
    {
        return match ($this) {
            self::Assureur => 'Compagnie d\'assurance',
            self::Mutuelle => 'Mutuelle',
            self::Etat => 'Organisme public',
        };
    }
}
