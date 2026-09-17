<?php

namespace App\Http\Controllers\Assurance;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Models\InsuranceSettlement;
use App\Services\Assurance\ReglementAssuranceService;
use Illuminate\Http\Request;

class ReglementController extends Controller
{
    public function store(Request $request, InsuranceCompany $assuranceOrganisme, ReglementAssuranceService $reglements)
    {
        $donnees = $request->validate([
            'montant_recu' => ['required', 'numeric', 'min:0'],
            'mode' => ['required', 'in:automatique,manuel'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'imputations' => ['nullable', 'array'],
            'imputations.*.paye' => ['nullable', 'numeric', 'min:0'],
            'imputations.*.ecart' => ['nullable', 'numeric', 'min:0'],
        ], ['montant_recu.required' => 'Indiquez le montant reçu de l\'organisme.']);

        $imputations = [];
        if ($donnees['mode'] === 'manuel') {
            $imputations = array_filter($donnees['imputations'] ?? [], fn ($i) => (float) ($i['paye'] ?? 0) > 0 || (float) ($i['ecart'] ?? 0) > 0);

            if (! $imputations) {
                throw new \App\Exceptions\Assurance\OperationAssuranceImpossible('Imputation manuelle : saisissez au moins un montant payé ou un écart.');
            }
        }

        $reglement = $reglements->regler($assuranceOrganisme, (float) $donnees['montant_recu'], $imputations, $donnees);

        return redirect()->route('assurance.reglements.show', $reglement)->with('success', 'Règlement ' . $reglement->settlement_no . ' enregistré.');
    }

    public function show(InsuranceSettlement $insuranceSettlement)
    {
        $insuranceSettlement->load(['insuranceCompany', 'items.reclamation.invoice.transaction.patient', 'items.paiement']);

        return view('assurance.reglements.show', ['reglement' => $insuranceSettlement]);
    }
}
