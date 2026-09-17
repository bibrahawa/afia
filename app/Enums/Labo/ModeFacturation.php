<?php

namespace App\Enums\Labo;

enum ModeFacturation: string
{
    case LABO = 'labo';                 // facturée par le labo (transaction propre à la demande)
    case CONSULTATION = 'consultation'; // déjà facturée via la consultation d'origine — NE PAS refacturer
    case GRATUIT = 'gratuit';

    public function libelle(): string
    {
        return match ($this) {
            self::LABO => 'Facturée au laboratoire',
            self::CONSULTATION => 'Incluse dans la consultation',
            self::GRATUIT => 'Gratuit',
        };
    }
}
