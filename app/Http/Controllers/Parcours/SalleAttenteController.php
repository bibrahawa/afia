<?php

namespace App\Http\Controllers\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Http\Controllers\Controller;
use App\Models\Parcours\Visite;

/**
 * Écran d'affichage de la salle d'attente : patient appelé et prochains
 * passages. Affiché en plein écran sur un téléviseur, rafraîchi tout seul.
 * Les noms sont abrégés (prénom + initiale) : la salle est un lieu public.
 */
class SalleAttenteController extends Controller
{
    public function index()
    {
        $visites = Visite::duJour()->actives()
            ->with(['patient', 'medecin'])
            ->ordreFile()
            ->get();

        return view('parcours.salle-attente.index', [
            'enConsultation' => $visites->where('statut', StatutVisite::EnConsultation)->values(),
            'enAttente' => $visites->where('statut', StatutVisite::EnAttente)->values(),
        ]);
    }
}
