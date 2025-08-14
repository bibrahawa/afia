<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class InsuranceBalanceController extends Controller
{


    public function index()
    {
        // 1) Récupère les assurances actives
        $companies = InsuranceCompany::where('status', 'active')
            ->get(['id','name','code','status']);

        if ($companies->isEmpty()) {
            $rows = collect();
            return view('insurance.balances', compact('rows'));
        }

        // 2) Récupère les factures liées (seulement les colonnes utiles)
        $invoices = Invoice::whereIn('insurance_company_id', $companies->pluck('id'))
            ->whereNotNull('insurance_company_id')
            ->get(['insurance_company_id','insurance_status','patient_amount_status','insurance_amount']);

        // 3) Regroupe par assurance
        $byCompany = $invoices->groupBy('insurance_company_id');

        // 4) Calcule les totaux avec map => tableau final prêt pour la vue
        $insuranceBalances = $companies->map(function ($c) use ($byCompany) {
            $list = $byCompany->get($c->id, collect());

            $isDue = fn($inv) =>
                in_array($inv->insurance_status, ['approved','pending'], true)
                && $inv->patient_amount_status === 'paid';

            $montant_du        = $list->filter($isDue)->sum('insurance_amount');
            $montant_paye      = $list->where('insurance_status', 'paid')->sum('insurance_amount');
            $montant_total     = $list->sum('insurance_amount');
            $factures_impayees = $list->filter($isDue)->count();
            $total_factures    = $list->count();

            return [
                'id'                => $c->id,
                'name'              => $c->name,
                'code'              => $c->code,
                'status'            => $c->status,
                'montant_du'        => (float) $montant_du,
                'montant_paye'      => (float) $montant_paye,
                'montant_total'     => (float) $montant_total,
                'factures_impayees' => $factures_impayees,
                'total_factures'    => $total_factures,
            ];
        })
        ->sortByDesc('montant_du')
        ->values();


        return view('insurance.balance.index', compact('insuranceBalances'));
    }


    /**
     * Calcule les soldes de toutes les assurances
     */
    private function getInsuranceBalances()
    {
        return InsuranceCompany::where('status', 'active')

                    // Montant dû (approved|pending) ET patient payé
                    ->withSum(['invoices as montant_du' => function ($q) {
                        $q->whereIn('insurance_status', ['approved', 'pending'])
                        ->where('patient_amount_status', 'paid');
                    }], 'insurance_amount')

                    // Montant payé
                    ->withSum(['invoices as montant_paye' => function ($q) {
                        $q->where('insurance_status', 'paid');
                    }], 'insurance_amount')

                    // Montant total (toutes factures)
                    ->withSum('invoices as montant_total', 'insurance_amount')

                    // Factures impayées (approved|pending) ET patient payé
                    ->withCount(['invoices as factures_impayees' => function ($q) {
                        $q->whereIn('insurance_status', ['approved', 'pending'])
                        ->where('patient_amount_status', 'paid');
                    }])

                    // Total factures
                    ->withCount('invoices as total_factures')

                    ->orderByDesc('montant_du')
                    ->get(['id','name','code','status']);

    }

    /**
     * Affiche les détails d'une assurance spécifique
     */
    public function show($id)
    {
        $insurance = InsuranceCompany::findOrFail($id);
        
        // Récupérer les factures avec pagination
        $invoices = Invoice::where('insurance_company_id', $id)
                        ->with(['transaction.patient'])
                        ->whereIn('insurance_status', ['approved', 'pending'])
                        ->where('patient_amount_status', 'paid')
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);

        // Calculer les statistiques avec une seule requête optimisée
        $statsQuery = DB::table('invoices')
                        ->where('insurance_company_id', $id)
                        ->selectRaw("
                            SUM(CASE 
                                WHEN insurance_status IN ('approved', 'pending') 
                                AND patient_amount_status = 'paid' 
                                THEN insurance_amount 
                                ELSE 0 
                            END) as montant_du,
                            
                            SUM(CASE 
                                WHEN insurance_status = 'paid' 
                                THEN insurance_amount 
                                ELSE 0 
                            END) as montant_paye,
                            
                            COUNT(CASE 
                                WHEN insurance_status IN ('approved', 'pending') 
                                AND patient_amount_status = 'paid' 
                                THEN 1 
                            END) as factures_impayees,
                            
                            COUNT(*) as total_factures,
                            SUM(insurance_amount) as montant_total
                        ")
                        ->first();

        $stats = [
            'montant_du' => $statsQuery->montant_du ?? 0,
            'montant_paye' => $statsQuery->montant_paye ?? 0,
            'factures_impayees' => $statsQuery->factures_impayees ?? 0,
            'total_factures' => $statsQuery->total_factures ?? 0,
            'montant_total' => $statsQuery->montant_total ?? 0
        ];

        return view('insurance.balance.show', compact('insurance', 'invoices', 'stats'));
    }

    /**
     * Traite le paiement d'une facture par l'assurance
     */
    public function processPayment(Request $request, $invoiceId)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'claim_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            $invoice = Invoice::findOrFail($invoiceId);
            
            // Vérifier si le montant ne dépasse pas le montant dû
            if ($request->payment_amount > $invoice->insurance_amount) {
                return redirect()->back()->with('error', 'Le montant du paiement ne peut pas dépasser le montant dû.');
            }

            // Mettre à jour la facture
            $invoice->update([
                'insurance_status' => 'approved',
                'insurance_payment_date' => $request->payment_date,
                'insurance_claim_number' => $request->claim_number,
                'insurance_notes' => $request->notes
            ]);

            // Mettre à jour le statut de la transaction si nécessaire
            $transaction = $invoice->transaction;
            if ($transaction) {
                // Si le patient a aussi payé, la transaction est complètement réglée
                if ($invoice->patient_amount_status === 'paid') {
                    $transaction->update(['status' => 'completed']);
                }
            }

            DB::commit();
            
            return redirect()->back()->with('success', 'Paiement enregistré avec succès.');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    /**
     * Traite le paiement groupé pour plusieurs factures
     */
    public function processGroupPayment(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'exists:invoices,id',
            'payment_date' => 'required|date',
            'claim_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            
            $invoices = Invoice::whereIn('id', $request->invoice_ids)
                ->where('insurance_status', 'pending')
                ->get();

            if ($invoices->isEmpty()) {
                return redirect()->back()->with('error', 'Aucune facture valide sélectionnée.');
            }

            $totalAmount = $invoices->sum('insurance_amount');

            foreach ($invoices as $invoice) {
                $invoice->update([
                    'insurance_status' => 'approved',
                    'insurance_payment_date' => $request->payment_date,
                    'insurance_claim_number' => $request->claim_number,
                    'insurance_notes' => $request->notes
                ]);

                // Mettre à jour le statut de la transaction si nécessaire
                $transaction = $invoice->transaction;
                if ($transaction && $invoice->patient_amount_status === 'paid') {
                    $transaction->update(['status' => 'completed']);
                }
            }

            DB::commit();
            
            return redirect()->back()->with('success', "Paiement groupé enregistré avec succès. Total: " . number_format($totalAmount, 0, ',', ' ') . " FCFA");
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    public function ProcessPaiement(Request $request){
        
        dd($request->all());
        
        $request->validate([
            'insurance_companies_id'   => 'required|exists:insurance_companies,id',
            'patient_id'   => 'required|exists:patients,id',
            'montant_assurance' => 'nullable|numeric|min:0',
            'source_assurance' => 'nullable|string',
            'description_assurance' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $insurance = InsuranceCompany::findOrFail($request->insurance_companies_id);
            // Récupérer les factures avec pagination
            $invoices = Invoice::where('insurance_company_id', $insurance->id)
                        ->with(['transaction.patient'])
                        ->whereIn('insurance_status', ['approved', 'pending'])
                        ->where('patient_amount_status', 'paid')
                        ->orderBy('created_at', 'desc')
                        ->get();

            $transactions = $patient->getPendingAndPartialTransaction();

            foreach($invoices as $key => $invoice) {

                $montantAssurance = (float) $request->montant_assurance;

                if ($montantAssurance > 0) {
                    foreach ($transactions as $transaction) {
                        if ($montantAssurance <= 0) break;

                        $invoice = $transaction->invoice;
                        if (!$invoice) continue;

                        $resteAssurance = $invoice->insurance_amount - $this->getPaidAmount($invoice->id, 'ASSURANCE');
                        if ($resteAssurance <= 0) continue;

                        $montantAPayer = min($montantAssurance, $resteAssurance);

                        // Enregistrer le paiement assurance
                        $this->createPaiement($transaction, $patient, 'ASSURANCE', $montantAPayer, $request->description_assurance);

                        // Mettre à jour la facture
                        if (($resteAssurance - $montantAPayer) <= 0) {
                            $invoice->insurance_status = 'paid';
                        } else {
                            $invoice->insurance_status = 'pending';
                        }

                        $invoice->save();

                        $montantAssurance -= $montantAPayer;
                    }
                }
                DB::commit();
            }

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }


    /**
     * Crée un paiement
     */
    private function createPaiement($transaction, $patient, $source, $montant, $description)
    {
        Paiement::create([
            'user_id' => auth()->id(),
            'patient_id' => $patient->id,
            'transaction_id' => $transaction->id,
            'source' => strtoupper($source),
            'description' => $description,
            'montant' => $montant,
        ]);
    }

    /**
     * Récupère le montant déjà payé par type (patient ou assurance)
     */
    private function getPaidAmount($invoiceId, $type)
    {
        return Paiement::whereHas('transaction.invoice', function($q) use ($invoiceId) {
            $q->where('id', $invoiceId);
        })
        ->where('source', strtoupper($type))
        ->sum('montant');
    }

    /**
     * Exporte les données des soldes en CSV
     */
    public function export()
    {
        $insuranceBalances = $this->getInsuranceBalances();
        
        $filename = 'soldes_assurances_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($insuranceBalances) {
            $handle = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($handle, [
                'Code',
                'Nom Assurance',
                'Montant Dû (FCFA)',
                'Montant Payé (FCFA)',
                'Montant Total (FCFA)',
                'Factures Impayées',
                'Total Factures',
                'Statut'
            ]);

            // Données
            foreach ($insuranceBalances as $balance) {
                fputcsv($handle, [
                    $balance->code,
                    $balance->name,
                    number_format($balance->montant_du, 0, ',', ' '),
                    number_format($balance->montant_paye, 0, ',', ' '),
                    number_format($balance->montant_total, 0, ',', ' '),
                    $balance->factures_impayees,
                    $balance->total_factures,
                    $balance->status
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }



    public function pendingInvoices($insuranceId)     {
        $insurance = InsuranceCompany::findOrFail($insuranceId);
        
        // Récupérer les factures avec pagination
        $invoices = Invoice::where('insurance_company_id', $insuranceId)
                        ->with(['transaction.patient'])
                        ->whereIn('insurance_status', ['approved', 'pending'])
                        ->where('patient_amount_status', 'paid')
                        ->orderBy('created_at', 'desc')
                        ->get();

        // Calculer les statistiques avec une seule requête optimisée
        $statsQuery = DB::table('invoices')
                        ->where('insurance_company_id', $insuranceId)
                        ->selectRaw("
                            SUM(CASE 
                                WHEN insurance_status IN ('approved', 'pending') 
                                AND patient_amount_status = 'paid' 
                                THEN insurance_amount 
                                ELSE 0 
                            END) as montant_du,
                            
                            SUM(CASE 
                                WHEN insurance_status = 'paid' 
                                THEN insurance_amount 
                                ELSE 0 
                            END) as montant_paye,
                            
                            COUNT(CASE 
                                WHEN insurance_status IN ('approved', 'pending') 
                                AND patient_amount_status = 'paid' 
                                THEN 1 
                            END) as factures_impayees,
                            
                            COUNT(*) as total_factures,
                            SUM(insurance_amount) as montant_total
                        ")
                        ->first();

        $stats = [
            'montant_du' => $statsQuery->montant_du ?? 0,
            'montant_paye' => $statsQuery->montant_paye ?? 0,
            'factures_impayees' => $statsQuery->factures_impayees ?? 0,
            'total_factures' => $statsQuery->total_factures ?? 0,
            'montant_total' => $statsQuery->montant_total ?? 0
        ];

        return response()->json([
            'success' => true,
            'invoices' => $invoices,
            'stats' => $stats,
            'insurance' => $insurance
        ]);
    }
}