<?php

namespace App\Enums\Labo;

enum OrigineDemande: string
{
    case INTERNE = 'interne';       // prescrite par un médecin de l'établissement
    case EXTERNE = 'externe';       // ordonnance d'un médecin/établissement extérieur
    case SPONTANEE = 'spontanee';   // sans ordonnance (bilan, test de grossesse...)

    public function libelle(): string
    {
        return match ($this) {
            self::INTERNE => 'Prescription interne',
            self::EXTERNE => 'Ordonnance extérieure',
            self::SPONTANEE => 'Sans ordonnance',
        };
    }
}
