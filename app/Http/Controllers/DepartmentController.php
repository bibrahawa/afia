<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $departments = Department::all();
        return view('departments.index', compact('departments'));
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $request->validate( ['name'=>'required|unique_etablissement:departments,name']);
        Department::create($data);
        return back()->with('success', 'Department saved successfully.');
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //return $request->all();

        $data = Department::find($request->id );
        $data->name = ($request->name);
        $data->save ();
        return back()->with('success', 'Department Updated successfully');
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $department = Department::findOrFail($id);

        if ($department->services()->exists() ||
            $department->employees()->exists()
            // || $department->doctors()->exists()
            ) {
            return back()->with('error', 'Department cannot be deleted because it has related records.');
        }

        try {
            $department->delete();
            return back()->with('success', 'Department deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete department.');
        }
    }

}
