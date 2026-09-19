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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        // ============================================
        // VALIDATION
        // ============================================
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|min:8|max:15|unique:users,phone',
            'department_id' => 'nullable|exists_etablissement:departments,id',
            'role_id' => ['required', 'string', 'exists:roles,name', \Illuminate\Validation\Rule::notIn([\App\Support\EtablissementContext::ROLE_PLATEFORME])],
            'address' => 'nullable|string|max:500',
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
            'working_day' => 'nullable|array',
        ], [
            // Messages personnalisés en français
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'phone.required' => 'Le téléphone est obligatoire.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'role_id.required' => 'Le rôle est obligatoire.',
            'role_id.exists' => 'Le rôle sélectionné n\'existe pas.',
            'department_id.exists' => 'Le département sélectionné n\'existe pas.',
        ]);

        // ============================================
        // TRANSACTION DATABASE
        // ============================================
        try {
            DB::beginTransaction();

            // Traitement des jours de travail
            $workingDays = null;
            if ($request->has('working_day') && is_array($request->working_day)) {
                $workingDays = implode(',', $request->working_day);
            }

            // CORRIGÉ — le titre était ajouté au prénom à CHAQUE enregistrement :
            // une fiche modifiée devenait « Dr Dr Alpha », puis « Dr Dr Dr Alpha ».
            // La fiche employé garde le prénom seul (Employee::nom_affiche ajoute
            // « Dr » à l'affichage) ; le nom du compte utilisateur, affiché dans la
            // barre du haut, reçoit le titre une seule fois.
            $firstName = preg_replace('/^\s*(dr\.?|docteur)\s+/iu', '', trim($validated['first_name']));
            $nomCompte = ($validated['role_id'] === 'medecin' ? 'Dr ' : '') . $firstName;

            // ============================================
            // CRÉATION DE L'UTILISATEUR
            // ============================================
            $user = User::create([
                'name' => $nomCompte . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
            ]);
            // Hors $fillable volontairement : l'établissement n'est jamais choisi par le formulaire.
            $user->forceFill(['etablissement_id' => \App\Support\EtablissementContext::id()])->save();

            $departementId = $validated['department_id'] ?? Department::orderBy('id')->value('id');

            // ============================================
            // CRÉATION DE L'EMPLOYÉ
            // ============================================
            Employee::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'department_id' => $departementId,
                'address' => $validated['address'] ?? null,
                'working_day' => $workingDays,
                'speciality' => Department::find($departementId)?->name,
                'type' => $validated['role_id'],
            ]);

            // ============================================
            // ASSIGNATION DU RÔLE
            // ============================================
            $user->assignRole($validated['role_id']);

            DB::commit();

            // ============================================
            // LOGS (Optionnel)
            // ============================================
            \Log::info('Nouvel utilisateur créé', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $validated['role_id'],
                'created_by' => auth()->id(),
            ]);

            return redirect()
                ->route('users.index')
                ->with('success', 'Utilisateur créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Log de l'erreur
            \Log::error('Erreur lors de la création d\'un utilisateur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création de l\'utilisateur.');
        }
    }

    /**
     * Afficher la liste des utilisateurs
     */
    public function index()
    {
        $users = User::deMonEtablissement()->with(['employee.department', 'roles'])
            ->latest()
            ->get();

        return view('users.index', compact('users'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::all();
        $departments = \App\Models\Department::all();

        return view('users.create', compact('roles', 'departments'));
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        $user = User::deMonEtablissement()->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|string|min:8|max:15|unique:users,phone,' . $id,
            'department_id' => 'nullable|exists_etablissement:departments,id',
            'role_id' => ['required', 'string', 'exists:roles,name', \Illuminate\Validation\Rule::notIn([\App\Support\EtablissementContext::ROLE_PLATEFORME])],
            'address' => 'nullable|string|max:500',
            'password' => ['nullable', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ]);

        try {
            DB::beginTransaction();

            // CORRIGÉ — le titre était ajouté au prénom à CHAQUE enregistrement :
            // une fiche modifiée devenait « Dr Dr Alpha », puis « Dr Dr Dr Alpha ».
            // La fiche employé garde le prénom seul (Employee::nom_affiche ajoute
            // « Dr » à l'affichage) ; le nom du compte utilisateur, affiché dans la
            // barre du haut, reçoit le titre une seule fois.
            $firstName = preg_replace('/^\s*(dr\.?|docteur)\s+/iu', '', trim($validated['first_name']));
            $nomCompte = ($validated['role_id'] === 'medecin' ? 'Dr ' : '') . $firstName;

            // Mise à jour utilisateur
            $user->update([
                'name' => $nomCompte . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);

            // Mise à jour du mot de passe si fourni
            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }

            // Mise à jour employé
            $user->employee->update([
                'first_name' => $firstName,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'department_id' => $validated['department_id'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            // Mise à jour du rôle
            $user->syncRoles([$validated['role_id']]);

            DB::commit();

            return redirect()
                ->route('users.index')
                ->with('success', 'Utilisateur mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la mise à jour d\'un utilisateur', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        try {
            $user = User::deMonEtablissement()->findOrFail($id);

            // Empêcher la suppression de son propre compte
            if ($user->id === auth()->id()) {
                return redirect()
                    ->back()
                    ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            }

            DB::beginTransaction();

            // Supprimer l'employé associé
            $user->employee()->delete();

            // Supprimer l'utilisateur
            $user->delete();

            DB::commit();

            \Log::info('Utilisateur supprimé', [
                'user_id' => $id,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()
                ->route('users.index')
                ->with('success', 'Utilisateur supprimé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la suppression d\'un utilisateur', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    public function disableUser($id)
    {
       $user = User::deMonEtablissement()->findOrFail($id);

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

    /**
     * CORRIGÉ — un administrateur de clinique pouvait s'attribuer (ou attribuer)
     * n'importe quelle permission ou rôle, y compris ceux de la plateforme
     * (etablissement.*, module.*, super-admin) : escalade de privilèges.
     *
     * Règle : on ne peut donner que ce que l'on possède soi-même. Seul
     * l'administrateur plateforme peut tout attribuer. Les éléments refusés
     * sont signalés, jamais appliqués en silence.
     */
    public function assignPermissions(Request $request, $id)
    {
        $user = User::deMonEtablissement()->findOrFail($id);
        $acteur = $request->user();
        $plateforme = \App\Support\EtablissementContext::estAdministrateurPlateforme($acteur);

        $demandees = collect($request->input('permissions', []))->filter()->unique()->values();
        $autorisees = $plateforme
            ? $demandees
            : $demandees->filter(fn ($p) => $acteur->can($p) && ! str_starts_with($p, 'etablissement.') && ! str_starts_with($p, 'module.'));
        $refusees = $demandees->diff($autorisees);

        $rolesRefuses = collect();
        $roles = null;
        if ($request->has('roles')) {
            $rolesDemandes = collect($request->input('roles', []))->filter()->unique();
            $roles = $plateforme ? $rolesDemandes : $rolesDemandes->filter(function ($nom) use ($acteur) {
                if ($nom === \App\Support\EtablissementContext::ROLE_PLATEFORME) {
                    return false;
                }
                $role = Role::where('name', $nom)->first();

                // Un rôle n'est attribuable que si l'acteur a déjà toutes ses permissions.
                return $role && $role->permissions->every(fn ($perm) => $acteur->can($perm->name));
            });
            $rolesRefuses = $rolesDemandes->diff($roles);
        }

        if ($user->is($acteur) && ! $plateforme && $demandees->isNotEmpty()) {
            // Personne ne modifie ses propres droits (hors plateforme) : un autre administrateur doit le faire.
            return redirect()->back()->with('error', 'Vous ne pouvez pas modifier vos propres permissions.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($user, $autorisees, $roles) {
                $user->syncPermissions($autorisees->all());
                if ($roles !== null) {
                    $user->syncRoles($roles->all());
                }
            });

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            \App\Models\ActivityLog::create([
                'etablissement_id' => \App\Support\EtablissementContext::id(),
                'causer_type' => User::class, 'causer_id' => $acteur->id,
                'subject_type' => User::class, 'subject_id' => $user->id,
                'action' => 'users.permissions_modifiees',
                'description' => $autorisees->count() . ' permission(s) attribuée(s)',
                'proprietes' => ['permissions' => $autorisees->all(), 'roles' => $roles?->all(), 'refusees' => $refusees->merge($rolesRefuses)->all()],
                'ip_address' => $request->ip(),
            ]);

            $message = 'Les permissions de ' . $user->name . ' ont été mises à jour (' . $autorisees->count() . ' permissions assignées).';
            $redirection = redirect()->route('users.index')->with('success', $message);

            return $refusees->isEmpty() && $rolesRefuses->isEmpty()
                ? $redirection
                : $redirection->with('error', 'Non attribués (vous ne les possédez pas ou ils sont réservés à la plateforme) : '
                    . $refusees->merge($rolesRefuses)->implode(', '));
        } catch (\Exception $e) {
            \Log::error('Erreur assignPermissions: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour des permissions.')
                ->withInput();
        }
    }

    public function listePermissions($id) 
    {
        $user = User::deMonEtablissement()->findOrFail($id);
        
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
