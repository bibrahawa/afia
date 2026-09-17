<?php

namespace App\Http\Controllers\Assurance;

use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use App\Services\Assurance\ReclamationService;
use Illuminate\Http\Request;

class ReclamationController extends Controller
{
    public function __construct(private ReclamationService $reclamations)
    {
    }

    public function show(InsuranceClaim $insuranceClaim)
    {
        $insuranceClaim->load([
            'lignes', 'insuranceCompany', 'bordereau', 'invoice.transaction.patient',
            'patientInsurance.beneficiaire.adhesion.patient', 'settlementItems.settlement', 'settlementItems.paiement', 'pieces.auteur',
        ]);

        return view('assurance.reclamations.show', ['reclamation' => $insuranceClaim]);
    }

    public function repondre(Request $request, InsuranceClaim $insuranceClaim)
    {
        $donnees = $request->validate([
            'lignes' => ['array'],
            'lignes.*.montant_accepte' => ['nullable', 'numeric', 'min:0'],
            'lignes.*.motif_rejet' => ['nullable', 'string', 'max:255'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->reclamations->enregistrerReponse($insuranceClaim, $donnees['lignes'] ?? [], $donnees['commentaire'] ?? null);

        return back()->with('success', 'Réponse de l\'assureur enregistrée.');
    }

    public function transferer(InsuranceClaim $insuranceClaim)
    {
        $reclamation = $this->reclamations->transfererEcartAuPatient($insuranceClaim);

        return back()->with('success', 'Écart de ' . number_format((float) $reclamation->montant_transfere_patient, 0, ',', ' ') . ' GNF remis à la charge du patient.');
    }
}
