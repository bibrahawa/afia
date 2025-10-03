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

    public function assignPermissions(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Permissions
            $permissions = $request->input('permissions', []);
            $user->syncPermissions($permissions);
            
            // Rôles (si vous avez un champ roles[] dans le formulaire)
            if ($request->has('roles')) {
                $roles = $request->input('roles', []);
                $user->syncRoles($roles);
            }
            
            // Effacer le cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            return redirect()->route('users.index')
                ->with('success', 'Les permissions de ' . $user->name . ' ont été mises à jour avec succès. (' . count($permissions) . ' permissions assignées)');
                
        } catch (\Exception $e) {
            \Log::error('Erreur assignPermissions: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour des permissions : ' . $e->getMessage())
                ->withInput();
        }
    }

    public function listePermissions($id) 
    {
        $user = User::findOrFail($id);
        
        // Configuration des modules et leurs préfixes
        $modulesConfig = [
            'Dashboard & Configuration' => ['dashboard', 'backup', 'setting'],
            'Configuration Hôpital' => ['hospital', 'tax', 'config'],
            'Gestion Utilisateurs' => ['users'],
            'Employés' => ['employee'],
            'Espace Médecin' => ['medecin'],
            'Structure' => ['department', 'service'],
            'Patients' => ['patient'],
            'Consultations' => ['consultation'],
            'Rendez-vous' => ['appointment'],
            'Pharmacie' => ['medicament'],
            'Packages & Tests' => ['package', 'test'],
            'Comptabilité' => ['account', 'payment'],
            'Rapports' => ['report'],
            'Hospitalisation' => ['hospitalisation', 'chambre'],
            'Assurances' => [
                'insurance_company', 
                'insurance_coverage', 
                'patient_insurance'
            ],
            'Factures Assurance' => ['invoice', 'invoice_item', 'insurance_balance'],
        ];

        // Récupérer toutes les permissions
        $allPermissions = Permission::orderBy('name')->get();

        // Grouper les permissions par module
        $modules = [];
        $assignedPermissions = []; // Pour suivre les permissions déjà assignées

        foreach ($modulesConfig as $moduleName => $prefixes) {
            $modulePermissions = $allPermissions->filter(function ($permission) use ($prefixes, &$assignedPermissions) {
                // Vérifier si la permission correspond à un des préfixes
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($permission->name, $prefix . '.')) {
                        // Marquer comme assignée pour éviter les doublons
                        if (!in_array($permission->id, $assignedPermissions)) {
                            $assignedPermissions[] = $permission->id;
                            return true;
                        }
                    }
                }
                return false;
            });

            // N'ajouter le module que s'il contient des permissions
            if ($modulePermissions->isNotEmpty()) {
                $modules[$moduleName] = $modulePermissions;
            }
        }

        // Ajouter les permissions non catégorisées (si il y en a)
        $uncategorizedPermissions = $allPermissions->filter(function ($permission) use ($assignedPermissions) {
            return !in_array($permission->id, $assignedPermissions);
        });

        if ($uncategorizedPermissions->isNotEmpty()) {
            $modules['Autres'] = $uncategorizedPermissions;
        }

        // Profils prédéfinis des rôles
        $rolePermissions = [
            'admin' => $this->getAdminPermissions(),
            'medecin' => $this->getMedecinPermissions(),
            'comptable' => $this->getComptablePermissions(),
            'secretaire' => $this->getSecretairePermissions(),
        ];

        // Permissions actuelles de l'utilisateur
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        $userRoles = $user->roles->pluck('name')->toArray();

        return view('users.create_permissions', [
            'user' => $user,
            'modules' => $modules,
            'rolePermissions' => $rolePermissions,
            'userPermissions' => $userPermissions,
            'userRoles' => $userRoles,
            'totalPermissions' => $allPermissions->count(),
            'userPermissionsCount' => count($userPermissions),
        ]);
    }

    /**
     * Retourner les permissions du rôle Admin
     */
    private function getAdminPermissions()
    {
        return Permission::all()->pluck('name')->toArray();
    }

    /**
     * Retourner les permissions du rôle Médecin
     */
    private function getMedecinPermissions()
    {
        return [
            'dashboard.view', 'dashboard.medecin',
            'medecin.appointments', 'medecin.confirm_appointment', 'medecin.complete_appointment',
            'medecin.availabilities', 'medecin.leaves',
            'patient.view', 'patient.add_file',
            'consultation.view', 'consultation.create', 'consultation.edit', 'consultation.delete',
            'consultation.facture', 'consultation.ordonnance', 'consultation.medicament', 'consultation.examen',
            'appointment.view',
            'medicament.view',
            'test.view', 'test.status',
            'hospitalisation.view', 'hospitalisation.create', 'hospitalisation.edit',
            'department.view', 'service.view',
            'chambre.view',
            'patient_insurance.view',
        ];
    }

    /**
     * Retourner les permissions du rôle Comptable
     */
    private function getComptablePermissions()
    {
        return [
            'dashboard.view',
            'patient.view',
            'consultation.view', 'consultation.facturer', 'consultation.facture', 'consultation.paiement',
            'account.facture', 'account.payer', 'account.service_report', 'account.opd_report', 'account.package_report',
            'report.view', 'report.actes', 'report.service',
            'hospitalisation.view', 'hospitalisation.payer', 'hospitalisation.facture',
            'package.view', 'package.sale',
            'medicament.view', 'service.view', 'test.view',
            'insurance_company.view', 'insurance_coverage.view',
            'patient_insurance.view', 'patient_insurance.create', 'patient_insurance.edit',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
            'invoice_item.view', 'invoice_item.create', 'invoice_item.edit', 'invoice_item.delete',
            'payment.view', 'payment.process', 'payment.calculate', 'payment.hospitalisation',
            'insurance_balance.view', 'insurance_balance.show', 'insurance_balance.payment', 'insurance_balance.export',
        ];
    }

    /**
     * Retourner les permissions du rôle Secrétaire
     */
    private function getSecretairePermissions()
    {
        return [
            'dashboard.view',
            'patient.view', 'patient.create', 'patient.edit', 'patient.add_file',
            'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete',
            'consultation.view', 'consultation.create',
            'department.view', 'service.view',
            'employee.view', 'employee.profile',
            'medicament.view',
            'package.view', 'package.sale',
            'test.view',
            'hospitalisation.view', 'hospitalisation.create',
            'chambre.view',
            'insurance_company.view', 'insurance_coverage.view', 'patient_insurance.view',
            'payment.view', 'payment.calculate',
        ];
    }



}
