<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceSale;
use App\Models\Service;
use App\Models\OpdSales;
use App\Models\Doctor;
use App\Models\PackageSale;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Transaction;
use App\Models\Paiement;
use App\Models\Consultation;

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

   public function payer(Request $request)
   {

       $request->validate([
           'montant' => 'required|numeric|min:1',
           'source' => 'required|string'
       ]);

       $montant = $request->montant;

       if ($montant == 0 || $montant == null || $montant < 0) {
           return redirect()->back()->with('error', 'Please enter a valid amount.');
       }

       $patient = Patient::find($request->patient_id);
       $transactions = $patient->getPendingAndPartialTransaction();

       foreach ($transactions as $transaction) {
           if ($montant > 0) {
               $payer = $transaction->montant_payer;
               $total = $transaction->total;

               if ($payer < $total) {
                   $montantRestant = $total - $payer;
                   if ($montant >= $montantRestant) {
                       $transaction->montant_payer += $montantRestant;
                       $payer = $montantRestant;
                       $montant -= $montantRestant;
                   } else {
                       $transaction->montant_payer += $montant;
                       $payer = $montant;
                       $montant = 0;
                    }

                   // Calculer le montant réellement payé pour cette transaction
                   $montantPayePourCetteTransaction = ($montant >= $montantRestant) ? $montantRestant : $payer;

                    // dd($montantPayePourCetteTransaction, $montantRestant, $montant, $payer);

                   if ($transaction->save()) {
                       $paiement = new Paiement();
                       $paiement->user_id = auth()->user()->id;
                       $paiement->patient_id = $patient->id;
                       $paiement->transaction_id = $transaction->id;
                       $paiement->source = $request->source;
                       $paiement->description = $request->description;
                       $paiement->montant = $montantPayePourCetteTransaction; // Montant réel pour cette transaction

                       if ($paiement->save()) {
                           // Mettre à jour le statut de la transaction
                           if ($transaction->montant_payer >= $transaction->total) {
                               $transaction->status = 'paid';
                           } else {
                               $transaction->status = 'partial';
                           }
                           $transaction->save();

                       } else {
                           return redirect()->back()->with('error', 'Error saving payment.');
                       }
                   } else {
                       return redirect()->back()->with('error', 'Error saving transaction.');
                   }
               }
           }
       }

       // Déduire le montant total payé du solde du compte (une seule fois à la fin)
       $montantTotalPaye = $request->montant - $montant; // Ce qui reste dans $montant n'a pas été utilisé
       if ($montantTotalPaye > 0) {
           $account = $patient->account;
           $account->balance -= $montantTotalPaye;
           $account->save();
       }

       return redirect()->back()->with('success', 'Payment successful.');
   }

}
