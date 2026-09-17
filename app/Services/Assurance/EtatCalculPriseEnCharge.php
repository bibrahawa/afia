<?php

namespace App\Services\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Models\Assurance\ConventionFamille;
use App\Models\Assurance\PriseEnCharge;
use App\Models\InsuranceCoverage;
use App\Support\Assurance\ConventionApplicable;
use App\Support\Assurance\Couverture;
use Carbon\Carbon;

/**
 * État d'un calcul : caches (conventions, consommations, fréquences) et
 * montants / actes déjà accordés dans CETTE facture, pour décompter plafonds et
 * limites au fil des lignes.
 *
 * @internal utilisé par MoteurPriseEnCharge
 */
final class EtatCalculPriseEnCharge
{
    private array $conventions = [];
    private array $reglesFamille = [];
    private array $consommationDb = [];
    private array $utilisationsDb = [];
    private array $accordeProjection = [];
    private array $accordeFamille = [];
    private array $accordeBon = [];
    private array $actesAccordes = [];
    private array $alertes = [];

    public function __construct(
        private CouverturesApplicables $couvertures,
        private Carbon $date,
        private ?int $exclureInvoiceId,
    ) {
    }

    public function date(): Carbon
    {
        return $this->date;
    }

    /**
     * Convention applicable à l'acte chez l'organisme de cette couverture.
     * Ordre : ligne par acte (active = prix négocié ; inactive = acte exclu),
     * puis règle de la famille, sinon hors convention.
     *
     * @return array{0: ?ConventionApplicable, 1: ?string} [convention, motif si aucune]
     */
    public function convention(Couverture $couverture, array $item, FamilleActe $famille): array
    {
        $organismeId = $couverture->organisme->id;
        $cle = $organismeId . '|' . ($item['acte_type'] ?? '') . '|' . ($item['acte_id'] ?? '');

        if (! array_key_exists($cle, $this->conventions)) {
            $this->conventions[$cle] = empty($item['acte_type']) || empty($item['acte_id'])
                ? null
                : InsuranceCoverage::where('insurance_company_id', $organismeId)
                    ->pourActe((string) $item['acte_type'], (int) $item['acte_id'])
                    ->whereDate('valid_from', '<=', $this->date)
                    ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $this->date))
                    ->orderByRaw("status = 'active' DESC")
                    ->latest('id')
                    ->first();
        }

        $ligne = $this->conventions[$cle];

        if ($ligne) {
            return $ligne->status === 'active'
                ? [ConventionApplicable::depuisLigne($ligne), null]
                : [null, 'acte exclu de la convention'];
        }

        $regle = $this->regleFamille($organismeId, $famille);

        if ($regle) {
            return [ConventionApplicable::depuisFamille($regle, (float) ($item['unit_price'] ?? 0)), null];
        }

        return [null, 'acte hors convention'];
    }

    private function regleFamille(int $organismeId, FamilleActe $famille): ?ConventionFamille
    {
        $cle = $organismeId . '|' . $famille->value;

        if (! array_key_exists($cle, $this->reglesFamille)) {
            $regle = ConventionFamille::where('insurance_company_id', $organismeId)->where('famille_acte', $famille->value)->first();
            $this->reglesFamille[$cle] = $regle && $regle->enVigueurLe($this->date) ? $regle : null;
        }

        return $this->reglesFamille[$cle];
    }

    /**
     * Nombre d'unités encore couvrables par rapport à une limite de fréquence.
     * $acte = [type, id] pour une limite par acte, null pour une limite par famille.
     */
    public function unitesDisponibles(Couverture $couverture, int $max, string $periode, FamilleActe $famille, ?array $acte): int
    {
        $bornes = $couverture->bornesPeriode($periode, $this->date);
        $cle = $couverture->projection->id . '|' . $periode . '|' . ($acte ? implode('#', $acte) : 'f:' . $famille->value);

        $deja = $this->utilisationsDb[$cle] ??= $this->couvertures->utilisations(
            $couverture->projection->id,
            $bornes,
            $acte ? null : $famille->value,
            $acte,
            $this->exclureInvoiceId
        );

        return max(0, $max - $deja - ($this->actesAccordes[$cle] ?? 0));
    }

    public function compterActes(Couverture $couverture, string $periode, FamilleActe $famille, ?array $acte, int $unites): void
    {
        $cle = $couverture->projection->id . '|' . $periode . '|' . ($acte ? implode('#', $acte) : 'f:' . $famille->value);
        $this->actesAccordes[$cle] = ($this->actesAccordes[$cle] ?? 0) + $unites;
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
