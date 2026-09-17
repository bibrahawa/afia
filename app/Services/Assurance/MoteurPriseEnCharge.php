<?php

namespace App\Services\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Models\Assurance\PriseEnCharge;
use App\Models\InsuranceCoverage;
use App\Support\Assurance\Couverture;
use App\Support\Assurance\FamillesActes;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Moteur de prise en charge (lot 2b).
 *
 * Pour chaque acte, dans cet ordre :
 *  1. TARIF — prix de la convention du premier payeur de la chaîne qui a une
 *     convention pour l'acte, sinon prix du catalogue. Fixé UNE fois : l'ancien
 *     moteur pouvait changer le prix de l'acte en passant d'une assurance à l'autre.
 *  2. CHAÎNE DES PAYEURS — contrat propre du patient, puis contrat dont il est
 *     ayant droit, puis complément employeur ; chaque payeur applique son taux
 *     au RESTE (ticket modérateur).
 *  3. Pour chaque payeur : convention pour l'acte (sinon il ne couvre pas),
 *     exclusion de la famille d'actes, taux de la famille ou de la formule,
 *     accord préalable (bon valide requis), puis bornes :
 *       plafond par acte · plafond annuel du bénéficiaire · plafond annuel
 *       familial · reste du bon de prise en charge.
 *     Les plafonds sont décomptés AU FIL DE LA FACTURE : deux actes d'une même
 *     facture ne peuvent plus dépasser ensemble un plafond que chacun respecte.
 *  4. Ce qui reste est à la charge du patient ; les raisons (plafond atteint,
 *     accord préalable manquant, acte exclu) sont rendues en clair.
 *
 * Aucun effet de bord : le moteur calcule, InvoiceService / InsuranceConsumptionService écrivent.
 */
class MoteurPriseEnCharge
{
    public function __construct(
        private CouverturesApplicables $couvertures,
        private FamillesActes $familles,
    ) {
    }

    /**
     * @param array $items lignes du BillingItemBuilderService (acte_type, acte_id, description, unit_price, quantity, total)
     * @return array format historique de calculateInsuranceCoverage + 'alertes'
     */
    public function calculer(int $patientId, array $items, ?Carbon $date = null, ?int $exclureInvoiceId = null): array
    {
        $date ??= now();
        $chaine = $this->couvertures->pour($patientId, $date);
        $etat = new EtatCalculPriseEnCharge($this->couvertures, $date, $exclureInvoiceId);

        $details = [];
        $utilisees = [];
        $totalActes = 0.0;
        $totalAssurance = 0.0;

        foreach ($items as $item) {
            $detail = $this->calculerLigne($item, $chaine, $etat);
            $details[] = $detail;

            $totalActes += $detail['item_amount'];
            $totalAssurance += $detail['insurance_amount'];

            foreach ($detail['insurances_applied'] as $part) {
                $id = $part['insurance_id'];
                $utilisees[$id] ??= [
                    'insurance_id' => $id,
                    'insurance_company_id' => $part['insurance_company_id'],
                    'insurance_company' => $part['insurance_company'],
                    'policy_number' => $part['policy_number'],
                    'total_covered' => 0.0,
                    'prises_en_charge' => [],
                ];
                $utilisees[$id]['total_covered'] = round($utilisees[$id]['total_covered'] + $part['amount_covered'], 2);

                if ($part['prise_en_charge_id']) {
                    $pec = $part['prise_en_charge_id'];
                    $utilisees[$id]['prises_en_charge'][$pec] = round(($utilisees[$id]['prises_en_charge'][$pec] ?? 0) + $part['amount_covered'], 2);
                }
            }
        }

        return [
            'total_amount' => round($totalActes, 2),
            'insurance_coverage' => round($totalAssurance, 2),
            'patient_amount' => round($totalActes - $totalAssurance, 2),
            'insurances_used' => array_values($utilisees),
            'details' => $details,
            'alertes' => $etat->alertes(),
        ];
    }

