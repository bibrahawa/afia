<?php


namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        $roles = Role::all();
        return view('users.index', compact('users','roles'));
    }

    public function listePermissions_old($id)
    {
        $user = User::find($id);
        $permissions = Permission::all();
        return view('users.create_permissions', compact('user', 'permissions'));
    }

    // Dans votre contrôleur
    public function listePermissions($id) {
        
        $user = User::findOrFail($id);
        $permissions = Permission::all();

        // Modules et leurs clés
        $modulesConfig = [
            'Dashboard & Configuration' => ['dashboard', 'backup', 'setting', 'hospital', 'tax', 'config'],
            'Gestion Utilisateurs' => ['users'],
            'Employés' => ['employee', 'medecin'],
            'Structure' => ['department', 'service'],
            'Patients & Consultations' => ['patient', 'consultation', 'appointment'],
            'Médical' => ['medicament', 'package', 'test'],
            'Comptabilité' => ['account', 'report', 'payment'],
            'Hospitalisation' => ['hospitalisation', 'chambre'],
            'Assurances' => ['insurance_company', 'insurance_coverage', 'patient_insurance', 'invoice', 'invoice_item', 'insurance_balance'],
        ];

        // Groupement des permissions par module
        $modules = [];
        foreach ($modulesConfig as $title => $keys) {
            $modules[$title] = $permissions->filter(function ($perm) use ($keys) {
                return collect($keys)->contains(fn($key) => str_starts_with($perm->name, $key . '.'));
            });
        }

        // Profils prédéfinis (à ajuster selon ton besoin)
        $rolePermissions = [
            'medecin' => $this->getMedecinPermissions(),
            'comptable' => $this->getComptablePermissions(),
            'secretaire' => $this->getSecretairePermissions(),
        ];

        return view('users.create_permissions', [
            'user' => $user,
            'modules' => $modules,
            'rolePermissions' => $rolePermissions,
            'userPermissions' => $user->getPermissionNames()->toArray(),
        ]);
    }

    public function assignPermissions(Request $request, $id)
    {

        try {
            $user = User::findOrFail($id);
        
            // Récupérer uniquement les champs nécessaires (en ignorant _token et autres)
            $permissions = collect($request->except('_token'))->mapWithKeys(fn($value, $key) => [str_replace('_', '.', $key) => (bool) $value]);
        
            $newPermissions = $permissions->filter()->keys();
        
            $user->syncPermissions($newPermissions);
        
            return redirect()->back()->with('success', 'Permissions mises à jour');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
        }
        
        

        return redirect()->route('users.index',compact('user'))->withSucces('permissions ajouté avec succès.');;
    }

    public function create()
    {
        $roles = Role::all();
        $departments = Department::all();
        return view('users.create', compact('roles','departments'));
    }

    public function store(Request $request)
    {

        $rules = [
            'first_name'                  => 'required|string|max:255',
            'last_name'               => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email',
            'phone'              => 'required|string|min:8|max:15',
            'password'             => ['required', 'confirmed', Rules\Password::defaults()],
            'password_confirmation'=> 'required',
            'department_id'=>'required|numeric',
        ];

        if (count($request->working_day)) {
             $request['working_day'] = implode(',',$request->working_day);
        }

        $data = $request->all();

        if($request->role_id == 'medecin')
        {
            $data['first_name'] = 'DR '.$request->first_name;
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $request->password;

        if($user->save()){
            $data['user_id'] = $user->id;
            Employee::create($data);
            $user->assignRole($request->role_id);
        }

        return redirect()->route('users.index')->withSucces('Utilisateur ajouté avec succès.');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'nom'                  => 'required|string|max:255',
            'prenom'               => 'required|string|max:255',
            'contact'              => 'required|string|min:8|max:15',
            'role'                 => 'required|string|max:50',
            'password'             => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::find($user->id);


        if ($request->hasFile('image')) {
            $image = $request->image->getClientOriginalName() . '_' . time() . '.' . $request->image->extension();
            $image = str_replace(" ", "_", $image);
            $request->image->move(public_path('assets/img'), $image);
            $user->image = $image;
        }

        $user->nom     = $request->nom;
        $user->prenom  = $request->prenom;
        $user->contact = $request->contact;
        $user->email   = $request->email;
        $user->contact = $request->contact ?? $user->contact;
        $user->role    = $request->role ?? $user->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->update();
        return redirect()->route('users.index')->withSucces('Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->withSucces('Utilisateur supprimé avec succès.');
    }

    public function disableUser($id)
    {
       $user = User::findOrFail($id);

       if(is_null($user)){
          return back();
        }
        $user->status ? $user->status =  false : $user->status =  true;
        if ($user->status ==false && $user->save()) {
            return back()->withError('Utilisateur suspendu avec succes');
        }else{
            if ($user->status == true && $user->save()) {
                return back()->withSucces('Utilisateur Activé avec succes');
            }
        }
    }

    private function getMedecinPermissions(){
        // MEDECIN - Permissions liées aux soins et consultations
        return [
            // Dashboard médecin
            'dashboard.view', 'dashboard.medecin',
            
            // Gestion de ses activités médicales
            'medecin.appointments', 'medecin.confirm_appointment', 'medecin.complete_appointment', 
            'medecin.availabilities', 'medecin.leaves',
            
            // Patients - Lecture et ajout de fichiers
            'patient.view', 'patient.add_file',
            
            // Consultations - Toutes les actions médicales
            'consultation.view', 'consultation.create', 'consultation.edit', 'consultation.delete', 
            'consultation.facture', 'consultation.ordonnance', 'consultation.medicament', 'consultation.examen',
            
            // Rendez-vous - Consulter uniquement
            'appointment.view',
            
            // Médicaments - Consulter pour prescriptions
            'medicament.view',
            
            // Tests - Prescrire et voir résultats
            'test.view', 'test.status',
            
            // Hospitalisations - Médical uniquement
            'hospitalisation.view', 'hospitalisation.create', 'hospitalisation.edit',
            
            // Départements et Services - Lecture
            'department.view', 'service.view',
            
            // Chambres - Consulter disponibilité
            'chambre.view',
            
            // Assurances patients - Lecture
            'patient_insurance.view',
        ];
    } 
    
    private function getComptablePermissions(){
        // COMPTABLE - Permissions financières et rapports
        return [
            // Dashboard
            'dashboard.view',
            
            // Patients - Consultation pour facturation
            'patient.view',
            
            // Consultations - Facturation uniquement
            'consultation.view', 'consultation.facturer', 'consultation.facture', 'consultation.paiement',
            
            // Comptabilité - Toutes les actions financières
            'account.facture', 'account.payer', 'account.service_report', 'account.opd_report', 'account.package_report',
            
            // Rapports - Tous les rapports
            'report.view', 'report.actes', 'report.service',
            
            // Hospitalisations - Facturation
            'hospitalisation.view', 'hospitalisation.payer', 'hospitalisation.facture',
            
            // Packages - Vente
            'package.view', 'package.sale',
            
            // Médicaments - Consultation prix
            'medicament.view',
            
            // Services - Consultation prix
            'service.view',
            
            // Tests - Consultation prix
            'test.view',
            
            // Assurances complètes - Gestion financière
            'insurance_company.view', 'insurance_coverage.view',
            'patient_insurance.view', 'patient_insurance.create', 'patient_insurance.edit',
            
            // Factures assurance - Toutes les actions
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
            'invoice_item.view', 'invoice_item.create', 'invoice_item.edit', 'invoice_item.delete',
            
            // Paiements - Toutes les actions
            'payment.view', 'payment.process', 'payment.calculate', 'payment.hospitalisation',
            
            // Soldes Assurance - Toutes les actions
            'insurance_balance.view', 'insurance_balance.show', 'insurance_balance.payment', 'insurance_balance.export',
        ];
    }
    
    private function getSecretairePermissions(){
        // SECRETAIRE - Permissions administratives et accueil
        return [
            // Dashboard
            'dashboard.view',
            
            // Patients - Gestion complète (accueil)
            'patient.view', 'patient.create', 'patient.edit', 'patient.add_file',
            
            // Rendez-vous - Gestion complète
            'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete',
            
            // Consultations - Programmation uniquement
            'consultation.view', 'consultation.create',
            
            // Départements et Services - Lecture
            'department.view', 'service.view',
            
            // Employés - Consultation
            'employee.view', 'employee.profile',
            
            // Médicaments - Consultation
            'medicament.view',
            
            // Packages - Consultation et vente
            'package.view', 'package.sale',
            
            // Tests - Programmation
            'test.view',
            
            // Hospitalisations - Admission
            'hospitalisation.view', 'hospitalisation.create',
            
            // Chambres - Gestion disponibilité
            'chambre.view',
            
            // Assurances patients - Vérification couverture
            'insurance_company.view', 'insurance_coverage.view', 'patient_insurance.view',
            
            // Paiements - Consultation uniquement
            'payment.view', 'payment.calculate',
        ];
    }


}
