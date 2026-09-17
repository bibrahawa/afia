<?php

namespace App\Enums\Assurance;

use App\Enums\TypeRelationFamiliale;

enum LienBeneficiaire: string
{
    case Adherent = 'adherent';
    case Conjoint = 'conjoint';
    case Enfant = 'enfant';
    case Autre = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::Adherent => 'Adhérent (assuré principal)',
            self::Conjoint => 'Conjoint',
            self::Enfant => 'Enfant',
            self::Autre => 'Autre ayant droit',
        };
    }

    /** Liens qu'on peut ajouter à la main (l'adhérent est créé avec l'adhésion). */
    public static function ajoutables(): array
    {
        return [self::Conjoint, self::Enfant, self::Autre];
    }

    /**
     * « L est [type] de l'adhérent » → lien d'ayant droit proposé, ou null si
     * ce lien familial ne donne pas lieu, par usage, à une couverture.
     */
    public static function depuisRelation(TypeRelationFamiliale $type): ?self
    {
        return match ($type) {
            TypeRelationFamiliale::Epoux, TypeRelationFamiliale::Epouse => self::Conjoint,
            TypeRelationFamiliale::Enfant => self::Enfant,
            default => null,
        };
    }
}
