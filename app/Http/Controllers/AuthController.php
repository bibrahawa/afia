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
            'password' => ['required', 'string', 'min:4', 'max:255'],
        ], [
            'phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
            'password.min' => 'Le mot de passe doit contenir au moins 4 caractères.',
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

        // Si cet utilisateur est un médecin
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

            return response()->json([
                'message' => 'Login successful',
                'patient' => $user->patient
            ], 200);
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

        return response()->json([
            'message' => 'Les informations de connexion sont incorrectes.'
        ], 401);
    }

    public function findOrCreate(Request $request): JsonResponse
    {
        $ipKey = 'findOrCreate.ip.' . $request->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return response()->json([
                'error' => true,
                'message' => "Trop de tentatives. Réessayez dans " . gmdate('H:i:s', $seconds) . "."
            ], 429);
        }

        $normalizedPhone = preg_replace('/\D/', '', $request->phone ?? '');

        $validated = validator(
            [
                'phone' => $normalizedPhone,
                'name' => $request->name,
            ],
            [
                'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
                'name' => ['nullable', 'string', 'min:2', 'max:100'],
            ],
            [
                'phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
                'name.min' => 'Le nom doit contenir au moins 2 caractères.',
                'name.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            ]
        )->validate();

        try {

            $user = User::where('phone', $validated['phone'])->first();

            if ($user) {
                if ($user->locked_until && Carbon::now()->lt($user->locked_until)) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Ce compte est temporairement verrouillé. Réessayez plus tard.'
                    ], 403);
                }

                $patient = $user->patient;

                if (!$patient) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Ce compte n\'est pas un compte patient.'
                    ], 403);
                }

                $user->update(['last_login_at' => Carbon::now()]);
                RateLimiter::clear($ipKey);

                Log::info('Patient trouvé via findOrCreate', [
                    'user_id' => $user->id,
                    'patient_id' => $patient->id,
                    'phone' => $user->phone,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => false,
                    'exists' => true,
                    'message' => 'Patient trouvé',
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'first_name' => $patient->first_name,
                        'last_name' => $patient->last_name,
                    ],
                    'is_new' => false
                ], 200);
            }

            $result = DB::transaction(function () use ($validated) {
                $generatedPassword = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

                $providedName = $validated['name'] ?? null;
                $nameParts = $providedName
                    ? explode(' ', trim($providedName), 2)
                    : ['Patient', substr($validated['phone'], -4)];

                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? substr($validated['phone'], -4);

                $user = User::create([
                    'email' => strtolower(trim(random_int(1000000000, 9999999999) . '@aprosafe.com')),
                    'phone' => $validated['phone'],
                    'name' => $providedName ?? 'Patient ' . substr($validated['phone'], -4),
                    'password' => Hash::make($generatedPassword),
                    'last_login_at' => Carbon::now(),
                    'login_attempts' => 0,
                    'locked_until' => null,
                ]);

                $patient = Patient::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'age' => 0,
                    'marital_status' => '',
                ]);

                $user->assignRole('patient');

                return compact('user', 'patient');
            });

            RateLimiter::clear($ipKey);

            Log::info('Nouveau patient créé via findOrCreate', [
                'user_id' => $result['user']->id,
                'patient_id' => $result['patient']->id,
                'phone' => $result['user']->phone,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => false,
                'exists' => false,
                'message' => 'Nouveau compte créé',
                'patient' => [
                    'id' => $result['patient']->id,
                    'name' => $result['user']->name,
                    'phone' => $result['user']->phone,
                    'first_name' => $result['patient']->first_name,
                    'last_name' => $result['patient']->last_name,
                ],
                'is_new' => true,
            ], 201);

        } catch (\Exception $e) {
            RateLimiter::increment($ipKey, 3600);

            Log::error('Erreur findOrCreate', [
                'error' => $e->getMessage(),
                'phone' => $validated['phone'] ?? null,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Une erreur est survenue lors du traitement.'
            ], 500);
        }
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
                'min:4',
                'max:255'
            ],
        ], [
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
            'password.min' => 'Le mot de passe doit contenir au moins 4 caractères.',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'email' => strtolower(trim(random_int(1000000000, 9999999999) . '@aprosafe.com')), // Email temporaire
                'phone' => $validated['phone'],
                'name' => $validated['name'],
                'password' => $validated['password'], // Le mutateur de User va hasher automatiquement
                'last_login_at' => null,
                'login_attempts' => 0,
                'locked_until' => null,
            ]);

            // Create Patient Profile
            $patient = Patient::create([
                'user_id' => $user->id,
                'first_name' => $user->name,
                'last_name' => '',
                'age' => 0,
                'marital_status' => '',
            ]);

            // Assign the 'patient' role to the user
            $user->assignRole('patient');

            // Connexion automatique après enregistrement
            Auth::login($user);

            DB::commit();

            // Nettoyage du rate limiter
            RateLimiter::clear($key);

            // Log d'enregistrement réussi
            Log::info('Nouvel utilisateur enregistré', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'ip' => $request->ip(),
            ]);

            // Return a response with the patient profile
            return response()->json([
                'message' => 'Registration successful',
                'patient' => $patient
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::increment($key);
            
            Log::error('Erreur lors de l\'enregistrement', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du compte.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Connexion utilisateur (interface web) avec protection contre les attaques par force brute
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'password' => ['required', 'string', 'min:4', 'max:255'],
        ], [
            'phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
            'password.min' => 'Le mot de passe doit contenir au moins 4 caractères.',
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

            // Rôles sans tableau de bord général (laboratoire) : page adaptée
            // au lieu d'un 403 juste après la connexion.
            if (\App\Support\PageAccueil::aUneAutrePage($user)) {
                return redirect()->to(\App\Support\PageAccueil::url($user));
            }

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

        return redirect('/login')->with('success', 'Vous avez été déconnecté avec succès.');
    }

    /**
     * Déconnexion API
     */
    public function logoutApi(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Log de déconnexion
        if ($user) {
            Log::info('Déconnexion utilisateur API', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
        }

        // Déconnexion
        Auth::logout();

        // Invalidation de la session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ], 200);
    }

    /**
     * Déblocage d'un compte (pour les administrateurs)
     */
    public function unlockAccount(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
        ], [
            'phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
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
        RateLimiter::clear('login.ip.' . $request->ip());

        Log::info('Compte débloqué par administrateur', [
            'user_id' => $user->id,
            'admin_id' => Auth::id(),
            'admin_ip' => $request->ip(),
        ]);

        return back()->with('success', 'Compte débloqué avec succès.');
    }

    /**
     * Vérifier le statut du compte
     */
    public function checkAccountStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
        ]);

        $user = User::where('phone', $validated['phone'])->first();

        if (!$user) {
            return response()->json([
                'exists' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $isLocked = $user->locked_until && Carbon::now()->lt($user->locked_until);

        return response()->json([
            'exists' => true,
            'is_locked' => $isLocked,
            'locked_until' => $isLocked ? $user->locked_until->toIso8601String() : null,
            'login_attempts' => $user->login_attempts,
        ], 200);
    }
}