<?php

namespace App\Http\Controllers\Facturation;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Services\Facturation\AnnulationPaiementService;
use Illuminate\Http\Request;

class AnnulationPaiementController extends Controller
{
    /**
     * {paiement} est résolu via le global scope d'établissement : un paiement
     * d'une autre clinique donne un 404. Les erreurs métier
     * (OperationFacturationImpossible) reviennent à la page précédente avec
     * leur message (FacturationServiceProvider).
     */
    public function __invoke(Request $request, Paiement $paiement, AnnulationPaiementService $annulations)
    {
        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'motif.required' => "Le motif de l'annulation est obligatoire.",
            'motif.min' => "Le motif de l'annulation doit faire au moins 5 caractères.",
        ]);

        $paiement = $annulations->annuler($paiement, $donnees['motif'], $request->user());

        return back()->with('success', sprintf(
            'Paiement %s de %s GNF annulé. Le montant est de nouveau dû.',
            $paiement->paiement_no,
            number_format((float) $paiement->montant, 0, ',', ' ')
        ));
    }
}
