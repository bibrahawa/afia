<?php

namespace App\Enums\Labo;

enum StatutDemande: string
{
    case ENREGISTREE = 'enregistree';
    case EN_PRELEVEMENT = 'en_prelevement';
    case EN_ANALYSE = 'en_analyse';
    case A_VALIDER = 'a_valider';
    case VALIDEE = 'validee';
    case PARTIELLEMENT_PUBLIEE = 'partiellement_publiee';
    case PUBLIEE = 'publiee';
    case ANNULEE = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::ENREGISTREE => 'Enregistrée',
            self::EN_PRELEVEMENT => 'Prélèvement en cours',
            self::EN_ANALYSE => 'En analyse',
            self::A_VALIDER => 'À valider',
            self::VALIDEE => 'Validée — à publier',
            self::PARTIELLEMENT_PUBLIEE => 'Partiellement publiée',
            self::PUBLIEE => 'Publiée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::ENREGISTREE => 'secondary',
            self::EN_PRELEVEMENT => 'info',
            self::EN_ANALYSE => 'warning',
            self::A_VALIDER => 'primary',
            self::VALIDEE, self::PARTIELLEMENT_PUBLIEE => 'success',
            self::PUBLIEE => 'success',
            self::ANNULEE => 'dark',
        };
    }

    /**
     * Statut global dérivé des statuts de ses examens — jamais saisi à la main.
     *
     * @param  StatutExamen[]  $statuts
     */
    public static function deduire(array $statuts): self
    {
        $actifs = array_values(array_filter($statuts, fn (StatutExamen $s) => $s !== StatutExamen::ANNULE));

        if (! $actifs) {
            return self::ANNULEE;
        }

        $publies = count(array_filter($actifs, fn (StatutExamen $s) => $s === StatutExamen::PUBLIE));
        if ($publies === count($actifs)) {
            return self::PUBLIEE;
        }
        if ($publies > 0) {
            return self::PARTIELLEMENT_PUBLIEE;
        }

        $moinsAvance = min(array_map(fn (StatutExamen $s) => $s->rang(), $actifs));

        return match (true) {
            $moinsAvance >= StatutExamen::VALIDE_BIOLOGIQUE->rang() => self::VALIDEE,
            $moinsAvance >= StatutExamen::VALIDE_TECHNIQUE->rang() => self::A_VALIDER,
            $moinsAvance >= StatutExamen::RECU->rang() => self::EN_ANALYSE,
            $moinsAvance >= StatutExamen::PRELEVE->rang() => self::EN_PRELEVEMENT,
            default => self::ENREGISTREE,
        };
    }
}
