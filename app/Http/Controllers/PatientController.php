<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\User;

class PatientController extends Controller
{
   public function __construct()
    {

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $patients = Patient::get();
        return view('patients.index' , compact('patients'));
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
        $data['age'] = date('Y') - date('Y', strtotime($data['birth_date']));
        $data['country'] = 'Guinee';
        $data['state'] = 'Conakry';

        $user = new User();
        $user->name = $request->first_name;
        $user->email = $request->email;
        $user->password = "12345678";

        if($user->save()){
            $data['user_id'] = $user->id;
            Patient::create($data);
            $user->assignRole('patient');
        }

        return back()->with('success', 'Patient saved Successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $patient = Patient::find($id);
        return view('patients.show' , compact('patient'));
        //
    }

    public function addFile(Request $request, $id){

        if(!$request->hasFile('file')) {
            return back()->with('error', 'No file selected for upload');
        }

        if(!$id) {
            return back()->with('error', 'Invalid patient ID');
        }
        $patient = Patient::find($id);
        try {

            $file = $request->file('file');
            // Store file
            $chemin = $file->store('patients/files/'.$patient->id, 'public');
            // Create file record

            $patient->files()->create([
                'nom_fichier' => $request->name,
                'chemin_fichier' => $chemin,
                'used_by' => 'accueil'
            ]);

            return back()->with('success', 'Files uploaded successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading files: ' . $e->getMessage());
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $patient = Patient::find($id);

        if($patient->status)
        {
           $patient->status = 0;
        }
        else
        {

         $patient->status = 1;
        }
        $patient->save();
        return back()->with('success', 'Status changed successfully.');
        //
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $patient = Patient::find($id);
        $data = $request->all();
        $data['age'] = date('Y') - date('Y', strtotime($data['birth_date']));
        $patient->update($data);
        return back()
            ->with('success', 'Patient updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $patient = Patient::find($id);
        if (count($patient->invoices) || count($patient->reports) || count($patient->packageSales) ) {
            return back()->with('error', 'Patient cannot deleted...');
        }
        $patient->delete();
        return redirect()->route('patient.index')->with('success', 'Patient Deletetd Successfully');
        //
    }
}
