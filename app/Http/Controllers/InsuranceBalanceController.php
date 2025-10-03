<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Paiement;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class InsuranceBalanceController extends Controller
{
    public function index()
    {
        // 1) Récupère les assurances actives
        $companies = InsuranceCompany::where('status', 'active')
                                    ->get(['id','name','code','status']);

        // 2) Récupère les factures liées avec transactions
        $invoices = Invoice::whereIn('insurance_company_id', $companies->pluck('id'))
            ->whereNotNull('insurance_company_id')
            ->with('transaction')
            ->get();

        // 3) Regroupe par compagnie
        $byCompany = $invoices->groupBy('insurance_company_id');

        // 4) Calcule les totaux
        $insuranceBalances = $companies->map(function ($c) use ($byCompany) {
            $list = $byCompany->get($c->id, collect());

            // Factures assurées mais encore dues (validées ou en attente)
            $isDue = fn($inv) =>
                in_array($inv->insurance_status, ['approved', 'pending'], true)
                && $inv->patient_amount_status === 'paid';

            $isPaid = fn($inv) =>
                in_array($inv->insurance_status, ['approved', 'paid'], true)
                && $inv->patient_amount_status === 'paid';

            // Montant dû par l’assurance = total - montant déjà payé
            $montant_du = $list->filter($isDue)->sum(function ($inv) {
                return max(0, (float)$inv->transaction->total - (float)$inv->transaction->montant_payer);
            });

            // Montant déjà payé par l’assurance = ce qui a été payé en excédent de la part patient
            $montant_paye = $list->filter($isPaid)->sum(function ($inv) {
                // Si patient est déjà payé, alors tout le reste du "montant_payer" vient de l’assurance
                if ($inv->patient_amount_status === 'paid' && $inv->insurance_status === 'paid') {
                   return (float)$inv->insurance_amount;
                }else{
                    return max(0, (float)$inv->transaction->montant_payer - (float)$inv->patient_amount);
                }
                return 0;
            });

            // Montant total théorique couvert par l’assurance = somme de insurance_amount
            $montant_total = $list->sum('insurance_amount');

            return [
                'id'                => $c->id,
                'name'              => $c->name,
                'code'              => $c->code,
                'status'            => $c->status,
                'montant_du'        => (float) $montant_du,
                'montant_paye'      => (float) $montant_paye,
                'montant_total'     => (float) $montant_total,
                'factures_impayees' => $list->filter($isDue)->count(),
                'total_factures'    => $list->count(),
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
        
        // 1) Récupérer les factures avec pagination
        $invoices = Invoice::where('insurance_company_id', $id)
                        ->with(['transaction.patient'])
                        ->whereIn('insurance_status', ['approved', 'pending'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);

        // 2) Récupérer toutes les factures (non paginées) pour calculer les stats
        $allInvoices = Invoice::where('insurance_company_id', $id)
                                ->whereIn('insurance_status', ['approved', 'pending', 'paid'])
                                ->with('transaction')
                                ->get();

        // 3) Calculer les stats
        $isDue = fn($inv) =>
            in_array($inv->insurance_status, ['approved', 'pending'], true)
            && $inv->patient_amount_status === 'paid';

        $isPaid = fn($inv) =>
                in_array($inv->insurance_status, ['approved', 'paid'], true)
                && $inv->patient_amount_status === 'paid';

        $montant_du = $allInvoices->filter($isDue)->sum(function ($inv) {
            return max(0, (float)$inv->transaction->total - (float)$inv->transaction->montant_payer);
        });

        $montant_paye = $allInvoices->filter($isPaid)->sum(function ($inv) {
            // L’assurance paie après que le patient ait soldé sa part
            if ($inv->patient_amount_status === 'paid' && $inv->insurance_status === 'paid') {
                return (float)$inv->insurance_amount;
            }else{
                return max(0, (float)$inv->transaction->montant_payer - (float)$inv->patient_amount);
            }
            return 0;
        });

        $montant_total = $allInvoices->sum('insurance_amount');

        $stats = [
            'montant_du'        => (float) $montant_du,
            'montant_paye'      => (float) $montant_paye,
            'factures_impayees' => $invoices->filter($isDue)->count(),
            'total_factures'    => $invoices->count(),
            'montant_total'     => (float) $montant_total,
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
            
            return redirect()->back()->with('success', "Paiement groupé enregistré avec succès. Total: " . number_format($totalAmount, 0, ',', ' ') . " GNF");
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    public function ProcessPaiement(Request $request){

        $request->validate([
            'insurance_companies_id'   => 'required|exists:insurance_companies,id',
            'payment_date'   => 'nullable|date',
            'montant' => 'nullable|numeric|min:0',
            'totalRemise' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {

            $insurance = InsuranceCompany::findOrFail($request->insurance_companies_id);
           
            // Récupérer les factures
            $invoices = Invoice::where('insurance_company_id', $insurance->id)
                                ->with(['transaction.patient'])
                                ->whereIn('insurance_status', ['approved', 'pending'])
                                ->where('patient_amount_status', 'paid')
                                ->orderBy('created_at', 'desc')
                                ->get();

            $montantAssurance = (float) $request->montant;

            foreach($invoices as $key => $invoice) {

                $transaction = $invoice->transaction;
                
                if ($montantAssurance > 0) {

                    $resteAssurance = $invoice->insurance_amount - $this->getPaidAmount($transaction->id, 'remboursement');
                    
                    if ($resteAssurance <= 0) continue;

                    $montantAPayer = min($montantAssurance, $resteAssurance);

                    // Enregistrer le paiement assurance
                    $this->createPaiement($transaction, $transaction->patient_id, 'remboursement', $montantAPayer, $request->notes);

                    // Mettre à jour la facture
                    if (($resteAssurance - $montantAPayer) <= 0) {
                        
                        $invoice->insurance_status   = 'paid';
                        
                        $transaction->montant_payer += $montantAPayer;
                        $transaction->status         = 'paid';

                    } else {
                        
                        $invoice->insurance_status   = 'approved';

                        $transaction->montant_payer += $montantAPayer;
                        $transaction->status         = 'approved';
                    }

                    $invoice->save();
                    $transaction->save();
                    
                    //Mettre a jour le compte accounts
                    $account = $transaction->patient->account;
                    $account->balance -= $montantAPayer;
                    $account->save();

                    $montantAssurance -= $montantAPayer;

                }

            }

            DB::commit();

        return redirect()->back()->with('success', "Paiement groupé enregistré avec succès. Total: " . number_format($montantAssurance, 0, ',', ' ') . " GNF");

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }


    /**
     * Crée un paiement
     */
    private function createPaiement($transaction, $patientId, $source, $montant, $description)
    {
       return Paiement::create([
            'user_id' => auth()->id(),
            'patient_id' => $patientId,
            'transaction_id' => $transaction->id,
            // 'source' => strtoupper($source),
            'type' => $source,
            'description' => $description,
            'montant' => $montant,
        ]);
    }

    /**
     * Récupère le montant déjà payé par type (patient ou assurance)
     */
    private function getPaidAmount($transctionId, $type)
    {
        return Paiement::where('transaction_id', $transctionId)
                        ->where('type', $type)->sum('montant');
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
                'Montant Dû (GNF)',
                'Montant Payé (GNF)',
                'Montant Total (GNF)',
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