<?php

namespace App\Enums\Labo;

enum StatutEchantillon: string
{
    case ATTENDU = 'attendu';
    case PRELEVE = 'preleve';
    case RECU = 'recu';
    case REJETE = 'rejete';

    public function libelle(): string
    {
        return match ($this) {
            self::ATTENDU => 'À prélever',
            self::PRELEVE => 'Prélevé',
            self::RECU => 'Reçu conforme',
            self::REJETE => 'Rejeté',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::ATTENDU => 'secondary',
            self::PRELEVE => 'info',
            self::RECU => 'success',
            self::REJETE => 'danger',
        };
    }

    /** Motifs de non-conformité standards (liste fermée = statistiques exploitables). */
    public static function motifsRejet(): array
    {
        return [
            'hemolyse' => 'Échantillon hémolysé',
            'coagule' => 'Échantillon coagulé',
            'volume_insuffisant' => 'Volume insuffisant',
            'mauvais_tube' => 'Tube ou contenant inadapté',
            'non_identifie' => 'Échantillon non identifié / étiquette illisible',
            'delai_depasse' => 'Délai d\'acheminement dépassé',
            'contamine' => 'Échantillon contaminé',
            'lipemique' => 'Échantillon lactescent (lipémique)',
            'autre' => 'Autre',
        ];
    }
}
