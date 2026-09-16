<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

/**
 * Avant ce service, deux standards différents coexistaient : le code de
 * connexion (PatientAuthController) était haché, le code de confirmation
 * de consentement (AccesDossierSanteService) restait en clair en base.
 * Un seul mécanisme désormais, utilisé partout où un code à usage unique
 * est envoyé par SMS.
 */
class OtpService
{
    public function generer(int $chiffres = 6): string
    {
        $min = (int) str_pad('1', $chiffres, '0');
        $max = (int) str_pad('9', $chiffres, '9');

        return (string) random_int($min, $max);
    }

    public function hacher(string $code): string
    {
        return Hash::make($code);
    }

    public function verifier(string $code, ?string $hash): bool
    {
        return $hash && Hash::check($code, $hash);
    }
}