    private function calculerLigne(array $item, Collection $chaine, EtatCalculPriseEnCharge $etat): array
    {
        $quantite = max(1, (int) ($item['quantity'] ?? 1));
        $famille = $this->familles->pour($item['acte_type'] ?? null, isset($item['acte_id']) ? (int) $item['acte_id'] : null);
        $description = $item['description'] ?? '';

        // 1. Tarif fixé une fois pour toute la chaîne.
        $prixUnitaire = (float) ($item['unit_price'] ?? 0);
        $montant = (float) ($item['total'] ?? $prixUnitaire * $quantite);
        $conventionTarif = null;

        foreach ($chaine as $couverture) {
            if ($convention = $etat->convention($couverture, $item)) {
                $conventionTarif = $convention;
                $prixUnitaire = (float) $convention->acte_price;
                $montant = round($prixUnitaire * $quantite, 2);
                break;
            }
        }

        $reste = $montant;
        $appliquees = [];
        $repartition = [];

        // 2-3. Chaîne des payeurs.
        foreach ($chaine as $couverture) {
            if ($reste < 0.01) {
                break;
            }

            $convention = $etat->convention($couverture, $item);
            $base = ['payeur' => $couverture->libelle(), 'insurance_id' => $couverture->projection->id];

            if (! $convention) {
                $repartition[] = $base + ['montant' => 0, 'motif' => 'acte hors convention'];
                continue;
            }

            if ($couverture->exclu($famille)) {
                $repartition[] = $base + ['montant' => 0, 'motif' => 'famille d\'actes exclue du contrat'];
                continue;
            }

            $taux = $couverture->taux($famille);
            if ($taux <= 0) {
                continue;
            }

            $bon = null;
            if ($couverture->accordPrealableRequis($famille) || $convention->requires_preauthorization) {
                $bon = $etat->bonDisponible($couverture, $famille);

                if (! $bon) {
                    $etat->alerter("Accord préalable de {$couverture->organisme->name} requis pour « {$description} » : sans bon de prise en charge valide, l'acte est à la charge du patient.");
                    $repartition[] = $base + ['montant' => 0, 'motif' => 'accord préalable manquant'];
                    continue;
                }
            }

            $souhaite = round($reste * $taux / 100, 2);
            $bornes = [];

            if (($plafondActe = $couverture->plafondParActe($famille)) !== null) {
                $bornes['plafond par acte'] = $plafondActe * $quantite;
            }
            if ($convention->coverage_amount_limit !== null) {
                $bornes['plafond de la convention'] = (float) $convention->coverage_amount_limit;
            }
            if (($reliquat = $etat->resteBeneficiaire($couverture)) !== null) {
                $bornes['plafond annuel du bénéficiaire'] = $reliquat;
            }
            if (($reliquat = $etat->resteFamille($couverture)) !== null) {
                $bornes['plafond annuel familial'] = $reliquat;
            }
            if ($bon && ($reliquat = $etat->resteBon($bon)) !== null) {
                $bornes['montant du bon ' . $bon->numero] = $reliquat;
            }

            $accorde = max(0.0, round(min([$souhaite, ...array_values($bornes)]), 2));
            $motif = null;

            if ($accorde < $souhaite) {
                $motif = array_search(min($bornes), $bornes) . ' atteint';
                $etat->alerter("« {$description} » : {$motif} ({$couverture->organisme->name}), prise en charge limitée à " . number_format($accorde, 0, ',', ' ') . ' GNF.');
            }

            $repartition[] = $base + ['montant' => $accorde, 'taux' => $taux, 'motif' => $motif, 'bon' => $bon?->numero];

            if ($accorde <= 0) {
                continue;
            }

            $etat->consommer($couverture, $accorde, $bon);
            $reste = round($reste - $accorde, 2);

            $appliquees[] = [
                'insurance_id' => $couverture->projection->id,
                'insurance_company_id' => $couverture->organisme->id,
                'insurance_company' => $couverture->organisme->name,
                'policy_number' => $couverture->projection->policy_number,
                'coverage_percentage' => $taux,
                'amount_covered' => $accorde,
                'remaining_after' => $reste,
                'beneficiaire_id' => $couverture->beneficiaire?->id,
                'prise_en_charge_id' => $bon?->id,
            ];
        }

        return [
            'item_description' => $description,
            'famille_acte' => $famille->value,
            'unit_price' => round($prixUnitaire, 2),
            'quantity' => $quantite,
            'item_amount' => round($montant, 2),
            'insurance_amount' => round($montant - $reste, 2),
            'patient_amount' => round($reste, 2),
            'insurances_applied' => $appliquees,
            'repartition' => $repartition,
            'tarif_convention' => $conventionTarif !== null,
        ];
    }
}
