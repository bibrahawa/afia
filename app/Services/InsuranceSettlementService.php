<?php

namespace App\Services;

use App\Models\InsuranceCompany;
use App\Models\InsuranceSettlement;
use App\Models\InsuranceSettlementItem;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InsuranceSettlementService
{
    public function __construct(
        private TransactionStatusService $transactionStatusService
    ) {
    }

    public function settle(
        InsuranceCompany $company,
        float $paidAmount,
        float $discountAmount = 0,
        ?string $paymentMethod = null,
        ?string $paymentReference = null,
        ?string $paymentDate = null,
        ?string $notes = null,
        ?string $periodStart = null,
        ?string $periodEnd = null
    ): InsuranceSettlement {
        return DB::transaction(function () use (
            $company,
            $paidAmount,
            $discountAmount,
            $paymentMethod,
            $paymentReference,
            $paymentDate,
            $notes,
            $periodStart,
            $periodEnd
        ) {
            if ($paidAmount < 0 || $discountAmount < 0) {
                throw new InvalidArgumentException('Les montants doivent être positifs.');
            }

            if (($paidAmount + $discountAmount) <= 0) {
                throw new InvalidArgumentException('Le paiement ou la remise doit être supérieur à zéro.');
            }

            $openInvoices = $this->getOpenInvoices($company, $periodStart, $periodEnd);

            if ($openInvoices->isEmpty()) {
                throw new InvalidArgumentException('Aucune facture assurance ouverte à régler.');
            }

            $grossAmount = (float) $openInvoices->sum('remaining_due');
            $netSettlementAmount = $paidAmount + $discountAmount;

            if ($netSettlementAmount > $grossAmount) {
                throw new InvalidArgumentException("Le total paiement + remise dépasse le montant dû.");
            }

            $settlement = InsuranceSettlement::create([
                'insurance_company_id' => $company->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'gross_amount' => $grossAmount,
                'discount_amount' => $discountAmount,
                'paid_amount' => $paidAmount,
                'net_amount' => $netSettlementAmount,
                'remaining_amount' => $grossAmount - $netSettlementAmount,
                'payment_method' => $paymentMethod ? strtoupper($paymentMethod) : null,
                'payment_reference' => $paymentReference,
                'payment_date' => $paymentDate,
                'status' => ($grossAmount - $netSettlementAmount) > 0 ? 'partial' : 'paid',
                'notes' => $notes,
            ]);

            $remainingPaid = $paidAmount;
            $remainingDiscount = $discountAmount;

            foreach ($openInvoices as $invoice) {
                if (($remainingPaid + $remainingDiscount) <= 0) {
                    break;
                }

                $remainingBefore = (float) $invoice->remaining_due;
                if ($remainingBefore <= 0) {
                    continue;
                }

                $appliedDiscount = min($remainingDiscount, $remainingBefore);
                $remainingDiscount -= $appliedDiscount;

                $afterDiscountRemaining = $remainingBefore - $appliedDiscount;

                $appliedPaid = min($remainingPaid, $afterDiscountRemaining);
                $remainingPaid -= $appliedPaid;

                $settledAmount = $appliedDiscount + $appliedPaid;
                $remainingAfter = $remainingBefore - $settledAmount;

                InsuranceSettlementItem::create([
                    'insurance_settlement_id' => $settlement->id,
                    'invoice_id' => $invoice->id,
                    'invoice_amount' => (float) $invoice->insurance_amount,
                    'already_settled_amount' => (float) $invoice->already_settled_amount,
                    'remaining_before' => $remainingBefore,
                    'applied_discount_amount' => $appliedDiscount,
                    'applied_paid_amount' => $appliedPaid,
                    'settled_amount' => $settledAmount,
                    'remaining_after' => $remainingAfter,
                ]);

                $invoice = $invoice->fresh(['transaction.invoice', 'transaction.paiements']);
                $this->refreshInvoiceInsuranceStatus($invoice);

                if ($invoice->transaction) {
                    $this->transactionStatusService->refresh(
                        $invoice->transaction->fresh(['invoice', 'paiements'])
                    );
                }
            }

            return $settlement->fresh(['items.invoice.transaction.patient', 'insuranceCompany']);
        });
    }

    public function getOpenInvoices(
        InsuranceCompany $company,
        ?string $periodStart = null,
        ?string $periodEnd = null
    ): Collection {
        $query = Invoice::query()
            ->where('insurance_company_id', $company->id)
            ->whereIn('insurance_status', ['pending', 'approved', 'partial'])
            ->where('insurance_amount', '>', 0)
            ->where('patient_amount_status', 'paid')
            ->with([
                'transaction.patient',
                'settlementItems',
            ])
            ->orderBy('created_at');

        if ($periodStart) {
            $query->whereDate('created_at', '>=', $periodStart);
        }

        if ($periodEnd) {
            $query->whereDate('created_at', '<=', $periodEnd);
        }

        return $query->get()
            ->map(function ($invoice) {
                $alreadySettled = (float) $invoice->settlementItems->sum(function ($item) {
                    return (float) $item->applied_paid_amount + (float) $item->applied_discount_amount;
                });

                $invoice->already_settled_amount = $alreadySettled;
                $invoice->remaining_due = max(0, (float) $invoice->insurance_amount - $alreadySettled);

                return $invoice;
            })
            ->filter(fn ($invoice) => $invoice->remaining_due > 0)
            ->values();
    }

    public function refreshInvoiceInsuranceStatus(Invoice $invoice): void
    {
        $alreadySettled = (float) $invoice->settlementItems()
            ->sum(DB::raw('applied_paid_amount + applied_discount_amount'));

        $insuranceAmount = (float) $invoice->insurance_amount;

        if ($insuranceAmount <= 0) {
            $invoice->insurance_status = null;
        } elseif ($alreadySettled <= 0) {
            $invoice->insurance_status = 'pending';
        } elseif ($alreadySettled < $insuranceAmount) {
            $invoice->insurance_status = 'partial';
        } else {
            $invoice->insurance_status = 'paid';
        }

        $invoice->save();
    }

    public function getCompanyBalanceData(InsuranceCompany $company): array
    {
        $allInvoices = Invoice::query()
            ->where('insurance_company_id', $company->id)
            ->where('insurance_amount', '>', 0)
            ->with('settlementItems')
            ->get();

        $montantTotal = (float) $allInvoices->sum('insurance_amount');

        $montantPaye = (float) $allInvoices->sum(function ($invoice) {
            return $invoice->settlementItems->sum('applied_paid_amount');
        });

        $montantRemise = (float) $allInvoices->sum(function ($invoice) {
            return $invoice->settlementItems->sum('applied_discount_amount');
        });

        $montantDu = (float) $allInvoices->sum(function ($invoice) {
            $alreadySettled = $invoice->settlementItems->sum(function ($item) {
                return (float) $item->applied_paid_amount + (float) $item->applied_discount_amount;
            });

            return max(0, (float) $invoice->insurance_amount - $alreadySettled);
        });

        $facturesImpayees = $allInvoices->filter(function ($invoice) {
            $alreadySettled = $invoice->settlementItems->sum(function ($item) {
                return (float) $item->applied_paid_amount + (float) $item->applied_discount_amount;
            });

            return $invoice->patient_amount_status === 'paid'
                && ((float) $invoice->insurance_amount - $alreadySettled) > 0;
        })->count();

        return [
            'montant_total' => $montantTotal,
            'montant_paye' => $montantPaye,
            'montant_remise' => $montantRemise,
            'montant_du' => $montantDu,
            'factures_impayees' => $facturesImpayees,
            'total_factures' => $allInvoices->count(),
        ];
    }
}