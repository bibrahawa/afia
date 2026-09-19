<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

/**
 * Lot R2 — « Mot de passe oublié ? » pour le personnel, par code SMS.
 *
 * Même principe que la connexion du portail patient :
 *  - réponse identique que le numéro existe ou non (pas de révélation des comptes) ;
 *  - code à 6 chiffres, haché, valable 10 minutes, jamais en clair au journal des SMS ;
 *  - 3 envois par numéro / 15 min, 5 essais de code par numéro / 15 min ;
 *  - un compte suspendu ne reçoit pas de code.
 */
class MotDePasseOublieController extends Controller
{
    private const VALIDITE_MINUTES = 10;

    public function demande()
    {
        return view('auth.mot-de-passe-oublie', ['etape' => 'telephone']);
    }

    public function envoyer(Request $request, OtpService $otp, SmsService $sms)
    {
        $telephone = $request->validate(['phone' => ['required', 'regex:/^[0-9]{9}$/']],
            ['phone.required' => 'Indiquez votre numéro de téléphone.', 'phone.regex' => 'Le numéro doit contenir 9 chiffres.'])['phone'];

        $cle = 'reinit-mdp-envoi:' . $telephone;
        if (RateLimiter::tooManyAttempts($cle, 3)) {
            return back()->withInput()->withErrors(['phone' => 'Trop de demandes pour ce numéro. Réessayez dans ' . ceil(RateLimiter::availableIn($cle) / 60) . ' minutes.']);
        }
        RateLimiter::hit($cle, 900);

        $utilisateur = User::where('phone', $telephone)->first();
        $actif = $utilisateur && ($utilisateur->status === null || (bool) $utilisateur->status);

        if ($actif) {
            $code = $otp->generer();
            Cache::put($this->cleCode($telephone), $otp->hacher($code), now()->addMinutes(self::VALIDITE_MINUTES));
            $sms->sendSms($telephone,
                "Hali : votre code pour changer de mot de passe est {$code} (valable " . self::VALIDITE_MINUTES . " min). Si vous n'avez rien demande, ignorez ce message.",
                ['type' => 'reinitialisation_mdp', 'masquer' => true, 'etablissement' => $utilisateur->etablissement_id]);
        }

        // Même suite dans tous les cas : on ne révèle pas si le numéro a un compte.
        $request->session()->put('reinit_mdp_telephone', $telephone);

        return redirect()->route('password.code');
    }

    public function formulaireCode(Request $request)
    {
        $telephone = $request->session()->get('reinit_mdp_telephone');
        if (! $telephone) {
            return redirect()->route('password.request');
        }

        return view('auth.mot-de-passe-oublie', ['etape' => 'code', 'telephone' => $telephone]);
    }

    public function reinitialiser(Request $request, OtpService $otp)
    {
        $telephone = $request->session()->get('reinit_mdp_telephone');
        if (! $telephone) {
            return redirect()->route('password.request');
        }

        $donnees = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'code.required' => 'Saisissez le code reçu par SMS.',
            'code.digits' => 'Le code contient 6 chiffres.',
            'password.confirmed' => 'Les deux saisies du mot de passe ne correspondent pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.letters' => 'Le mot de passe doit contenir au moins une lettre.',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
        ]);

        $cleEssais = 'reinit-mdp-essais:' . $telephone;
        if (RateLimiter::tooManyAttempts($cleEssais, 5)) {
            return back()->withErrors(['code' => 'Trop d\'essais. Demandez un nouveau code dans ' . ceil(RateLimiter::availableIn($cleEssais) / 60) . ' minutes.']);
        }

        $hache = Cache::get($this->cleCode($telephone));
        $utilisateur = User::where('phone', $telephone)->first();

        if (! $hache || ! $utilisateur || ! $otp->verifier($donnees['code'], $hache)) {
            RateLimiter::hit($cleEssais, 900);

            return back()->withErrors(['code' => 'Code incorrect ou expiré.']);
        }

        Cache::forget($this->cleCode($telephone));
        RateLimiter::clear($cleEssais);
        $request->session()->forget('reinit_mdp_telephone');

        $utilisateur->forceFill([
            'password' => Hash::make($donnees['password']),
            'doit_changer_mot_de_passe' => false,
            'login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        try {
            ActivityLog::create([
                'etablissement_id' => $utilisateur->etablissement_id,
                'causer_type' => User::class, 'causer_id' => $utilisateur->id,
                'subject_type' => User::class, 'subject_id' => $utilisateur->id,
                'action' => 'utilisateur.mot_de_passe_reinitialise',
                'description' => 'Mot de passe réinitialisé par code SMS',
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable) {
        }

        return redirect()->to(url('/login'))->with('status', 'Mot de passe modifié. Connectez-vous avec votre nouveau mot de passe.');
    }

    private function cleCode(string $telephone): string
    {
        return 'reinit-mdp-code:' . $telephone;
    }
}
