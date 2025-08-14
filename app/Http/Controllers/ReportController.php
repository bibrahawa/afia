<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;
use App\Models\Service;
use App\Models\Department;
use App\Models\Transaction;
use App\Models\Consultation;

class ReportController extends Controller
{
	public function index()
	{
        $services = Service::all();
        $departments = Department::all();
        $examens = Test::all();
		return view('reports.tools.index', compact('services', 'departments', 'examens'));

    }

    public function services(Request $request)
    {
        // Validation des données
        $request->validate([
            'department_id' => 'required',
            'services' => 'required|array',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ], [
            'to.after_or_equal' => 'La date de fin doit être supérieure ou égale à la date de début',
            'services.required' => 'Veuillez sélectionner au moins un service',
        ]);

        dd($request->all());

        $departmentId = $request->get('department_id');
        $serviceIds = $request->get('services');
        $from = $request->get('from');
        $to = $request->get('to');
        $rapports = [];

        // Conversion des dates
        $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $to)->endOfDay();
        // Construction de la requête pour les services
        $consultationQuery = Consultation::whereBetween('created_at', [$fromDate, $toDate]);
        // Filtrer par département si spécifié
        if ($departmentId != 'all') {
            $consultationQuery->where('department_id', $departmentId);
        }

        $consultationQuery = $consultationQuery->get();
        $i = 0;
        $consultationQuery->map(function($consultation) use ($serviceIds, &$rapports, $i) {
            $total = 0;
            $rapports[$i] = [
                'department' => $consultation->department->name,
                'patiente' => $consultation->patient->first_name . ' ' . $consultation->patient->last_name,
                'consultation' => $consultation,
            ];
            // Récupération des services associés à la consultation
            foreach ($consultation->services as $key=>$service) {
                // Vérifier si le service est dans la liste des services sélectionnés
                if (in_array($service->id, $serviceIds) || in_array('all', $serviceIds)) {
                    $rapports[$i]['services'][$key] = [
                        'service' => $service->name,
                        'amount' => $service->amount,
                    ];
                    $total += $service->amount;
                }
            }

            $rapports[$i]['total'] = $total;

        });

        $i++;

        return view('reports.tools.rapport_service', compact(
            'rapports',
            'from',
            'to',
        ));
    }

    public function rapportActes(Request $request)
    {
        // Validation des données
        $request->validate([
            'from' => 'required|date',
            'to' => 'nullable|date',
        ]);

        $from = $request->get('from');
        $to = $request->get('to') ?? date('Y-m-d');
        
        // Conversion des dates
        $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $to)->endOfDay();
        
        // Récupération des transactions dans la période
        $transactions = Transaction::with(['patient']) // Chargement des relations
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderBy('created_at', 'asc')
            ->get();

        $rapports = [];
        $soldeAccumule = 0; // Pour calculer le solde cumulé

        foreach ($transactions as $transaction) {
            $debit = $transaction->total ?? 0;
            $credit = $transaction->montant_payer ?? 0;
            $soldeLigne = $debit - $credit;
            $soldeAccumule += $soldeLigne;

            $rapports[] = [
                'date' => $transaction->created_at->format('d/m/Y'),
                'patient' => $transaction->patient ? $transaction->patient->getFullNameAttribute() : 'Patient inconnu',
                'actes' => $this->getActes($transaction),
                'debit' => $debit,
                'credit' => $credit,
                'solde' => $soldeAccumule, // Solde cumulé
            ];
        }

        // Données pour la vue
        $donnees = $rapports;
        $date_debut = $fromDate->format('d/m/Y');
        $date_fin = $toDate->format('d/m/Y');
        $clinique_nom = config('app.name', 'Clinique Médicale'); // Nom depuis config

        return view('reports.tools.rapport_actes', compact(
            'donnees',
            'date_debut',
            'date_fin',
            'clinique_nom',
            'from',
            'to'
        ));
    }

    /**
     * Méthode helper pour récupérer les actes d'une transaction
     */
    private function getActes($transaction)
    {
        $consultation = $transaction->transactionable;
        $actes = [];
        
        // Vérifier les services
        if ($consultation?->services?->count() > 0) {
            $services = $consultation->services->pluck('name')->filter()->implode('| ');

            if (!empty($services)) {
                $actes[] = $services;
            }
        }

        // Vérifier les packages
        if ($consultation?->packages?->count() > 0) {
            $packages = $consultation->packages->pluck('name')->filter()->implode('| ');
            if (!empty($packages)) {
                $actes[] = $packages;
            }
        }

        // Vérifier les tests
        if ($consultation?->tests?->count() > 0) {
            $tests = $consultation->tests->pluck('name')->filter()->implode('| ');
            if (!empty($tests)) {
                $actes[] = $tests;
            }
        }

        // Vérifier les médicaments
        if ($consultation?->medicaments?->count() > 0) {
            $medicaments = $consultation->medicaments->pluck('nom')->filter()->implode('| ');
            if (!empty($medicaments)) {
                $actes[] = $medicaments;
            }
        }

        // Vérifier les hospitalisations
        if ($consultation?->hospitalisations?->count() > 0) {
            $actes[] = "Hospitalisation";
        }

        // Retourner les actes trouvés ou valeur par défaut
        return implode(' | ', $actes);
    }

}
