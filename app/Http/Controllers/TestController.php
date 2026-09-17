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
		$tests = Test::get();
        $antibiotics = [];
		return view('tests.test', compact('tests', 'services' ));
	}

	public function store(Request $request)
    {
        $request->validate( ['name' => 'required|unique_etablissement:tests,name']);
        $test = Test::create($request->all());
        return back()->with('success', 'Examen saved Successfully.');
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
        $request->validate(['name'=>'required']);
        $test = Test::find($request->edit_id);
        $test->update($request->all());
        return back()->with('success', 'Examen Updated successfully');
    }

    public function delete(Request $request)
    {
        $examen = Test::find($request->id);

        if (count($examen->consultations)) {
            return back()->with('error', 'Test cannot be deleted...');
        }

        $examen->delete();

        return back()->with('success', 'Test successfully Deleted');

    }

}
