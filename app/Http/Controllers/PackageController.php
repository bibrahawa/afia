<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Test;
Use App\Models\PackageTest;
use App\Models\Patient;
use App\Models\Department;
use App\Models\Service;
use Auth;

class PackageController extends Controller
{

	public function getIndex()
	{
        $packages = Package::with(['tests', 'services', 'department'])->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $tests = Test::orderBy('name')->get();
        $services = Service::actifs()->orderBy('name')->get();
		return view('packages.index', compact('packages', 'departments', 'tests', 'services'));
	}

	public function store(Request $request)
	{

		$request->validate($this->regles(), $this->messages());

		$package['name'] = $request->name;
		$package['description'] = $request->description;
        $package['department_id'] = $request->department_id;
        $package['famille_acte'] = \App\Enums\Assurance\FamilleActe::tryFrom((string) $request->famille_acte)?->value;
        $package['price'] = $this->prix($request);

		$package = Package::create($package);
        $package->services()->attach($request->input('services', []));
        $package->tests()->attach($request->input('tests', []));

		return back()->with('success', 'Forfait créé.');

	}

    public function edit($id){
        $package = Package::findOrFail($id);
        $departments = Department::all();
        $tests = Test::all();
        // Actes proposés + ceux déjà dans le forfait (même masqués, pour ne pas les perdre à l'enregistrement).
        $dejaInclus = $package->services()->pluck('services.id');
        $services = Service::where(fn ($q) => $q->where('actif', true)->orWhereIn('id', $dejaInclus))->orderBy('name')->get();

        return view('packages.edit', [
            'package' => $package,
            'departments' => $departments,
            'tests' => $tests,
            'services' => $services
        ]);
    }
	public function update(Request $request, $id)
	{
        // CORRIGÉ — lisait $request->id (champ absent du formulaire) au lieu de l'identifiant de l'URL.
        $package = Package::findOrFail($id);
		$request->validate($this->regles(), $this->messages());
		$data['name'] = $request->name;
		$data['description'] = $request->description;
		$data['department_id'] = $request->department_id;
		$data['famille_acte'] = \App\Enums\Assurance\FamilleActe::tryFrom((string) $request->famille_acte)?->value ?? $package->famille_acte;
		$data['price'] = $this->prix($request);

		$package->update($data);

		// CORRIGÉ — décocher tous les examens (ou tous les actes) ne les retirait pas.
        $package->tests()->sync($request->input('tests', []));
        $package->services()->sync($request->input('services', []));

        return redirect()->route('package.index')->with('success', 'Package modifié avec succès');
	}

	public function packageTestDelete(Request $request)
	{

		$package_test = PackageTest::find($request->id);
		if($package_test) {
			$package_test->delete();
		}
		return back()->with('success', 'Package Test Deleted Successfully.');
	}


	 public function delete(Request $request)
	 {
	 	//return $request->all();
	 	$package = Package::findOrFail($request->id);

	 	if (\Illuminate\Support\Facades\DB::table('consultation_package')->where('package_id', $package->id)->exists()) {
	 		return back()->with('error', "« {$package->name} » a déjà été utilisé en consultation : il ne peut pas être supprimé.");
	 	}

        $package->tests()->detach();
        $package->services()->detach();
	 	$package->delete();

	 	return back()->with('success', 'Forfait supprimé.');
	 }

    /** Actes et examens du catalogue de l'établissement uniquement. */
    private function regles(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists_etablissement:departments,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['exists_etablissement:services,id'],
            'tests' => ['nullable', 'array'],
            'tests.*' => ['exists_etablissement:tests,id'],
            'prix_forfait' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function messages(): array
    {
        return ['name.required' => 'Indiquez le nom du forfait.'];
    }

    /**
     * Prix du forfait : celui saisi (un forfait est souvent moins cher que la
     * somme de ses actes), sinon la somme des actes et examens choisis.
     */
    private function prix(Request $request): float
    {
        if ($request->filled('prix_forfait')) {
            return (float) $request->input('prix_forfait');
        }

        return (float) Test::whereIn('id', $request->input('tests', []))->sum('amount')
            + (float) Service::whereIn('id', $request->input('services', []))->sum('amount');
    }

     public function getServicesByDepartment($departmentId)
     {
         $department = Department::with('services')->find($departmentId); // Assuming a many-to-many or one-to-many relationship
         if ($department) {
             return response()->json($department->services);
         }

         return response()->json([]);
     }

     public function getTestsByDepartment($departmentId)
     {
         $department = Department::with('tests')->find($departmentId); // Assuming a many-to-many or one-to-many relationship
         if ($department) {
             return response()->json($department->tests);
         }

         return response()->json([]);
     }

    /*
     * Priorité 3 — SUPPRIMÉ : sale(), packageSale() et packageSales().
     * Code hérité d'une autre application (montants en « Rs. », table hospitals
     * supprimée, modèles PackageSale / Report / TestReport inexistants, numéro de
     * facture « dernier id + 1 ») : ces écrans plantaient. La vente d'un package
     * passe par la consultation et BillingService. Retirez les routes associées.
     */
}
