<?php

namespace App\Http\Controllers;

use App\Models\ComptePatient;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class PatientAuthController extends Controller
{
    public function afficherConnexion()
    {
        return view('patient-auth.connexion');
    }

    public function envoyerCode(Request $request, SmsService $sms, OtpService $otp)
    {
        $data = $request->validate(['telephone' => ['required', 'regex:/^[0-9]{9}$/']]);

        // Limite ANCRÉE SUR LE NUMÉRO, pas sur l'IP — voir l'analyse :
        // un throttle par IP seul est contournable (rotation de connexion
        // mobile) et pénalise à tort des patients qui partagent une même
        // IP opérateur, fréquent sur les réseaux mobiles guinéens. Le vrai
        // coût à protéger, c'est l'envoi de SMS par numéro.
        $cle = 'otp-connexion:' . $data['telephone'];
        if (RateLimiter::tooManyAttempts($cle, 3)) {
            return response()->json([
                'error' => 'Trop de tentatives pour ce numéro. Réessayez dans quelques minutes.'
            ], 429);
        }
        RateLimiter::hit($cle, 600); // 3 essais / 10 minutes / numéro

        $compte = ComptePatient::where('telephone', $data['telephone'])->first();

        // Réponse volontairement identique, compte existant ou non — ne
        // jamais révéler par la réponse si un numéro est enregistré.
        if ($compte) {
            $code = $otp->generer();
            $compte->update([
                'code_otp' => $otp->hacher($code),
                'otp_expire_le' => now()->addMinutes(5),
            ]);

            $sms->sendSms($compte->telephone, "Votre code de connexion : {$code} (valable 5 minutes).");
        }

        return response()->json(['message' => 'Si ce numéro est enregistré, un code vient de vous être envoyé.']);
    }

    public function verifierCode(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'],
            'code' => ['required', 'string'],
        ]);

        $cle = 'otp-verif:' . $data['telephone'];
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            return response()->json(['error' => 'Trop de tentatives. Redemandez un nouveau code.'], 429);
        }

        $compte = ComptePatient::where('telephone', $data['telephone'])->first();

        if (! $compte || ! $compte->otp_expire_le || $compte->otp_expire_le->isPast()
            || ! $otp->verifier($data['code'], $compte->code_otp)) {
            RateLimiter::hit($cle, 600);
            return response()->json(['error' => 'Code invalide ou expiré.'], 422);
        }

        RateLimiter::clear($cle);

        $compte->update([
            'code_otp' => null,
            'otp_expire_le' => null,
            'telephone_verifie_le' => $compte->telephone_verifie_le ?: now(),
        ]);

        Auth::guard('patient')->login($compte, remember: true);

        return response()->json(['redirect' => route('portail.index')]);
    }

    public function deconnexion(Request $request)
    {
        Auth::guard('patient')->logout();
        $request->session()->invalidate();

        return redirect()->route('patient-auth.connexion');
    }
}
