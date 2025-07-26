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

    public function listePermissions($id)
    {
        $user = User::find($id);
        $permissions = Permission::all();
        return view('users.create_permissions', compact('user', 'permissions'));
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
}
