<?php

namespace App\Services\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Models\Assurance\PriseEnCharge;
use App\Models\InsuranceCoverage;
use App\Support\Assurance\Couverture;
use Carbon\Carbon;

/**
 * État d'un calcul : caches (conventions, consommations) et montants déjà
 * accordés dans CETTE facture, pour décompter les plafonds au fil des lignes.
 *
 * @internal
 */
final class EtatCalculPriseEnCharge
{
    private array $conventions = [];
    private array $consommationDb = [];
    private array $accordeProjection = [];
    private array $accordeFamille = [];
    private array $accordeBon = [];
    private array $alertes = [];

    public function __construct(
        private CouverturesApplicables $couvertures,
        private Carbon $date,
        private ?int $exclureInvoiceId,
    ) {
    }

    public function convention(Couverture $couverture, array $item): ?InsuranceCoverage
    {
        $cle = $couverture->organisme->id . '|' . ($item['acte_type'] ?? '') . '|' . ($item['acte_id'] ?? '');

        if (! array_key_exists($cle, $this->conventions)) {
            $this->conventions[$cle] = empty($item['acte_type']) || empty($item['acte_id'])
                ? null
                : InsuranceCoverage::where('insurance_company_id', $couverture->organisme->id)
                    ->pourActe((string) $item['acte_type'], (int) $item['acte_id'])
                    ->where('status', 'active')
                    ->whereDate('valid_from', '<=', $this->date)
                    ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $this->date))
                    ->first();
        }

        return $this->conventions[$cle];
    }

    public function resteBeneficiaire(Couverture $couverture): ?float
    {
        $plafond = $couverture->plafondBeneficiaire();
        if ($plafond === null) {
            return null;
        }

        $id = $couverture->projection->id;
        $consomme = $this->consommationDb['p' . $id] ??= $this->couvertures->consomme([$id], $couverture->exercice($this->date), $this->exclureInvoiceId);

        return max(0.0, round($plafond - $consomme - ($this->accordeProjection[$id] ?? 0), 2));
    }

    public function resteFamille(Couverture $couverture): ?float
    {
        $plafond = $couverture->plafondFamille();
        $cle = $couverture->cleFamille();

        if ($plafond === null || $cle === null) {
            return null;
        }

        $consomme = $this->consommationDb[$cle] ??= $this->couvertures->consomme(
            $this->couvertures->projectionsFamille($couverture),
            $couverture->exercice($this->date),
            $this->exclureInvoiceId
        );

        return max(0.0, round($plafond - $consomme - ($this->accordeFamille[$cle] ?? 0), 2));
    }

    public function bonDisponible(Couverture $couverture, FamilleActe $famille): ?PriseEnCharge
    {
        if (! $couverture->beneficiaire) {
            return null;
        }

        return PriseEnCharge::where('beneficiaire_id', $couverture->beneficiaire->id)
            ->validesLe($this->date)
            ->orderBy('date_fin')
            ->get()
            ->first(fn (PriseEnCharge $bon) => $bon->couvre($famille) && ($this->resteBon($bon) === null || $this->resteBon($bon) > 0));
    }

    public function resteBon(PriseEnCharge $bon): ?float
    {
        $reste = $bon->resteDisponible($this->exclureInvoiceId);

        return $reste === null ? null : max(0.0, round($reste - ($this->accordeBon[$bon->id] ?? 0), 2));
    }

    public function consommer(Couverture $couverture, float $montant, ?PriseEnCharge $bon): void
    {
        $id = $couverture->projection->id;
        $this->accordeProjection[$id] = ($this->accordeProjection[$id] ?? 0) + $montant;

        if ($cle = $couverture->cleFamille()) {
            $this->accordeFamille[$cle] = ($this->accordeFamille[$cle] ?? 0) + $montant;
        }

        if ($bon) {
            $this->accordeBon[$bon->id] = ($this->accordeBon[$bon->id] ?? 0) + $montant;
        }
    }

    public function alerter(string $message): void
    {
        $this->alertes[$message] = true;
    }

    public function alertes(): array
    {
        return array_keys($this->alertes);
    }
}
