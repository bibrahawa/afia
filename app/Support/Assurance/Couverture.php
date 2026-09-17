<?php

namespace App\Support\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Enums\Assurance\LienBeneficiaire;
use App\Models\Assurance\Beneficiaire;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Formule;
use App\Models\Assurance\FormuleGarantie;
use App\Models\InsuranceCompany;
use App\Models\PatientInsurance;
use Carbon\Carbon;

/**
 * Une couverture applicable à un patient à une date donnée : règles du
 * référentiel (formule, garanties) quand la ligne est reliée à un
 * bénéficiaire, sinon règles simples de l'ancienne ligne patient_insurances.
 */
final class Couverture
{
    public readonly ?Beneficiaire $beneficiaire;
    public readonly ?Formule $formule;
    public readonly ?Contrat $contrat;
    public readonly InsuranceCompany $organisme;

    public function __construct(public readonly PatientInsurance $projection)
    {
        $this->beneficiaire = $projection->beneficiaire;
        $this->formule = $this->beneficiaire?->adhesion?->formule;
        $this->contrat = $this->formule?->contrat;
        $this->organisme = $projection->insuranceCompany;
    }

    public function garantie(FamilleActe $famille): ?FormuleGarantie
    {
        return $this->formule?->garantiePour($famille);
    }

    public function taux(FamilleActe $famille): float
    {
        $garantie = $this->garantie($famille);

        if ($garantie && $garantie->taux !== null) {
            return (float) $garantie->taux;
        }

        return (float) ($this->formule?->taux_prise_en_charge ?? $this->projection->coverage_percentage);
    }

    public function exclu(FamilleActe $famille): bool
    {
        return (bool) $this->garantie($famille)?->exclu;
    }

    public function accordPrealableRequis(FamilleActe $famille): bool
    {
        return (bool) $this->garantie($famille)?->accord_prealable;
    }

    /**
     * Fin du délai de carence propre à une famille (ex. maternité), comptée à
     * partir du début de couverture du bénéficiaire ; null s'il n'y en a pas.
     */
    public function finCarence(FamilleActe $famille): ?Carbon
    {
        $jours = $this->garantie($famille)?->delai_carence_jours;

        if (! $jours) {
            return null;
        }

        $debut = collect([
            $this->beneficiaire?->adhesion?->date_debut,
            $this->beneficiaire?->date_debut,
            $this->beneficiaire ? null : $this->projection->start_date,
        ])->filter()->max();

        return $debut?->copy()->addDays($jours);
    }

    /** Limite de fréquence de la garantie : [nombre, période] ou null. */
    public function limiteFrequence(FamilleActe $famille): ?array
    {
        $garantie = $this->garantie($famille);

        return $garantie?->nombre_max && $garantie->periode ? [(int) $garantie->nombre_max, $garantie->periode] : null;
    }

    /** Bornes d'une période de limite : mois ou trimestre civil, année = exercice du contrat. */
    public function bornesPeriode(string $periode, Carbon $date): array
    {
        return match ($periode) {
            'mois' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            'trimestre' => [$date->copy()->startOfQuarter(), $date->copy()->endOfQuarter()],
            default => $this->exercice($date),
        };
    }

    /** Plafond de prise en charge pour UNE unité de l'acte (null = aucun). */
    public function plafondParActe(FamilleActe $famille): ?float
    {
        $plafond = $this->garantie($famille)?->plafond_par_acte;

        return $plafond !== null ? (float) $plafond : null;
    }

    public function plafondBeneficiaire(): ?float
    {
        $plafond = $this->formule ? $this->formule->plafond_annuel_beneficiaire : $this->projection->annual_limit;

        return $plafond !== null ? (float) $plafond : null;
    }

    public function plafondFamille(): ?float
    {
        return $this->formule?->plafond_annuel_famille !== null ? (float) $this->formule->plafond_annuel_famille : null;
    }

    /**
     * Exercice (période annuelle du contrat) contenant la date : les plafonds
     * repartent à zéro à chaque date anniversaire du contrat, pas au 1er janvier.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function exercice(Carbon $date): array
    {
        $origine = ($this->contrat?->date_debut ?? $this->projection->start_date ?? $date)->copy()->startOfDay();
        $annees = max(0, (int) floor($origine->diffInYears($date->copy()->startOfDay(), false)));
        $debut = $origine->copy()->addYearsNoOverflow($annees);

        if ($debut->gt($date)) {
            $debut->subYearNoOverflow();
        }

        return [$debut, $debut->copy()->addYearNoOverflow()->subDay()->endOfDay()];
    }

    /** Clé du groupe familial dont le plafond est partagé. */
    public function cleFamille(): ?string
    {
        return $this->beneficiaire ? 'adhesion:' . $this->beneficiaire->adhesion_id : null;
    }

    public function estAyantDroit(): bool
    {
        return $this->beneficiaire !== null && $this->beneficiaire->lien !== LienBeneficiaire::Adherent;
    }

    /**
     * Ordre d'application en cas de double assurance : contrat propre du
     * patient d'abord, puis contrat dont il est ayant droit ; à égalité, le
     * plus ancien.
     */
    public function rang(): array
    {
        return [
            $this->estAyantDroit() ? 1 : 0,
            optional($this->beneficiaire?->adhesion?->date_debut ?? $this->projection->start_date)->timestamp ?? 0,
            $this->projection->id,
        ];
    }

    public function libelle(): string
    {
        $lien = $this->estAyantDroit() ? ' (' . mb_strtolower($this->beneficiaire->lien->libelle()) . ')' : '';

        return $this->organisme->name . $lien;
    }
}
