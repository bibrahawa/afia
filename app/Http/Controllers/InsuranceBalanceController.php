<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Services\InsuranceSettlementService;
use Illuminate\Http\Request;

class InsuranceBalanceController extends Controller
{
    public function __construct(
        private InsuranceSettlementService $insuranceSettlementService
    ) {
    }

    public function index()
    {
        $companies = InsuranceCompany::where('status', 'active')
            ->with([
                'invoices.settlementItems',
            ])
            ->get(['id', 'name', 'code', 'status']);

        $insuranceBalances = $companies->map(function ($company) {
            $invoices = $company->invoices;

            $montantTotal = (float) $invoices->sum('insurance_amount');

            $montantPaye = (float) $invoices->sum(function ($invoice) {
                return $invoice->settlementItems->sum('applied_paid_amount');
            });

            $montantRemise = (float) $invoices->sum(function ($invoice) {
                return $invoice->settlementItems->sum('applied_discount_amount');
            });

            $montantSolde = $montantPaye + $montantRemise;
            $montantDu = max(0, $montantTotal - $montantSolde);

            $facturesImpayees = $invoices->filter(function ($invoice) {
                $settled = (float) $invoice->settlementItems->sum(function ($item) {
                    return $item->applied_paid_amount + $item->applied_discount_amount;
                });

                return (float) $invoice->insurance_amount > $settled;
            })->count();

            return [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'status' => $company->status,
                'montant_du' => $montantDu,
                'montant_paye' => $montantPaye,
                'montant_remise' => $montantRemise,
                'montant_total' => $montantTotal,
                'factures_impayees' => $facturesImpayees,
                'total_factures' => $invoices->count(),
            ];
        })->sortByDesc('montant_du')->values();

        return view('insurance.balance.index', compact('insuranceBalances'));
    }

    public function show($id)
    {
        $insurance = InsuranceCompany::with([
            'settlements.items.invoice.transaction.patient',
        ])->findOrFail($id);

        $invoices = Invoice::where('insurance_company_id', $id)
            ->where('insurance_amount', '>', 0)
            ->with([
                'transaction.patient',
                'settlementItems',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $allInvoices = Invoice::where('insurance_company_id', $id)
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

        $montantSolde = $montantPaye + $montantRemise;
        $montantDu = max(0, $montantTotal - $montantSolde);

        $facturesImpayees = $allInvoices->filter(function ($invoice) {
            $settled = (float) $invoice->settlementItems->sum(function ($item) {
                return $item->applied_paid_amount + $item->applied_discount_amount;
            });

            return (float) $invoice->insurance_amount > $settled;
        })->count();

        $stats = [
            'montant_du' => $montantDu,
            'montant_paye' => $montantPaye,
            'montant_remise' => $montantRemise,
            'montant_total' => $montantTotal,
            'factures_impayees' => $facturesImpayees,
            'total_factures' => $allInvoices->count(),
        ];

        $settlements = $insurance->settlements()
            ->with('items.invoice.transaction.patient')
            ->latest()
            ->paginate(10, ['*'], 'settlements_page');

        return view('insurance.balance.show', compact(
            'insurance',
            'invoices',
            'stats',
            'settlements'
        ));
    }

    public function processPaiement(Request $request)
    {

        $request->validate([
            'insurance_companies_id' => 'required|exists_etablissement:insurance_companies,id',
            'montant' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:CASH,CARD,MOBILE',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'notes' => 'nullable|string',
        ]);

        try {
            $insurance = InsuranceCompany::findOrFail($request->insurance_companies_id);

            $settlement = $this->insuranceSettlementService->settle(
                company: $insurance,
                paidAmount: (float) $request->montant,
                discountAmount: (float) ($request->discount_amount ?? 0),
                paymentMethod: $request->payment_method,
                paymentReference: $request->payment_reference,
                paymentDate: $request->payment_date,
                notes: $request->notes,
                periodStart: $request->period_start,
                periodEnd: $request->period_end
            );

            return redirect()->back()->with(
                'success',
                'Règlement assurance enregistré avec succès. Settlement: ' . $settlement->settlement_no
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(
                'error',
                'Erreur lors du traitement: ' . $e->getMessage()
            );
        }
    }
}