<?php

namespace App\Support\Labo;

use App\Enums\Labo\FlagResultat;

/**
 * Calcul PUR du flag d'un résultat (aucune dépendance Laravel) : testable
 * isolément, et identique partout où on en a besoin (saisie, validation,
 * compte rendu, portail).
 */
class InterpretationResultat
{
    public static function numerique(?float $valeur, ?float $min, ?float $max, ?float $critiqueMin = null, ?float $critiqueMax = null): ?FlagResultat
    {
        if ($valeur === null) {
            return null;
        }

        // Les seuils critiques d'abord : une valeur de panique est aussi
        // hors norme, mais c'est l'information la plus urgente qui doit gagner.
        if ($critiqueMin !== null && $valeur < $critiqueMin) {
            return FlagResultat::CRITIQUE_BAS;
        }
        if ($critiqueMax !== null && $valeur > $critiqueMax) {
            return FlagResultat::CRITIQUE_HAUT;
        }

        // Aucune norme connue (ex. enfant alors que seules des normes adultes
        // sont configurées) : on ne prétend PAS que c'est normal.
        if ($min === null && $max === null) {
            return null;
        }

        if ($min !== null && $valeur < $min) {
            return FlagResultat::BAS;
        }
        if ($max !== null && $valeur > $max) {
            return FlagResultat::HAUT;
        }

        return FlagResultat::NORMAL;
    }

    public static function qualitatif(?string $valeur, ?string $valeurAttendue): ?FlagResultat
    {
        if ($valeur === null || trim($valeur) === '' || $valeurAttendue === null || trim($valeurAttendue) === '') {
            return null;
        }

        return self::normaliser($valeur) === self::normaliser($valeurAttendue)
            ? FlagResultat::NORMAL
            : FlagResultat::ANORMAL;
    }

    private static function normaliser(string $texte): string
    {
        $texte = mb_strtolower(trim($texte));

        return strtr($texte, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'ï' => 'i', 'ô' => 'o']);
    }
}
