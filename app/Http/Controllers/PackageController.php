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
        $packages = Package::with(['tests', 'services'])->get(); // Récupérer les packages pour la liste
        $departments = Department::all(); // Récupérer les départements pour le formulaire de création
        $tests = Test::all(); // Récupérer les départements pour le formulaire de création
        $services = Service::all(); // Récupérer les départements pour le formulaire de création
		return view('packages.index', compact('packages', 'departments', 'tests', 'services'));
	}

	public function store(Request $request)
	{

		$request->validate([
            'name' => 'required'
            ]);

		$package['name'] = $request->name;
		$package['description'] = $request->description;
        $package['department_id'] = $request->department_id;
        $package['famille_acte'] = \App\Enums\Assurance\FamilleActe::tryFrom((string) $request->famille_acte)?->value;
        $package['price'] = 0;

        if ($request->has('tests')) {
            foreach ($request->tests as $test) {
                $package['price'] += Test::find($test)->amount;
            }
        }

        if ($request->has('services')) {
            foreach ($request->services as $service) {
                $package['price'] += Service::find($service)->amount;
            }
        }

		$package = Package::create($package);
        $package->services()->attach($request->services);
        $package->tests()->attach($request->tests);

		return back()->with('success', 'Package Created Successfully.');

	}

    public function edit($id){
        $package = Package::find($id);
        $departments = Department::all();
        $tests = Test::all();
        $services = Service::all();

        return view('packages.edit', [
            'package' => $package,
            'departments' => $departments,
            'tests' => $tests,
            'services' => $services
        ]);
    }
	public function update(Request $request, $id)
	{
        $package = Package::find($request->id);
		$data['name'] = $request->name;
		$data['description'] = $request->description;
		$data['department_id'] = $request->department_id;
		$data['famille_acte'] = \App\Enums\Assurance\FamilleActe::tryFrom((string) $request->famille_acte)?->value ?? $package->famille_acte;
		$data['price'] = 0;


        if ($request->has('tests')) {
            foreach ($request->tests as $test) {
                $data['price'] += Test::find($test)->amount;
            }
        }

        if ($request->has('services')) {
            foreach ($request->services as $service) {
                $data['price'] += Service::find($service)->amount;
            }
        }

		$package->update($data);

		// Gérer les tests associés
        if ($request->has('tests')) {
            $package->tests()->sync($request->tests);
		}

        // Gérer les services associés
        if ($request->has('services')) {
            $package->services()->sync($request->services);
		}

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
	 	$package = Package::find($request->id);

	 	// if(count($package->packageSales)) {
	 	// 	return back()->with('error', 'Package cannot deleted..');
	 	// }

        $package->tests()->detach();
        $package->services()->detach();
	 	$package->delete();

	 	return back()->with('success', 'Package Deleted Successfully.');
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
