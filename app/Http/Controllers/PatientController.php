<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\User;
use App\Models\Role;

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
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15|unique:users,phone',
        ]);

        $data = $request->all();
        $data['country'] = 'Guinee';
        $data['state'] = 'Conakry';

        // Verifier si le numéro de téléphone est déjà utilisé
        $existingUser = User::where('phone', $request->phone)->first();
        if ($existingUser) {
            return back()->with('error', 'Le numéro de téléphone est déjà utilisé par un autre utilisateur.');
        }

        $user = new User();
        $user->name = $request->first_name;
        $user->phone = $request->phone;
        $user->email = rand(100000, 999999) . $request->last_name . '@aprosafe.com';
        $user->password = "12345678";

        if($user->save()){
            $data['user_id'] = $user->id;
            Patient::create($data);
            $user->assignRole('patient');
        }

        return back()->with('success', 'Patiente enregistré avec succès.');
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
        $patient->update($data);
        return back()->with('success', 'Information de la patiente modifier avec succès.');
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
        if (count($patient->consultations)) {
            return back()->with('error', 'La patiente ne peut pas être supprimé...');        }
        $patient->delete();
        return redirect()->route('patient.index')->with('success', 'La patiente est supprimé avec success');
    }
}
