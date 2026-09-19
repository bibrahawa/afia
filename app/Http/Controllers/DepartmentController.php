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
        $departments = Department::withCount(['services', 'employees', 'motifsRdv'])->orderBy('name')->get();
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
        $request->validate(['name' => 'required|string|max:255|unique_etablissement:departments,name'],
            ['name.required' => 'Indiquez le nom du département.', 'name.unique_etablissement' => 'Ce département existe déjà.']);
        Department::create($request->only('name'));
        return back()->with('success', 'Département ajouté.');
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

        // CORRIGÉ — aucune validation : un nom vide ou un identifiant d'un autre établissement passait.
        $request->validate([
            'id' => 'required|exists_etablissement:departments,id',
            'name' => 'required|string|max:255|unique_etablissement:departments,name,' . (int) $request->id,
        ], ['name.required' => 'Indiquez le nom du département.', 'name.unique_etablissement' => 'Ce département existe déjà.']);

        $data = Department::findOrFail($request->id);
        $data->name = $request->name;
        $data->save();
        return back()->with('success', 'Département renommé.');
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
            return back()->with('error', 'Ce département contient des actes ou des employés : déplacez-les avant de le supprimer.');
        }

        try {
            $department->delete();
            return back()->with('success', 'Département supprimé.');
        } catch (\Exception $e) {
            return back()->with('error', 'Suppression impossible : ce département est encore utilisé (motifs de rendez-vous, forfaits…).');
        }
    }

}
