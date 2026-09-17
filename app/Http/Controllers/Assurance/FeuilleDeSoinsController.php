<?php

namespace App\Http\Controllers\Assurance;

use App\Http\Controllers\Controller;
use App\Models\Assurance\PriseEnCharge;
use App\Models\Transaction;
use App\Support\Etablissement\IdentiteDocument;

/**
 * Feuille de soins à faire signer par l'assuré (et le médecin) : c'est la
 * pièce que les assureurs exigent pour accepter une réclamation. À imprimer
 * au passage en caisse, puis à joindre à la réclamation une fois signée.
 */
class FeuilleDeSoinsController extends Controller
{
    public function show(int $transactionId)
    {
        $transaction = Transaction::with([
            'patient',
            'transactionable',
            'invoice.items',
            'invoice.insuranceClaims.insuranceCompany',
            'invoice.insuranceClaims.patientInsurance.beneficiaire.adhesion.patient',
            'invoice.insuranceClaims.patientInsurance.beneficiaire.adhesion.formule.contrat.entreprise',
        ])->findOrFail($transactionId);

        abort_if(! $transaction->invoice || $transaction->invoice->insuranceClaims->isEmpty(), 404, 'Aucune prise en charge assurance sur cette facture.');

        $bons = PriseEnCharge::whereIn('id', \App\Models\Assurance\PecUtilisation::where('invoice_id', $transaction->invoice->id)->pluck('prise_en_charge_id'))->get();

        return view('assurance.feuilles-de-soins.show', [
            'transaction' => $transaction,
            'invoice' => $transaction->invoice,
            'reclamations' => $transaction->invoice->insuranceClaims,
            'bons' => $bons,
            'medecin' => $transaction->transactionable?->medecin ?? null,
            'identite' => IdentiteDocument::courante(),
        ]);
    }
}
