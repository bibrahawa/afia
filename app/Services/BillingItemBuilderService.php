<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Hospitalisation;
use App\Models\Transaction;
use App\Models\InsuranceCoverage;
use App\Enums\Labo\StatutExamen;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use InvalidArgumentException;

class BillingItemBuilderService
{
    public function buildFromTransaction(Transaction $transaction): array
    {
        return match ($transaction->transactionable_type) {
            Consultation::class => $this->buildFromConsultation($transaction->transactionable),
            Hospitalisation::class => $this->buildFromHospitalisation($transaction->transactionable),
            LaboDemande::class => $this->buildFromLaboDemande($transaction->transactionable),
            default => throw new InvalidArgumentException('Type de transaction non supporté.'),
        };
    }

    public function buildFromConsultation(Consultation $consultation): array
    {
        $consultation->loadMissing([
            'patient.activeInsurances',
            'services',
            'packages',
            'tests',
            'medicaments',
        ]);

        $insuranceId = optional($consultation->patient->activeInsurances()->first())->insurance_company_id;

        $items = [];
        $totalAmount = 0;

        foreach ($consultation->services ?? [] as $service) {
            $amount = $this->resolveAmount('App\\Models\\Service', $service->id, $insuranceId, $service);
            $items[] = [
                'acte_type' => 'App\\Models\\Service',
                'acte_id' => $service->id,
                'description' => $service->name,
                'unit_price' => $amount,
                'quantity' => 1,
                'total' => $amount,
            ];
            $totalAmount += $amount;
        }

        foreach ($consultation->packages ?? [] as $package) {
            $amount = $this->resolveAmount('App\\Models\\Package', $package->id, $insuranceId, $package);
            $items[] = [
                'acte_type' => 'App\\Models\\Package',
                'acte_id' => $package->id,
                'description' => $package->name,
                'unit_price' => $amount,
                'quantity' => 1,
                'total' => $amount,
            ];
            $totalAmount += $amount;
        }

        foreach ($consultation->tests ?? [] as $test) {
            $amount = $this->resolveAmount('App\\Models\\Test', $test->id, $insuranceId, $test);
            $items[] = [
                'acte_type' => 'App\\Models\\Test',
                'acte_id' => $test->id,
                'description' => $test->name,
                'unit_price' => $amount,
                'quantity' => 1,
                'total' => $amount,
            ];
            $totalAmount += $amount;
        }

        foreach ($consultation->medicaments ?? [] as $medicament) {
            $amount = $this->resolveAmount('App\\Models\\Medicament', $medicament->id, $insuranceId, $medicament);
            if($amount <= 0) {
                continue;
            }
            $quantity = (int) ($medicament->pivot->quantity ?? 1);

            $items[] = [
                'acte_type' => 'App\\Models\\Medicament',
                'acte_id' => $medicament->id,
                'description' => $medicament->nom,
                'unit_price' => $amount,
                'quantity' => $quantity,
                'total' => $amount * $quantity,
            ];

            $totalAmount += $amount * $quantity;
        }

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * AJOUT MODULE LABORATOIRE — mêmes conventions que buildFromConsultation :
     * prix de la convention assurance s'il existe (InsuranceCoverage sur
     * LaboExamen), sinon prix figé sur la ligne de demande au moment de
     * l'enregistrement (pas le prix actuel du catalogue).
     */
    public function buildFromLaboDemande(LaboDemande $demande): array
    {
        $demande->loadMissing(['patient.activeInsurances', 'examens']);

        $insuranceId = optional($demande->patient->activeInsurances()->first())->insurance_company_id;

        $items = [];
        $totalAmount = 0;

        foreach ($demande->examens as $ligne) {
            if ($ligne->statut === StatutExamen::ANNULE) {
                continue;
            }

            $coverage = $this->findCoverage(LaboExamen::class, $ligne->examen_id, $insuranceId);
            $amount = $coverage ? (float) $coverage->acte_price : (float) $ligne->prix_applique;

            $items[] = [
                'acte_type' => LaboExamen::class,
                'acte_id' => $ligne->examen_id,
                'description' => $ligne->examen_nom,
                'unit_price' => $amount,
                'quantity' => 1,
                'total' => $amount,
            ];
            $totalAmount += $amount;
        }

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    public function buildFromHospitalisation(Hospitalisation $hospitalisation): array
    {
        $hospitalisation->loadMissing([
            'patient.activeInsurances',
            'chambre',
        ]);

        $insuranceId = optional($hospitalisation->patient->activeInsurances()->first())->insurance_company_id;

        $unitPrice = $this->resolveAmount(
            'App\\Models\\Chambre',
            $hospitalisation->chambre_id,
            $insuranceId,
            $hospitalisation
        );

        $total = $this->resolveHospitalisationTotal(
            'App\\Models\\Chambre',
            $hospitalisation->chambre_id,
            $insuranceId,
            $hospitalisation
        );

        return [
            'items' => [[
                'acte_type' => 'App\\Models\\Chambre',
                'acte_id' => $hospitalisation->chambre_id,
                'description' => 'Hospitalisation chambre ' . $hospitalisation->chambre->numero,
                'unit_price' => $unitPrice,
                'quantity' => (int) $hospitalisation->nombre_jours,
                'total' => $total,
            ]],
            'total_amount' => $total,
        ];
    }

    private function resolveAmount(string $type, int $id, ?int $insuranceId, $acte): float
    {
        $coverage = $this->findCoverage($type, $id, $insuranceId);

        if ($coverage) {
            return (float) $coverage->acte_price;
        }

        return (float) ($acte->amount ?? $acte->price ?? $acte->chambre->prix_par_jour ?? 0);
    }

    private function resolveHospitalisationTotal(string $type, int $id, ?int $insuranceId, Hospitalisation $hospitalisation): float
    {
        $coverage = $this->findCoverage($type, $id, $insuranceId);

        if ($coverage) {
            return (float) $coverage->acte_price * (int) $hospitalisation->nombre_jours;
        }

        return (float) $hospitalisation->chambre->prix_par_jour * (int) $hospitalisation->nombre_jours;
    }

    private function findCoverage(string $type, int $id, ?int $insuranceId): ?InsuranceCoverage
    {
        if (!$insuranceId) {
            return null;
        }

        return InsuranceCoverage::where('insurance_company_id', $insuranceId)
            ->where('coverageable_type', $type)
            ->where('coverageable_id', $id)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            })
            ->first();
    }
}