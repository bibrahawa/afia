<?php

namespace App\Enums;

/**
 * Chaque catégorie doit pouvoir être autorisée/refusée indépendamment —
 * un patient peut vouloir partager ses résultats de labo à un établissement
 * sans lui ouvrir tout son carnet de consultations, par exemple.
 */
enum PorteeAcces: string
{
    case CarnetComplet = 'carnet_complet';
    case Consultations = 'consultations';
    case Laboratoire = 'labo';
    case Antecedents = 'antecedents';
    case Hospitalisations = 'hospitalisations';
    case InfosVitalesMinimales = 'vitales_minimales'; // ce que l'accès d'urgence expose, jamais plus

    public function libelle(): string
    {
        return match ($this) {
            self::CarnetComplet => 'Carnet médical complet',
            self::Consultations => 'Historique des consultations',
            self::Laboratoire => 'Résultats de laboratoire',
            self::Antecedents => 'Antécédents et allergies',
            self::Hospitalisations => "Historique d'hospitalisation",
            self::InfosVitalesMinimales => 'Informations vitales (urgence)',
        };
    }
}
