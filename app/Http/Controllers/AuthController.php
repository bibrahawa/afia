<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use \Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Connexion utilisateur avec protection contre les attaques par force brute
     */
    public function loginWithApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ], [
            'phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
        ]);

        // Clé pour la limitation du taux par IP et par utilisateur
        $ipKey = 'login.ip.' . $request->ip();
        $userKey = 'login.user.' . $validated['phone'];

        // Vérification des tentatives par IP (10 tentatives par heure)
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            throw ValidationException::withMessages([
                'phone' => "Trop de tentatives depuis cette adresse IP. Réessayez dans " .
                          gmdate('H:i:s', $seconds) . ".",
            ]);
        }

        // Vérification des tentatives par utilisateur (5 tentatives par heure)
        if (RateLimiter::tooManyAttempts($userKey, 5)) {
            $seconds = RateLimiter::availableIn($userKey);
            throw ValidationException::withMessages([
                'phone' => "Trop de tentatives pour ce compte. Réessayez dans " .
                          gmdate('H:i:s', $seconds) . ".",
            ]);
        }

        // Vérification si l'utilisateur existe et n'est pas verrouillé
        $user = User::where('phone', $validated['phone'])->first();

        // si cet utilisateur est un medecin
        if ($user && !$user->hasRole('patient')) {
            // Logique spécifique pour les médecins
            return response()->json(['message' => 'Les médecins doivent se connecter via l\'interface dédiée.'], 403);
        }

        if ($user && $user->locked_until && Carbon::now()->lt($user->locked_until)) {
            throw ValidationException::withMessages([
                'phone' => 'Ce compte est temporairement verrouillé. Réessayez plus tard.',
            ]);
        }

        // Tentative de connexion
        if (Auth::attempt([
            'phone' => $validated['phone'],
            'password' => $validated['password']
        ], $request->boolean('remember'))) {

            // Connexion réussie
            $user = Auth::user();

            // Mise à jour des informations de connexion
            $user->update([
                'last_login_at' => Carbon::now(),
                'login_attempts' => 0,
                'locked_until' => null,
            ]);


            // Nettoyage des limitations
            RateLimiter::clear($ipKey);
            RateLimiter::clear($userKey);

            // Log de connexion réussie
            Log::info('Connexion réussie', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['message' => 'Login successful', 'patient' => $user->patient], 200);
        }

        // Échec de connexion
        RateLimiter::increment($ipKey, 3600); // 1 heure
        RateLimiter::increment($userKey, 3600); // 1 heure

        // Gestion des tentatives échouées pour l'utilisateur
        if ($user) {
            $user->increment('login_attempts');

            // Verrouillage du compte après 5 tentatives échouées
            if ($user->login_attempts >= 5) {
                $user->update([
                    'locked_until' => Carbon::now()->addMinutes(30), // Verrouillage 30 minutes
                ]);

                Log::warning('Compte verrouillé après tentatives multiples', [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'ip' => $request->ip(),
                ]);
            }
        }

        // Log de tentative de connexion échouée
        Log::warning('Tentative de connexion échouée', [
            'phone' => $validated['phone'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Les informations de connexion sont incorrectes.'], 401);
    }

    /**
     * Enregistrement d'un nouvel utilisateur
     */
    public function register(Request $request): JsonResponse
    {
        // Limitation du taux de tentatives d'enregistrement
        $key = 'register.' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'phone' => "Trop de tentatives d'enregistrement. Réessayez dans {$seconds} secondes.",
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/', 'unique:users,phone'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255'
            ],
        ], [
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password.regex' => 'Le mot de passe doit contenir au moins: 1 minuscule, 1 majuscule, 1 chiffre et 1 caractère spécial.',
        ]);

        try {

            DB::beginTransaction();

            $user = User::create([
                'email' => strtolower(trim(random_int(1000000000, 9999999999) . '@aprosafe.com')), // Email temporaire, à remplacer par un email valide
                'phone' => $validated['phone'],
                'name' => $validated['name'],
                'password' => $validated['password'],
                'last_login_at' => null,
                'login_attempts' => 0,
                'locked_until' => null,
            ]);

            //Create Patient Profile
            $patient = Patient::create([
                'user_id' => $user->id,
                'first_name' => $user->name,
                'last_name' => '',
                'age' => 0,
                'marital_status' => '',
            ]);

            // Connexion automatique après enregistrement
            Auth::login($user);

            // Assign the 'patient' role to the user
            $user->assignRole('patient');

            DB::commit();

            // Return a response with the patient profile
            return response()->json(['message' => 'Registration successful', 'patient' => $patient], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::increment($key);
            Log::error('Erreur lors de l\'enregistrement', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Une erreur est survenue lors de la création du compte.'], 500);
        }
    }

    /**
     * Connexion utilisateur avec protection contre les attaques par force brute
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ], [
            'phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
        ]);

        // Clé pour la limitation du taux par IP et par utilisateur
        $ipKey = 'login.ip.' . $request->ip();
        $userKey = 'login.user.' . $validated['phone'];

        // Vérification des tentatives par IP (10 tentatives par heure)
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            throw ValidationException::withMessages([
                'phone' => "Trop de tentatives depuis cette adresse IP. Réessayez dans " .
                          gmdate('H:i:s', $seconds) . ".",
            ]);
        }

        // Vérification des tentatives par utilisateur (5 tentatives par heure)
        if (RateLimiter::tooManyAttempts($userKey, 5)) {
            $seconds = RateLimiter::availableIn($userKey);
            throw ValidationException::withMessages([
                'phone' => "Trop de tentatives pour ce compte. Réessayez dans " .
                          gmdate('H:i:s', $seconds) . ".",
            ]);
        }

        // Vérification si l'utilisateur existe et n'est pas verrouillé
        $user = User::where('phone', $validated['phone'])->first();

        if ($user && $user->locked_until && Carbon::now()->lt($user->locked_until)) {
            throw ValidationException::withMessages([
                'phone' => 'Ce compte est temporairement verrouillé. Réessayez plus tard.',
            ]);
        }

        // Tentative de connexion
        if (Auth::attempt([
            'phone' => $validated['phone'],
            'password' => $validated['password']
        ], $request->boolean('remember'))) {

            // Connexion réussie
            $user = Auth::user();

            // Mise à jour des informations de connexion
            $user->update([
                'last_login_at' => Carbon::now(),
                'login_attempts' => 0,
                'locked_until' => null,
            ]);

            // Régénération de la session
            $request->session()->regenerate();

            // Nettoyage des limitations
            RateLimiter::clear($ipKey);
            RateLimiter::clear($userKey);

            // Log de connexion réussie
            Log::info('Connexion réussie', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended('home');
        }

        // Échec de connexion
        RateLimiter::increment($ipKey, 3600); // 1 heure
        RateLimiter::increment($userKey, 3600); // 1 heure

        // Gestion des tentatives échouées pour l'utilisateur
        if ($user) {
            $user->increment('login_attempts');

            // Verrouillage du compte après 5 tentatives échouées
            if ($user->login_attempts >= 5) {
                $user->update([
                    'locked_until' => Carbon::now()->addMinutes(30), // Verrouillage 30 minutes
                ]);

                Log::warning('Compte verrouillé après tentatives multiples', [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'ip' => $request->ip(),
                ]);
            }
        }

        // Log de tentative de connexion échouée
        Log::warning('Tentative de connexion échouée', [
            'phone' => $validated['phone'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->withInput($request->only('phone'))
                    ->withErrors(['phone' => 'Les informations de connexion sont incorrectes.']);
    }

    /**
     * Déconnexion sécurisée
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Log de déconnexion
        if ($user) {
            Log::info('Déconnexion utilisateur', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
        }

        // Déconnexion
        Auth::logout();

        // Invalidation complète de la session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Vous avez été déconnecté avec succès.');
    }

    /**
     * Déblocage d'un compte (pour les administrateurs)
     */
    public function unlockAccount(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
        ]);

        $user = User::where('phone', $validated['phone'])->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'Utilisateur non trouvé.']);
        }

        $user->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);

        // Nettoyage des limitations
        RateLimiter::clear('login.user.' . $validated['phone']);

        Log::info('Compte débloqué par administrateur', [
            'user_id' => $user->id,
            'admin_ip' => $request->ip(),
        ]);

        return back()->with('success', 'Compte débloqué avec succès.');
    }
}
