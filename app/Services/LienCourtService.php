<?php

namespace App\Services;

use App\Models\LienCourt;
use Carbon\Carbon;

class LienCourtService
{
    /**
     * Code court en base62 (chiffres + lettres, casse mixte) — 7
     * caractères donnent plus de 3,5 milliards de combinaisons, largement
     * assez pour ne jamais avoir de collision gênante à l'échelle visée,
     * avec une nouvelle tentative en cas de doublon improbable.
     */
    public function creer(string $urlCible, ?Carbon $expiration = null): string
    {
        do {
            $code = substr(str_shuffle('abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 7);
        } while (LienCourt::where('code', $code)->exists());

        LienCourt::create([
            'code' => $code,
            'url_cible' => $urlCible,
            'expire_le' => $expiration,
        ]);

        return url('/l/' . $code);
    }
}
