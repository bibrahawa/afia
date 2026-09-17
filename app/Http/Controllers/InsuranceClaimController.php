<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaim;
use App\Models\Invoice; // Pour les relations
use App\Models\InsuranceCompany; // Pour les relations
use App\Models\Patient; // Pour les relations
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsuranceClaimController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Charge les relations nécessaires pour l'affichage
        $claims = InsuranceClaim::with(['invoice', 'insuranceCompany', 'patient'])->get();
        return view('insurance_claims.index', compact('claims'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Passe les données nécessaires pour les listes déroulantes
        $invoices = Invoice::all();
        $insuranceCompanies = InsuranceCompany::all();
        $patients = Patient::suivisParEtablissement()->orderBy('last_name')->get(); // Assure-toi d'avoir ce modèle et des données

        return view('insurance_claims.create', compact('invoices', 'insuranceCompanies', 'patients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'claim_number' => 'required|string|max:255|unique_etablissement:insurance_claims,claim_number',
            'invoice_id' => 'required|exists_etablissement:invoices,id',
            'insurance_company_id' => 'required|exists_etablissement:insurance_companies,id',
            'patient_id' => 'required|exists:patients,id',
            'claimed_amount' => 'required|numeric|min:0',
            'approved_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['draft', 'submitted', 'under_review', 'approved', 'rejected', 'paid'])],
            'submission_date' => 'nullable|date',
            'approval_date' => 'nullable|date',
            'payment_date' => 'nullable|date',
            'rejection_reason' => 'nullable|string',
            'documents' => 'nullable|json', // Valide que c'est un JSON valide
        ]);

        InsuranceClaim::create($validatedData);

        return redirect()->route('insurance_claims.index')->with('success', 'Réclamation d\'assurance créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(InsuranceClaim $insuranceClaim)
    {
        // Charge les relations pour l'affichage détaillé
        $insuranceClaim->load(['invoice', 'insuranceCompany', 'patient']);
        return view('insurance_claims.show', compact('insuranceClaim'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InsuranceClaim $insuranceClaim)
    {
        // Passe les données nécessaires pour les listes déroulantes
        $invoices = Invoice::all();
        $insuranceCompanies = InsuranceCompany::all();
        $patients = Patient::suivisParEtablissement()->orderBy('last_name')->get();

        return view('insurance_claims.edit', compact('insuranceClaim', 'invoices', 'insuranceCompanies', 'patients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InsuranceClaim $insuranceClaim)
    {
        $validatedData = $request->validate([
            'claim_number' => [
                'required',
                'string',
                'max:255',
                'unique_etablissement:insurance_claims,claim_number,' . $insuranceClaim->id, // Ignore l'ID actuel lors de la vérification d'unicité
            ],
            'invoice_id' => 'required|exists_etablissement:invoices,id',
            'insurance_company_id' => 'required|exists_etablissement:insurance_companies,id',
            'patient_id' => 'required|exists:patients,id',
            'claimed_amount' => 'required|numeric|min:0',
            'approved_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['draft', 'submitted', 'under_review', 'approved', 'rejected', 'paid'])],
            'submission_date' => 'nullable|date',
            'approval_date' => 'nullable|date',
            'payment_date' => 'nullable|date',
            'rejection_reason' => 'nullable|string',
            'documents' => 'nullable|json',
        ]);

        $insuranceClaim->update($validatedData);

        return redirect()->route('insurance_claims.index')->with('success', 'Réclamation d\'assurance mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InsuranceClaim $insuranceClaim)
    {
        $insuranceClaim->delete();
        return redirect()->route('insurance_claims.index')->with('success', 'Réclamation d\'assurance supprimée avec succès.');
    }
}

