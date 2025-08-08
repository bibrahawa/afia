<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InsuranceCompany;


class Api_InvoiceController extends Controller
{
    /**
     * Afficher la liste des factures
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['transaction.patient', 'insuranceCompany', 'items']);

        // Filtres
        if ($request->has('insurance_status')) {
            $query->where('insurance_status', $request->insurance_status);
        }

        if ($request->has('patient_amount_status')) {
            $query->where('patient_amount_status', $request->patient_amount_status);
        }

        if ($request->has('insurance_company_id')) {
            $query->where('insurance_company_id', $request->insurance_company_id);
        }

        $invoices = $query->latest()->paginate(15);
        $insuranceCompanies = InsuranceCompany::where('status', 'active')->get();

        return view('invoices.index', compact('invoices', 'insuranceCompanies'));
    }

    /**
     * Afficher le détail d'une facture
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'transaction.patient',
            'insuranceCompany',
            'patientInsurance',
            'items',
            'claims'
        ]);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Imprimer une facture
     */
    public function print(Invoice $invoice)
    {
        $invoice->load([
            'transaction.patient',
            'insuranceCompany',
            'patientInsurance',
            'items'
        ]);

        return view('invoices.print', compact('invoice'));
    }

    /**
     * Mettre à jour le statut d'une facture
     */
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $request->validate([
            'field' => 'required|in:insurance_status,patient_amount_status',
            'status' => 'required|string'
        ]);

        $invoice->update([
            $request->field => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès'
        ]);
    }
}