<?php

namespace App\Http\Controllers;

use App\Models\LienCourt;

class LienCourtController extends Controller
{
    public function rediriger(string $code)
    {
        $lien = LienCourt::where('code', $code)->first();

        if (! $lien || $lien->estExpire()) {
            abort(404, 'Ce lien a expiré ou est invalide.');
        }

        // Traçabilité légère : utile pour distinguer plus tard "jamais
        // ouvert" de "ouvert mais action non poursuivie", sans bloquer une
        // réutilisation (un lien de confirmation peut être ouvert
        // plusieurs fois avant que l'action ne soit faite).
        if (! $lien->utilise_le) {
            $lien->update(['utilise_le' => now()]);
        }

        return redirect($lien->url_cible);
    }
}
