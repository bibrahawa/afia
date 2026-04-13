<?php 

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use Illuminate\Http\Request;

class InsuranceCompanyController extends Controller
{
    public function index()
    {
        $companies = InsuranceCompany::latest()->get();
        return view('insurance_companies.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|unique:insurance_companies',
            'email' => 'nullable|email',
            'default_coverage_percentage' => 'nullable|numeric|min:0|max:100',
        ]);


        $data = $request->all();
        if($data['default_coverage_percentage'] == null){
            $data['default_coverage_percentage'] = 0;
        }

        InsuranceCompany::create($data);
        return back()->with('success', 'Compagnie créée avec succès.');
    }

    public function update(Request $request)
    {
        $insuranceCompany = InsuranceCompany::findOrFail($request->id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|unique:insurance_companies,code,' . $insuranceCompany->id,
            'email' => 'nullable|email',
            'default_coverage_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $data = $request->all();
        if($data['default_coverage_percentage'] == null){
            $data['default_coverage_percentage'] = 0;
        }

        $insuranceCompany->update($data);
        return back()->with('success', 'Compagnie mise à jour.');
    }

    public function destroy()
    {
        $insuranceCompany = InsuranceCompany::findOrFail(request()->id);
        $insuranceCompany->delete();
        return back()->with('success', 'Compagnie supprimée.');
    }
}
