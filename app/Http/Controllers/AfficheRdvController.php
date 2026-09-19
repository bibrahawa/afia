<?php

namespace App\Http\Controllers;

use App\Support\Etablissement\IdentiteDocument;
use App\Support\EtablissementContext;

/**
 * Lot S2 — Affiche et cartes « Prenez rendez-vous en ligne » avec QR code.
 * La prise de rendez-vous en ligne n'est utile que si les patients trouvent la page :
 * affiche en salle d'attente, cartes à l'accueil, lien à partager sur WhatsApp.
 */
class AfficheRdvController extends Controller
{
    public function __invoke()
    {
        $etablissement = EtablissementContext::current();
        abort_unless($etablissement?->slug, 404, 'Aucune clinique rattachée à ce compte.');

        $identite = IdentiteDocument::pour($etablissement);
        $lien = route('rdv', ['etablissement' => $etablissement->slug]);

        return view('appointments.affiche', compact('etablissement', 'identite', 'lien'));
    }
}
