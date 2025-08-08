<?php 

namespace App\Http\Controllers;

use App\Models\InsuranceCoverage;
use App\Models\InsuranceCompany;
use App\Models\Medicament;
use App\Models\Service;
use App\Models\Test;
use Illuminate\Http\Request;

class InsuranceCoverageController extends Controller
{
    public function index()
    {
        $coverages = InsuranceCoverage::with('insuranceCompany', 'coverageable')->latest()->get();
        $insuranceCompanies = InsuranceCompany::all();
        $services = \App\Models\Service::all();
        $medicaments = \App\Models\Medicament::all();
        $examens = \App\Models\Test::all();

        return view('insurance_coverages.index', compact('coverages', 'insuranceCompanies', 'services', 'medicaments', 'examens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'coverageable_type' => 'required|string',
            'coverageable_id' => 'required|integer',
            'valid_from' => 'required|date',
        ]);

        $validated = $request->all();

        if($validated['coverageable_type'] == 'Service'){
            $validated['coverageable_type'] = Service::class;
        }else if($validated['coverageable_type'] == 'Medicament'){
            $validated['coverageable_type'] = Medicament::class;
        }else if($validated['coverageable_type'] == 'Test'){
            $validated['coverageable_type'] = Test::class;
        }

        InsuranceCoverage::create($validated);

        return back()->with('success', 'Couverture ajoutée.');
    }

    public function update(Request $request)
    {

        $validated = $request->validate( [
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'coverageable_type' => 'required|string',
            'coverageable_id' => 'required|integer',
            'valid_from' => 'required|date',
        ]);

        $insuranceCoverage = InsuranceCoverage::find($request->id);
        
        $validated = $request->all();
        if($validated['coverageable_type'] == 'Service'){
            $validated['coverageable_type'] = Service::class;
        }else if($validated['coverageable_type'] == 'Medicament'){
            $validated['coverageable_type'] = Medicament::class;
        }else if($validated['coverageable_type'] == 'Test'){
            $validated['coverageable_type'] = Test::class;
        }

        $insuranceCoverage->update($validated);

        return back()->with('success', 'Couverture mise à jour.');
    }

    public function destroy(Request $request)
    {
        $insuranceCoverage = InsuranceCoverage::find($request->id);
        $insuranceCoverage->delete();
        return back()->with('success', 'Couverture supprimée.');
    }
}
