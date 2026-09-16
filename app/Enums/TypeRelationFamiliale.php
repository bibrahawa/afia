<?php

namespace App\Enums;

/**
 * Une seule ligne est stockée par relation (voir migration
 * relations_familiales) — l'inverse se calcule ici plutôt que d'être
 * dupliqué en base, pour ne jamais avoir un sens désynchronisé de l'autre.
 */
enum TypeRelationFamiliale: string
{
    case Pere = 'pere';
    case Mere = 'mere';
    case Enfant = 'enfant';
    case Epoux = 'epoux';
    case Epouse = 'epouse';
    case Tuteur = 'tuteur';
    case Frere = 'frere';
    case Soeur = 'soeur';

    /**
     * Si $this décrit "personne_liee_id EST [ce type] de patient_id",
     * renvoie le type inverse : "patient_id EST [inverse] de personne_liee_id".
     */
    public function inverse(): self
    {
        return match ($this) {
            self::Pere, self::Mere => self::Enfant,
            self::Enfant => self::Pere, // affiché comme "parent" côté UI si le sexe n'est pas connu
            self::Epoux => self::Epouse,
            self::Epouse => self::Epoux,
            self::Tuteur => self::Enfant,
            self::Frere, self::Soeur => $this,
        };
    }

    /**
     * Seules ces relations impliquent une tutelle légale par défaut sur un
     * mineur (accès automatique au dossier) — toutes les autres relations
     * du graphe familial n'ouvrent AUCUN accès sans consentement explicite.
     */
    public function impliqueTutelleSiMineur(): bool
    {
        return in_array($this, [self::Pere, self::Mere, self::Tuteur], true);
    }
}
