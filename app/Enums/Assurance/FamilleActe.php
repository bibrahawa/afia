<?php

namespace App\Enums\Assurance;

/**
 * Familles d'actes utilisées par les garanties des contrats : un assureur
 * prend souvent en charge la consultation à 80 %, la pharmacie à 70 %,
 * l'hospitalisation sur accord préalable, etc.
 */
enum FamilleActe: string
{
    case Consultation = 'consultation';
    case Imagerie = 'imagerie';
    case Laboratoire = 'laboratoire';
    case Pharmacie = 'pharmacie';
    case Soins = 'soins';
    case Hospitalisation = 'hospitalisation';
    case Autre = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::Consultation => 'Consultations',
            self::Imagerie => 'Imagerie (échographie, radio…)',
            self::Laboratoire => 'Analyses de laboratoire',
            self::Pharmacie => 'Pharmacie / médicaments',
            self::Soins => 'Soins et actes (chirurgie, accouchement…)',
            self::Hospitalisation => 'Hospitalisation',
            self::Autre => 'Autres actes',
        };
    }
}
