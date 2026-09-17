<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Transaction;

class AccountController extends Controller
{

    public function factureNonPayer()
    {
        $transactionsDu = Transaction::whereIn('status', ['pending', 'partial'])
            ->with('patient', 'invoice')
            ->whereRaw('total > 0')
            ->orderBy('created_at', 'DESC')
            ->get();

        return view('invoices.unpaid', compact('transactionsDu'));
    }

    /**
     * Encaissement global d'un patient (écran « factures impayées »).
     *
     * CORRIGÉ — dupliquait la logique de PaymentService avec ses propres
     * règles : statut « paid » dès que montant_payer atteignait le total
     * (part assurance ignorée), solde modifié en lecture-écriture non
     * atomique, plantage si le patient n'avait pas de compte, aucune
     * transaction SQL. Tout passe désormais par PaymentService.
     */
    public function payer(Request $request, \App\Services\PaymentService $paiements)
    {
        $donnees = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'montant' => 'required|numeric|min:1',
            'source' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        $patient = Patient::findOrFail($donnees['patient_id']);

        try {
            $resultat = $paiements->payPatientForPatient(
                patient: $patient,
                amount: (float) $donnees['montant'],
                paymentMethod: $donnees['source'],
                description: $donnees['description'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($resultat['paid_amount'] <= 0) {
            return redirect()->back()->with('error', 'Aucune somme due pour ce patient dans cet établissement.');
        }

        $message = 'Paiement enregistré : ' . number_format($resultat['paid_amount'], 0, ',', ' ') . ' GNF.';
        if ($resultat['remaining_amount'] > 0) {
            $message .= ' Trop-perçu non affecté : ' . number_format($resultat['remaining_amount'], 0, ',', ' ') . ' GNF (à rendre au patient).';
        }

        return redirect()->back()->with('success', $message);
    }
}
