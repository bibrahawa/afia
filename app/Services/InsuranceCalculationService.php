<?php

namespace App\Services;

use App\Models\PatientInsurance;
use App\Services\Assurance\MoteurPriseEnCharge;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Point d'entrée historique du calcul de prise en charge (utilisé par
 * BillingService, FacturationLaboService, PaymentController).
 *
 * Depuis le lot 2b, le calcul est fait par Assurance\MoteurPriseEnCharge :
 * garanties par famille d'actes, plafonds par exercice décomptés au fil de
 * la facture, plafond familial, ordre des payeurs, bons de prise en charge.
 * Le format de retour est inchangé (+ 'alertes', + détails par ligne).
 */
class InsuranceCalculationService
{
    public function __construct(private MoteurPriseEnCharge $moteur)
    {
    }

    /**
     * @param Carbon|null $dateSoin  date des soins (validité des droits, exercice des plafonds) — maintenant par défaut
     * @param int|null $exclureInvoiceId facture en cours de recalcul, exclue de la consommation déjà enregistrée
     */
    public function calculateInsuranceCoverage(int $patientId, array $items, ?Carbon $dateSoin = null, ?int $exclureInvoiceId = null): array
    {
        return $this->moteur->calculer($patientId, $items, $dateSoin, $exclureInvoiceId);
    }

    public function getActivePatientInsurances(int $patientId): Collection
    {
        return PatientInsurance::query()
            ->where('patient_id', $patientId)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->with('insuranceCompany')
            ->get();
    }

    public function getAverageCoveragePercentage(array $insurancesApplied): float
    {
        if (empty($insurancesApplied)) {
            return 0;
        }

        $totalPercentage = array_sum(array_map(
            fn ($insurance) => (float) ($insurance['coverage_percentage'] ?? 0),
            $insurancesApplied
        ));

        return round($totalPercentage / count($insurancesApplied), 2);
    }
}
