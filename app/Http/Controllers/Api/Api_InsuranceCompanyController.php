<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;


class Api_InsuranceCompanyController extends Controller
{
    /**
     * Afficher la liste des compagnies d'assurance
     */
    public function index()
    {
        $companies = InsuranceCompany::with(['patientInsurances', 'claims'])
            ->withCount(['patientInsurances', 'claims'])
            ->get();

        return view('insurance-companies.index', compact('companies'));
    }

    /**
     * Obtenir les compagnies d'assurance actives (API)
     */
    public function getActiveCompanies()
    {
        $companies = InsuranceCompany::where('status', 'active')
            ->where(function($query) {
                $query->whereNull('contract_end_date')
                      ->orWhere('contract_end_date', '>=', now());
            })
            ->select('id', 'name', 'code', 'default_coverage_percentage')
            ->get();

        return response()->json([
            'success' => true,
            'companies' => $companies
        ]);
    }

    /**
     * Créer une nouvelle compagnie d'assurance
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:insurance_companies,code',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'default_coverage_percentage' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string'
        ]);

        InsuranceCompany::create($request->all());

        return redirect()->route('insurance-companies.index')
            ->with('success', 'Compagnie d\'assurance créée avec succès.');
    }

    /**
     * Mettre à jour une compagnie d'assurance
     */
    public function update(Request $request, InsuranceCompany $insuranceCompany)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:insurance_companies,code,' . $insuranceCompany->id,
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'status' => 'required|in:active,inactive',
            'default_coverage_percentage' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string'
        ]);

        $insuranceCompany->update($request->all());

        return redirect()->route('insurance-companies.index')
            ->with('success', 'Compagnie d\'assurance mise à jour avec succès.');
    }
}