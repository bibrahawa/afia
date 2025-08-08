<?php

namespace App\Http\Controllers;

use App\Models\PatientInsurance;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientInsuranceController extends Controller
{
    public function index()
    {
        $patientInsurances = PatientInsurance::with('patient', 'insuranceCompany')->latest()->paginate(10);
        $patients = Patient::all();
        $insuranceCompanies = InsuranceCompany::all();

        return view('patient_insurance.index', compact('patientInsurances', 'patients', 'insuranceCompanies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'policy_number' => 'required|string|max:100',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'annual_limit' => 'nullable|numeric',
        ]);

        $data = $request->all();

        if(!isset($data->used_amount)){
            $data['used_amount'] = 0;
        }

        PatientInsurance::create($data);
        return back()->with('success', 'Assurance enregistrée.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'policy_number' => 'required|string|max:100',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'annual_limit' => 'nullable|numeric',
        ]);

        $patientInsurance = PatientInsurance::find($request->id);

        $patientInsurance->update($request->all());
        return back()->with('success', 'Contrat mis à jour.');
    }

    public function destroy(Request $request)
    {
        $patientInsurance = PatientInsurance::find($request->id);
        $patientInsurance->delete();
        return back()->with('success', 'Contrat supprimé.');
    }
}
