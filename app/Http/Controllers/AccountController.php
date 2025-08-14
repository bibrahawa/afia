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
   public function serviceReport(Request $request)
   {

        $services = Service::get();
        $user = '';

	    if (count($request->all())) {

	        if ($request->starting_date) {
	            $starting_date = date('Y-m-d '.'00:00:00', strtotime($request->starting_date));
	        } else {
	            $starting_date = date('Y-m-d ' .'00:00:00', time());
	        }

	        if ($request->ending_date) {

	            if ($request->ending_date == date('Y-m-d')) {

	                $ending_date = date('Y-m-d ' .'23:59:59', time());

	            }   else  {

	                $ending_date = date('Y-m-d '.'23:59:59', strtotime($request->ending_date));
	            }

	        }   else    {

	            $ending_date = date('Y-m-d ' .'23:59:59', time());
	        }

	        if ($request->service_id) {
	         // return $starting_date;
	            $invoices = ServiceSale::where('service_id', $request->service_id)->whereBetween('created_at', array($starting_date, $ending_date) )->get();
	        } else {

	            $invoices = ServiceSale::whereBetween('created_at', array($starting_date, $ending_date))->get();
	        }

	    }   else {

	          $starting_date = '';
	          $ending_date ='';
	          $invoices = ServiceSale::get();
	    }

        $total['total'] = $invoices->sum('amount');
        $total['starting_date'] = $starting_date;
        $total['ending_date'] = $ending_date;
        //return $total;
        return view('invoices.account.service', compact('invoices', 'total', 'services'));
   }

   // OPD sale Report
   public function opdReport(Request $request)
   {


	   	$doctors = Doctor::get();
        $user = '';

	    if (count($request->all())) {

	        if ($request->starting_date) {
	            $starting_date = date('Y-m-d '.'00:00:00', strtotime($request->starting_date));

	        }   else {
	          //return'here';
	              $starting_date = date('Y-m-d ' .'00:00:00', time());
	        }

	        if ($request->ending_date) {

	            if ($request->ending_date == date('Y-m-d')) {

	                $ending_date = date('Y-m-d ' .'23:59:59', time());

	            }   else  {

	                $ending_date = date('Y-m-d '.'23:59:59', strtotime($request->ending_date));
	            }

	        }   else    {

	            $ending_date = date('Y-m-d ' .'23:59:59', time());
	        }

	        if ($request->doctor_id) {
	         // return $starting_date;
	            $invoices = OpdSales::where('doctor_id', $request->doctor_id)->whereBetween('created_at', array($starting_date, $ending_date) )->get();
	        } else {

	            $invoices = OpdSales::whereBetween('created_at', array($starting_date, $ending_date))->get();
	        }

	    }   else {

	          $starting_date = '';
	          $ending_date ='';
	          $invoices = OpdSales::get();
	    }

        $total['doctor_fee'] = $invoices->sum('doctor_fee');
        $total['opd_charge'] = $invoices->sum('opd_charge');
        $total['starting_date'] = $starting_date;
        $total['ending_date'] = $ending_date;
        //return $starting_date;
        return view('invoices.account.opd', compact('invoices', 'total', 'doctors'));
   }


   // OPD sale Report
   public function packageReport(Request $request)
   {


	   	$packages = Package::get();
	   	//return PackageSale::get();
        $user = '';

	    if (count($request->all())) {

	        if ($request->starting_date) {
	            $starting_date = date('Y-m-d '.'00:00:00', strtotime($request->starting_date));

	        }   else {
	          //return'here';
	              $starting_date = date('Y-m-d ' .'00:00:00', time());
	        }

	        if ($request->ending_date) {

	            if ($request->ending_date == date('Y-m-d')) {

	                $ending_date = date('Y-m-d ' .'23:59:59', time());

	            }   else  {

	                $ending_date = date('Y-m-d '.'23:59:59', strtotime($request->ending_date));
	            }

	        }   else    {

	            $ending_date = date('Y-m-d ' .'23:59:59', time());
	        }

	        if ($request->doctor_id) {
	         // return $starting_date;
	            $invoices = PackageSale::where('doctor_id', $request->doctor_id)->whereBetween('created_at', array($starting_date, $ending_date) )->get();
	        } else {

	            $invoices = PackageSale::whereBetween('created_at', array($starting_date, $ending_date))->get();
	        }

	    }   else {

	          $starting_date = '';
	          $ending_date ='';
	          $invoices = PackageSale::get();
	    }

        $total['total'] = $invoices->sum('package_price');

        $total['starting_date'] = $starting_date;
        $total['ending_date'] = $ending_date;
        //return $starting_date;
        return view('invoices.account.package', compact('invoices', 'total', 'packages'));
   }

   public function factureNonPayer(){

    $transactionsDu = Transaction::whereIn('status', ['pending', 'partial'])
                                ->with('patient', 'invoice')
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
