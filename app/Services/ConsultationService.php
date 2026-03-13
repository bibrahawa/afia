<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Consultation;
use App\Models\InsuranceCoverage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConsultationService
{
    public function attachItems(Consultation $consultation, array $selectedItems): void
    {
        foreach ($selectedItems as $category => $items) {
            $syncValues = [];

            foreach ($items as $item) {
                if ($category === 'medicaments') {
                    $syncValues[$item['id']] = [
                        'quantity' => $item['quantity'] ?? 1
                    ];
                } else {
                    $syncValues[] = $item['id'];
                }
            }

            match ($category) {
                'medicaments' => $consultation->medicaments()->sync($syncValues),
                'services'    => $consultation->services()->sync($syncValues),
                'packages'    => $consultation->packages()->sync($syncValues),
                'examens'     => $consultation->tests()->sync($syncValues),
                default       => null,
            };
        }
    }

    public function calculateAmountAndReturnItems($consultationItem, bool $isConsultation = true): array
    {
        $items = [];
        $totalAmount = 0;
        $consultation = null;
        $transaction = null;

        if ($isConsultation) {
            $consultation = $consultationItem;
            $transaction = $consultationItem->transaction;
        } else {
            $consultation = $consultationItem->transactionable;
            $transaction = $consultationItem;
        }

        $insuranceId = $consultation->patient->activeInsurances()?->first()?->insurance_company_id;

        foreach ($consultation->services ?? [] as $service) {
            $amount = $this->getAmount("App\\Models\\Service", $service->id, $insuranceId, $service);

            $items[] = [
                'acte_type'   => 'App\\Models\\Service',
                'acte_id'     => $service->id,
                'description' => $service->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        foreach ($consultation->packages ?? [] as $package) {
            $amount = $this->getAmount("App\\Models\\Package", $package->id, $insuranceId, $package);

            $items[] = [
                'acte_type'   => 'App\\Models\\Package',
                'acte_id'     => $package->id,
                'description' => $package->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        foreach ($consultation->tests ?? [] as $test) {
            $amount = $this->getAmount("App\\Models\\Test", $test->id, $insuranceId, $test);

            $items[] = [
                'acte_type'   => 'App\\Models\\Test',
                'acte_id'     => $test->id,
                'description' => $test->name,
                'unit_price'  => $amount,
                'quantity'    => 1,
                'total'       => $amount,
            ];

            $totalAmount += $amount;
        }

        foreach ($consultation->medicaments ?? [] as $medicament) {
            $amount = $this->getAmount("App\\Models\\Medicament", $medicament->id, $insuranceId, $medicament);

            $items[] = [
                'acte_type'   => 'App\\Models\\Medicament',
                'acte_id'     => $medicament->id,
                'description' => $medicament->nom,
                'unit_price'  => $amount,
                'quantity'    => $medicament->pivot->quantity,
                'total'       => $amount * $medicament->pivot->quantity,
            ];

            $totalAmount += $amount * $medicament->pivot->quantity;
        }

        if ($transaction?->transactionable_type === "App\\Models\\Hospitalisation") {
            $hospitalisations = [$transaction->transactionable];

            foreach ($hospitalisations as $hospitalisation) {
                $items[] = [
                    'acte_type'   => 'App\\Models\\Chambre',
                    'acte_id'     => $hospitalisation->chambre_id,
                    'description' => $hospitalisation->date_entree . " au " . $hospitalisation->date_sortie_effective,
                    'unit_price'  => $this->getAmount("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                    'quantity'    => $hospitalisation->nombre_jours,
                    'total'       => $this->getAmountHospitalisation("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                ];

                $totalAmount += $hospitalisation->total_payer;
            }
        }

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    public function createNextAppointment(Consultation $consultation, string $prochainRdv): void
    {
        DB::transaction(function () use ($consultation, $prochainRdv) {
            $rdvDate = Carbon::parse($prochainRdv);

            Appointment::create([
                'employee_id'      => $consultation->medecin_id,
                'patient_id'       => $consultation->patient_id,
                'appointment_date' => $rdvDate->format('Y-m-d'),
                'appointment_time' => $rdvDate->format('H:i:s'),
                'reason'           => 'autre',
                'description'      => 'Reservation de rendez vous pris directement avec le medecin',
                'status'           => 'confirmed',
            ]);

            AppointmentSlot::where('employee_id', $consultation->medecin_id)
                ->where('date', $rdvDate->format('Y-m-d'))
                ->where('time', $rdvDate->format('H:i:s'))
                ->update(['is_available' => false]);
        });
    }

    public function updateNextAppointment(Consultation $consultation, ?string $nouveauProchainRdv): void
    {
        if (!$nouveauProchainRdv || !$consultation->prochain_rdv) {
            return;
        }

        DB::transaction(function () use ($consultation, $nouveauProchainRdv) {
            $ancienProchainRdv = $consultation->prochain_rdv;

            Appointment::where('employee_id', $consultation->medecin_id)
                ->where('patient_id', $consultation->patient_id)
                ->where('appointment_date', Carbon::parse($ancienProchainRdv)->format('Y-m-d'))
                ->where('appointment_time', Carbon::parse($ancienProchainRdv)->format('H:i:s'))
                ->update([
                    'appointment_date' => Carbon::parse($nouveauProchainRdv)->format('Y-m-d'),
                    'appointment_time' => Carbon::parse($nouveauProchainRdv)->format('H:i:s'),
                ]);

            AppointmentSlot::where('employee_id', $consultation->medecin_id)
                ->where('date', Carbon::parse($ancienProchainRdv)->format('Y-m-d'))
                ->where('time', Carbon::parse($ancienProchainRdv)->format('H:i:s'))
                ->update(['is_available' => true]);

            AppointmentSlot::where('employee_id', $consultation->medecin_id)
                ->where('date', Carbon::parse($nouveauProchainRdv)->format('Y-m-d'))
                ->where('time', Carbon::parse($nouveauProchainRdv)->format('H:i:s'))
                ->update(['is_available' => false]);
        });
    }

    public function getActes($transaction): array
    {
        $actes = [];
        $consultation = $transaction->transactionable;
        $insuranceId = $transaction->patient->activeInsurances()?->first()?->insurance_company_id;

        foreach ($consultation->services ?? [] as $item) {
            $actes[] = [
                'id' => $item->id,
                'type' => 'Service',
                'nom' => $item->name,
                'description' => $item->name,
                'prix_unitaire' => $this->getAmount("App\\Models\\Service", $item->id, $insuranceId, $item),
                'quantite' => 1,
                'created_at' => $item->created_at,
            ];
        }

        foreach ($consultation->packages ?? [] as $item) {
            $actes[] = [
                'id' => $item->id,
                'type' => 'Package',
                'nom' => $item->name,
                'description' => $item->name,
                'prix_unitaire' => $this->getAmount("App\\Models\\Package", $item->id, $insuranceId, $item),
                'quantite' => 1,
                'created_at' => $item->created_at,
            ];
        }

        foreach ($consultation->tests ?? [] as $item) {
            $actes[] = [
                'id' => $item->id,
                'type' => 'Test',
                'nom' => $item->name,
                'description' => $item->name,
                'prix_unitaire' => $this->getAmount("App\\Models\\Test", $item->id, $insuranceId, $item),
                'quantite' => 1,
                'created_at' => $item->created_at,
            ];
        }

        foreach ($consultation->medicaments ?? [] as $item) {
            $actes[] = [
                'id' => $item->id,
                'type' => 'Medicament',
                'nom' => $item->nom,
                'description' => $item->nom,
                'prix_unitaire' => $this->getAmount("App\\Models\\Medicament", $item->id, $insuranceId, $item),
                'quantite' => $item->pivot->quantity,
                'created_at' => $item->created_at,
            ];
        }

        if ($transaction->transactionable_type === 'App\Models\Hospitalisation') {

            $hospitalisations = [$transaction->transactionable];

            foreach ($hospitalisations as $hospitalisation) {
                $actes[] = [
                    'id' => $hospitalisation->chambre_id,
                    'type' => 'Hospitalisation',
                    'nom' => 'Hospitalisation',
                    'description' => $hospitalisation->date_entree . " au " . $hospitalisation->date_sortie_effective,
                    'prix_unitaire' => $this->getAmount("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                    'quantite' => $hospitalisation->nombre_jours,
                    'total' => $this->getAmountHospitalisation("App\\Models\\Chambre", $hospitalisation->chambre_id, $insuranceId, $hospitalisation),
                    'created_at' => $hospitalisation->created_at,
                ];
            }
        }

        return $actes;
    }

    public function getAmount($serviceType, $serviceId, $insuranceId, $acte): float
    {
        $item = $this->getCoverageItem($serviceType, $serviceId, $insuranceId);

        return !empty($item)
            ? (float) $item->acte_price
            : (float) ($acte?->amount ?? $acte?->price ?? $acte->chambre->prix_par_jour);
    }

    private function getAmountHospitalisation($serviceType, $serviceId, $insuranceId, $acte): float
    {
        $item = $this->getCoverageItem($serviceType, $serviceId, $insuranceId);

        return !empty($item)
            ? (float) ($item->acte_price * $acte->nombre_jours)
            : (float) $acte->total_payer;
    }

    private function getCoverageItem($serviceType, $serviceId, $insuranceId)
    {
        if (!$insuranceId) {
            return null;
        }

        return InsuranceCoverage::where('insurance_company_id', $insuranceId)
            ->where('coverageable_type', $serviceType)
            ->where('coverageable_id', $serviceId)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            })
            ->first();
    }
}