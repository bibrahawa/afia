<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Méthode d'enregistrement
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    // Méthode de connexion
    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:10',
            'password' => 'required|string|min:8',
        ]);

        if (Auth::attempt(['name' => $request->name, 'password' => $request->password])) {
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['name' => 'Les informations de connexion sont incorrectes.']);
    }

    // Méthode de déconnexion
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

