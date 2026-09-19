<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;
use App\Models\Service;

class TestController extends Controller
{
	public function index()
	{

		$services = Service::get();
		$tests = Test::orderBy('report_type')->orderBy('name')->get();
        $antibiotics = [];
		return view('tests.test', compact('tests', 'services' ));
	}

	public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique_etablissement:tests,name', 'amount' => 'nullable|numeric|min:0'],
            ['name.required' => 'Indiquez le nom de l\'examen.', 'name.unique_etablissement' => 'Cet examen existe déjà.']);
        Test::create($request->only(['name', 'report_type', 'description', 'amount']));
        return back()->with('success', 'Examen ajouté.');
    }


	 public function statusChange($id)
    {
        $lab = Test::find($id);

        if($lab->status)
        {
           $lab->status = 0;
        }
        else
        {
         $lab->status = 1;
        }
        $lab->save();
        return back()->with('success', 'Status changed successfully.');
    }

    public function edit(Request $request)
    {
        $request->validate([
            'edit_id' => 'required|exists_etablissement:tests,id',
            'name' => 'required|unique_etablissement:tests,name,' . (int) $request->edit_id,
            'amount' => 'nullable|numeric|min:0',
        ], ['name.required' => 'Indiquez le nom de l\'examen.', 'name.unique_etablissement' => 'Un autre examen porte déjà ce nom.']);
        $test = Test::findOrFail($request->edit_id);
        $test->update($request->only(['name', 'report_type', 'description', 'amount']));
        return back()->with('success', 'Examen modifié.');
    }

    public function delete(Request $request)
    {
        $examen = Test::findOrFail($request->id);

        if ($examen->consultations()->exists()) {
            return back()->with('error', "« {$examen->name} » a déjà été prescrit : il ne peut pas être supprimé.");
        }

        $examen->delete();

        return back()->with('success', 'Examen supprimé.');

    }

}
