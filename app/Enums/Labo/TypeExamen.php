<?php

namespace App\Enums\Labo;

enum TypeExamen: string
{
    case STANDARD = 'standard';
    case BACTERIOLOGIE = 'bacteriologie'; // + germes isolés et antibiogramme

    public function libelle(): string
    {
        return $this === self::STANDARD ? 'Standard' : 'Bactériologie (culture + antibiogramme)';
    }
}
