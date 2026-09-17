<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PatientInsurance;
use App\Models\Transaction;
use App\Support\Facturation\TypesFacturables;

class InvoiceService
{
    public function __construct(
        private InsuranceCalculationService $insuranceCalculationService,
        private PatientAccountService $patientAccountService,
    ) {
    }

    /**
     * Crée ou régénère la facture d'une pièce à partir du calcul de prise en charge.
     *
     * RÉVISION lot 1 :
     *  - les remises déjà accordées sont CONSERVÉES lors d'une régénération
     *    (avant, un recalcul effaçait les remises des lignes mais laissait la
     *    remise sur la transaction : totaux incohérents) ;
     *  - les totaux de la facture et de la transaction sont recalculés à partir
     *    des lignes (synchroniserTotaux), et le compte patient suit l'écart.
     */
    public function createOrUpdateInvoice(
        Transaction $transaction,
        array $calculation,
        array $items
    ): Invoice {
        $invoice = $transaction->invoice ?: new Invoice([
            'transaction_id' => $transaction->id
        ]);

        $remisesExistantes = $invoice->exists ? $this->remisesParLigne($invoice) : [];

        $firstInsurance = PatientInsurance::where('patient_id', $transaction->patient_id)
            ->where('status', 'active')
            ->with('insuranceCompany')
            ->first();

        $invoice->fill([
            'insurance_company_id' => $firstInsurance?->insurance_company_id,
            'patient_insurance_id' => $firstInsurance?->id,
            'total_amount' => $calculation['total_amount'],
            'patient_amount' => $calculation['patient_amount'],
            'insurance_amount' => $calculation['insurance_coverage'],
            'insurance_status' => $calculation['insurance_coverage'] > 0 ? 'pending' : null,
            'patient_amount_status' => 'pending',
        ]);

        $invoice->save();

        InvoiceItem::where('invoice_id', $invoice->id)->delete();

        $details = $calculation['details'] ?: $items;

        foreach ($details as $index => $detail) {
            $source = array_merge($items[$index] ?? [], $detail);

            $ligne = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'coverage_type_type' => $source['acte_type'] ?? null,
                'coverage_type_id' => $source['acte_id'] ?? null,
                'description' => $source['item_description'] ?? $source['description'] ?? '',
                'unit_price' => (float) ($source['unit_price'] ?? 0),
                'quantity' => (int) ($source['quantity'] ?? 1),
                'total_amount' => (float) ($source['item_amount'] ?? $source['total'] ?? 0),
                'discount' => 0,
                'insurance_covered_amount' => (float) ($source['insurance_amount'] ?? 0),
                'patient_amount' => empty($source['insurances_applied'])
                    ? (float) ($source['item_amount'] ?? $source['total'])
                    : (float) ($source['patient_amount']),
                'coverage_percentage_applied' => !empty($source['insurances_applied'])
                    ? $this->insuranceCalculationService->getAverageCoveragePercentage($source['insurances_applied'])
                    : 0,
            ]);

            $cle = $this->cleLigne($ligne->coverage_type_type, $ligne->coverage_type_id);

            if (isset($remisesExistantes[$cle])) {
                // Plafonnée à la nouvelle part patient si celle-ci a baissé.
                $ligne->appliquerRemise($remisesExistantes[$cle]);
                unset($remisesExistantes[$cle]);
            }
        }

        $this->synchroniserTotaux($invoice, $transaction);

        return $invoice->fresh(['items']);
    }

    /**
     * Totaux de la facture et de la transaction recalculés à partir des lignes.
     * Le compte du patient est ajusté de l'écart de total (atomique).
     */
    public function synchroniserTotaux(Invoice $invoice, Transaction $transaction): void
    {
        $totaux = InvoiceItem::withoutGlobalScope('etablissement')
            ->where('invoice_id', $invoice->id)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total, COALESCE(SUM(discount), 0) as remise,
                         COALESCE(SUM(insurance_covered_amount), 0) as assurance, COALESCE(SUM(patient_amount), 0) as patient')
            ->first();

        $total = round((float) $totaux->total, 2);
        $remise = round((float) $totaux->remise, 2);

        $invoice->forceFill([
            'total_amount' => $total,
            'insurance_amount' => round((float) $totaux->assurance, 2),
            'patient_amount' => round((float) $totaux->patient, 2),
        ])->save();

        // Lu en base : l'instance reçue peut être périmée.
        $ancienTotal = (float) Transaction::withoutGlobalScope('etablissement')->whereKey($transaction->id)->value('total');

        $transaction->forceFill([
            'sub_total' => round($total + $remise, 2),
            'discount' => $remise,
            'total' => $total,
        ])->save();

        $this->patientAccountService->ajusterPourTransaction($transaction, round($total - $ancienTotal, 2));
    }

    /** @return array<string, float> remise par ligne, clé « alias|id » */
    private function remisesParLigne(Invoice $invoice): array
    {
        return InvoiceItem::withoutGlobalScope('etablissement')
            ->where('invoice_id', $invoice->id)
            ->where('discount', '>', 0)
            ->get()
            ->mapWithKeys(fn (InvoiceItem $l) => [$this->cleLigne($l->coverage_type_type, $l->coverage_type_id) => (float) $l->discount])
            ->all();
    }

    private function cleLigne(?string $type, $id): string
    {
        return TypesFacturables::alias((string) $type) . '|' . (int) $id;
    }
}
