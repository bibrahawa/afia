<?php

namespace App\Support\Assurance;

use App\Models\Assurance\ConventionFamille;
use App\Models\InsuranceCoverage;

/**
 * Convention qui s'applique à un acte pour un organisme, qu'elle vienne d'une
 * ligne par acte (prioritaire) ou de la règle de sa famille.
 */
final class ConventionApplicable
{
    private function __construct(
        public readonly float $prixUnitaire,
        public readonly bool $accordPrealable,
        public readonly ?float $plafondLigne,
        public readonly ?float $plafondParActe,
        public readonly ?int $nombreMax,
        public readonly ?string $periode,
        public readonly string $origine,
    ) {
    }

    public static function depuisLigne(InsuranceCoverage $ligne): self
    {
        return new self(
            prixUnitaire: (float) $ligne->acte_price,
            accordPrealable: (bool) $ligne->requires_preauthorization,
            plafondLigne: $ligne->coverage_amount_limit !== null ? (float) $ligne->coverage_amount_limit : null,
            plafondParActe: null,
            nombreMax: $ligne->max_usage_count ? (int) $ligne->max_usage_count : null,
            periode: self::periode($ligne->usage_period),
            origine: 'acte',
        );
    }

    public static function depuisFamille(ConventionFamille $regle, float $prixCatalogue): self
    {
        return new self(
            prixUnitaire: round($prixCatalogue * (100 - (float) $regle->remise_pourcentage) / 100),
            accordPrealable: (bool) $regle->accord_prealable,
            plafondLigne: null,
            plafondParActe: $regle->plafond_par_acte !== null ? (float) $regle->plafond_par_acte : null,
            nombreMax: null,
            periode: null,
            origine: 'famille',
        );
    }

    /** Anciennes valeurs de insurance_coverages.usage_period (Mois / Trimestre / Annees). */
    public static function periode(?string $valeur): ?string
    {
        return match (mb_strtolower((string) $valeur)) {
            'mois' => 'mois',
            'trimestre' => 'trimestre',
            'annee', 'annees', 'année', 'années' => 'annee',
            default => null,
        };
    }
}
