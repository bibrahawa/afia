<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use App\Services\InsuranceSettlementService;
use Illuminate\Http\Request;

class InsuranceSettlementController extends Controller
{
    public function __construct(
        private InsuranceSettlementService $insuranceSettlementService
    ) {
    }

    public function store(Request $request)
    {
        $request->validate([
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'paid_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'notes' => 'nullable|string',
        ]);

        $company = InsuranceCompany::findOrFail($request->insurance_company_id);

        $this->insuranceSettlementService->settle(
            company: $company,
            paidAmount: (float) $request->paid_amount,
            discountAmount: (float) ($request->discount_amount ?? 0),
            paymentMethod: $request->payment_method,
            paymentReference: $request->payment_reference,
            paymentDate: $request->payment_date,
            notes: $request->notes,
            periodStart: $request->period_start,
            periodEnd: $request->period_end
        );

        return back()->with('success', 'Règlement assurance enregistré avec succès.');
    }
}