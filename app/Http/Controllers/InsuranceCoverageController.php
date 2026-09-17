<?php 

namespace App\Http\Controllers;

use App\Models\InsuranceCoverage;
use App\Models\InsuranceCompany;
use App\Models\Medicament;
use App\Models\Package;
use App\Models\Service;
use App\Models\Chambre;
use App\Models\Test;
use App\Support\Facturation\TypesFacturables;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InsuranceCoverageController extends Controller
{
    public function index()
    {
        $coverages = InsuranceCoverage::with('insuranceCompany', 'coverageable')->latest()->get();
        $insuranceCompanies = InsuranceCompany::all();
        $services = Service::all();
        $medicaments = Medicament::all();
        $examens = Test::all();
        $packages = Package::all();
        $chambres = Chambre::all();

        return view('insurance_coverages.index', compact('coverages', 'insuranceCompanies', 'services', 'medicaments', 'examens', 'packages', 'chambres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'insurance_company_id' => 'required|exists_etablissement:insurance_companies,id',
            'coverageable_type' => 'required|string',
            'coverageable_id' => 'required|integer',
            'valid_from' => 'required|date',
            'acte_price' => 'required|numeric|min:0'
        ]);

        $validated = $request->all();
        $validated['coverageable_type'] = $this->typeActe($validated['coverageable_type']);

        InsuranceCoverage::create($validated);

        return back()->with('success', 'Couverture ajoutée.');
    }

    public function update(Request $request)
    {

        $validated = $request->validate( [
            'insurance_company_id' => 'required|exists_etablissement:insurance_companies,id',
            'coverageable_type' => 'required|string',
            'coverageable_id' => 'required|integer',
            'valid_from' => 'required|date',
            'acte_price' => 'required|numeric|min:0'

        ]);

        $insuranceCoverage = InsuranceCoverage::find($request->id);
        
        $validated = $request->all();
        // CORRIGÉ : Package et Chambre n'étaient pas reconnus à la modification
        // (le libellé brut « Package » était enregistré tel quel).
        $validated['coverageable_type'] = $this->typeActe($validated['coverageable_type']);

        $insuranceCoverage->update($validated);

        return back()->with('success', 'Couverture mise à jour.');
    }

    public function destroy(Request $request)
    {
        $insuranceCoverage = InsuranceCoverage::find($request->id);
        $insuranceCoverage->delete();
        return back()->with('success', 'Couverture supprimée.');
    }

    /**
     * Type d'acte venu du formulaire (« Service », « Médicament », alias ou
     * ancien nom de classe) → alias stable. Tout autre valeur est refusée :
     * avant, un libellé inconnu était enregistré tel quel.
     */
    private function typeActe(?string $saisie): string
    {
        $classe = TypesFacturables::depuisSaisie($saisie, TypesFacturables::ACTES_COUVRABLES);

        if (! $classe) {
            throw ValidationException::withMessages(['coverageable_type' => "Type d'acte non reconnu."]);
        }

        return TypesFacturables::alias($classe);
    }
}
