<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
// use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    // use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('guest')->except('logout');
    }

    public function username()
    {
        return 'name';
    }

    public function login(Request $request){

        $credentials = $request->only('name', 'password');

        if ($request->missing(['name', 'password'])) {
            return response()->json(['message' => 'Name and password are required'], 422);
        }

        if (User::where('name', $credentials['name'])->doesntExist()) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Attempt to log the user in

        if (auth()->attempt($credentials)) {
            return response()->json(['message' => 'Login successful', 'patient' => auth()->user()->patient], 200);
        }else {
            // If authentication fails, return an error response
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

    }

    public function register(Request $request)
    {
        // Registration logic here
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        //Create a new user        // Check if user already exists
        if (User::where('name', $data['name'])->exists()) {
            return response()->json(['message' => 'User already exists'], 409);
        }

        // Create the user
        $user = User::create([
            'name' => $data['name'],
            'password' => $data['password'],
            'email' => rand(100000, 999999) . '@aprosafe.com',
        ]);

        //Create Patient Profile
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => $request->name,
            'last_name' => 'NA',
            'age' => 0,
            // 'gender' => 'Marie',
            'marital_status' => 'NA',
        ]);

        // Assign the 'patiente' role to the user
        $user->assignRole('patient');

        // Optionally, you can log the user in after registration
        auth()->login($user);
        // Return a response with the patient profile
        return response()->json(['message' => 'Registration successful', 'patient' => $patient], 201);
    }
}
