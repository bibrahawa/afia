<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;
use App\Models\Service;
use App\Models\Department;
use App\Models\Transaction;
use App\Models\Consultation;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\InsuranceCompany;

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

        $departmentId = $request->get('department_id');
        $serviceIds = $request->get('services');
        $from = $request->get('from');
        $to = $request->get('to');
        $rapports = [];

        // Conversion des dates
        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();
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

        foreach ($transactions as $key => $transaction) {
            $debit = $transaction->total ?? 0;
            $credit = $transaction->montant_payer ?? 0;
            $soldeLigne = $credit;
            $soldeAccumule += $soldeLigne;

            $rapports[] = [
                'date' => $transaction->created_at->format('d/m/Y'),
                'patient' => $transaction->patient ? $transaction->patient->getFullNameAttribute() : 'Patient inconnu',
                'actes' => $this->getActes($transaction),
                'debit' => $debit,
                'credit' => $credit,
            ];

            $rapports[$key]['solde'] = $soldeAccumule; // Solde cumulé jusqu'à cette ligne
        }

        // Données pour la vue
        $donnees = $rapports;
        $date_debut = $fromDate->format('d/m/Y');
        $date_fin = $toDate->format('d/m/Y');
        $clinique_nom = \App\Support\Etablissement\IdentiteDocument::courante()->nom; // établissement courant, plus le nom de l'application

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

    public function situationParActe(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ], [
            'to.after_or_equal' => 'La date de fin doit être supérieure ou égale à la date de début',
        ]);

        $from = $request->get('from');
        $to   = $request->get('to') ?? now()->format('Y-m-d');

        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate   = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        $consultations = Consultation::with([
            'patient',
            'transaction',
            'transaction.paiements',
            'transaction.invoice',
            'transaction.invoice.items',
        ])
        ->whereBetween('created_at', [$fromDate, $toDate])
        ->orderBy('created_at', 'desc')
        ->get();

        $situationParService = [];

        foreach ($consultations as $consultation) {
            $transaction = $consultation->transaction;
            $invoice     = $transaction?->invoice;
            $items       = $invoice?->items ?? collect();
            $paiements   = $transaction?->paiements ?? collect();

            if ($items->isEmpty()) {
                continue;
            }

            // Somme des paiements par source
            $paiementsParSource = [
                'CASH'   => (float) $paiements->where('type', 'paiement')->where('source', 'CASH')->sum('montant'),
                'MOBILE' => (float) $paiements->where('type', 'paiement')->where('source', 'MOBILE')->sum('montant'),
                'CARD'   => (float) $paiements->where('type', 'paiement')->where('source', 'CARD')->sum('montant'),
                'CHQ'    => (float) $paiements->where('type', 'paiement')->where('source', 'CHQ')->sum('montant'),
            ];

            $totalPaiementsPatient = array_sum($paiementsParSource);

            // Somme exacte de la part patient au niveau facture
            $totalPatientFacture = (float) $items->sum(function ($item) {
                return (float) ($item->patient_amount ?? 0);
            });

            foreach ($items as $item) {
                $nomActe           = trim($item->description ?? 'Non défini');
                $quantite          = (int) ($item->quantity ?? 1);
                $montantTotal      = (float) ($item->total_amount ?? 0);
                $montantAssurance  = (float) ($item->insurance_covered_amount ?? 0);
                $montantPatient    = (float) ($item->patient_amount ?? 0);

                if (!isset($situationParService[$nomActe])) {
                    $situationParService[$nomActe] = [
                        'service'           => $nomActe,
                        'nb_actes'          => 0,
                        'total_patient'     => 0,
                        'total_espece'      => 0,
                        'total_pm'          => 0,
                        'total_tpe'         => 0,
                        'total_chq'         => 0,
                        'total_assurance'   => 0,
                        'total_general'     => 0,
                    ];
                }

                // Répartition imputée de la part patient par source
                $espece = 0;
                $pm     = 0;
                $tpe    = 0;
                $chq    = 0;

                if ($totalPatientFacture > 0 && $totalPaiementsPatient > 0 && $montantPatient > 0) {
                    $ratio = $montantPatient / $totalPatientFacture;

                    $espece = $paiementsParSource['CASH']   * $ratio;
                    $pm     = $paiementsParSource['MOBILE'] * $ratio;
                    $tpe    = $paiementsParSource['CARD']   * $ratio;
                    $chq    = $paiementsParSource['CHQ']    * $ratio;
                }

                $situationParService[$nomActe]['nb_actes']        += $quantite;
                $situationParService[$nomActe]['total_patient']   += $montantPatient;
                $situationParService[$nomActe]['total_espece']    += $espece;
                $situationParService[$nomActe]['total_pm']        += $pm;
                $situationParService[$nomActe]['total_tpe']       += $tpe;
                $situationParService[$nomActe]['total_chq']       += $chq;
                $situationParService[$nomActe]['total_assurance'] += $montantAssurance;
                $situationParService[$nomActe]['total_general']   += $montantTotal;
            }
        }

        $situationParService = array_values($situationParService);

        usort($situationParService, function ($a, $b) {
            return $b['total_general'] <=> $a['total_general'];
        });

        $totaux = [
            'nb_actes'   => array_sum(array_column($situationParService, 'nb_actes')),
            'patient'    => array_sum(array_column($situationParService, 'total_patient')),
            'espece'     => array_sum(array_column($situationParService, 'total_espece')),
            'pm'         => array_sum(array_column($situationParService, 'total_pm')),
            'tpe'        => array_sum(array_column($situationParService, 'total_tpe')),
            'chq'        => array_sum(array_column($situationParService, 'total_chq')),
            'assurance'  => array_sum(array_column($situationParService, 'total_assurance')),
            'general'    => array_sum(array_column($situationParService, 'total_general')),
        ];

        $kpi = [
            'total_patients'    => $consultations->pluck('patient_id')->filter()->unique()->count(),
            'total_actes'       => $totaux['nb_actes'],
            'total_patient'     => $totaux['patient'],
            'total_espece'      => $totaux['espece'],
            'total_pm'          => $totaux['pm'],
            'total_tpe'         => $totaux['tpe'],
            'total_chq'         => $totaux['chq'],
            'total_assurance'   => $totaux['assurance'],
            'grand_total'       => $totaux['general'],
            'reste_a_encaisser' => $consultations->sum(function ($consultation) {
                $transaction = $consultation->transaction;
                $total       = (float) ($transaction?->total ?? 0);
                $paye        = (float) ($transaction?->montant_payer ?? 0);

                return max($total - $paye, 0);
            }),
        ];

        return view('reports.tools.compta', compact(
            'situationParService',
            'totaux',
            'kpi',
            'from',
            'to'
        ));
    }

    public function actesParAssurance(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ], [
            'to.after_or_equal' => 'La date de fin doit être supérieure ou égale à la date de début',
        ]);

        $from = $request->get('from');
        $to   = $request->get('to') ?? now()->format('Y-m-d');

        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate   = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        $invoices = Invoice::with([
            'insuranceCompany',
            'items',
            'transaction.patient',
        ])
        ->whereBetween('created_at', [$fromDate, $toDate])
        ->whereNotNull('insurance_company_id')
        ->orderBy('created_at', 'asc')
        ->get();

        $rapportParAssurance = [];

        foreach ($invoices as $invoice) {
            $insuranceCompany = $invoice->insuranceCompany;

            if (!$insuranceCompany) {
                continue;
            }

            $companyName = trim($insuranceCompany->name ?? 'Assurance non définie');

            if (!isset($rapportParAssurance[$companyName])) {
                $rapportParAssurance[$companyName] = [
                    'assurance'        => $companyName,
                    'nb_actes'         => 0,
                    'nb_factures'      => 0,
                    'patients'         => [],
                    'total_assurance'  => 0,
                    'total_patient'    => 0,
                    'total_general'    => 0,
                ];
            }

            $rapportParAssurance[$companyName]['nb_factures']++;

            $patientId = $invoice->transaction?->patient?->id;
            if ($patientId) {
                $rapportParAssurance[$companyName]['patients'][$patientId] = true;
            }

            foreach ($invoice->items as $item) {
                $insuranceCovered = (float) ($item->insurance_covered_amount ?? 0);

                // On ne compte que les actes réellement couverts par assurance
                if ($insuranceCovered <= 0) {
                    continue;
                }

                $quantite      = (int) ($item->quantity ?? 1);
                $montantTotal  = (float) ($item->total_amount ?? 0);
                $montantPatient = (float) ($item->patient_amount ?? 0);

                $rapportParAssurance[$companyName]['nb_actes']        += $quantite;
                $rapportParAssurance[$companyName]['total_assurance'] += $insuranceCovered;
                $rapportParAssurance[$companyName]['total_patient']   += $montantPatient;
                $rapportParAssurance[$companyName]['total_general']   += $montantTotal;
            }
        }

        // Transformer la liste des patients en compteur
        $rapportParAssurance = array_map(function ($row) {
            $row['nb_patients'] = count($row['patients']);
            unset($row['patients']);
            return $row;
        }, $rapportParAssurance);

        $rapportParAssurance = array_values($rapportParAssurance);

        usort($rapportParAssurance, function ($a, $b) {
            return $b['total_assurance'] <=> $a['total_assurance'];
        });

        $totaux = [
            'nb_actes'        => array_sum(array_column($rapportParAssurance, 'nb_actes')),
            'nb_factures'     => array_sum(array_column($rapportParAssurance, 'nb_factures')),
            'nb_patients'     => array_sum(array_column($rapportParAssurance, 'nb_patients')),
            'total_assurance' => array_sum(array_column($rapportParAssurance, 'total_assurance')),
            'total_patient'   => array_sum(array_column($rapportParAssurance, 'total_patient')),
            'total_general'   => array_sum(array_column($rapportParAssurance, 'total_general')),
        ];

        $kpi = [
            'nb_assurances'    => count($rapportParAssurance),
            'nb_actes'         => $totaux['nb_actes'],
            'nb_factures'      => $totaux['nb_factures'],
            'nb_patients'      => $totaux['nb_patients'],
            'total_assurance'  => $totaux['total_assurance'],
            'total_patient'    => $totaux['total_patient'],
            'total_general'    => $totaux['total_general'],
        ];

        return view('reports.tools.acteParAssurance', compact(
            'rapportParAssurance',
            'totaux',
            'kpi',
            'from',
            'to'
        ));
    }

    public function actesParAssuranceEtParActe(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ], [
            'to.after_or_equal' => 'La date de fin doit être supérieure ou égale à la date de début',
        ]);

        $from = $request->get('from');
        $to   = $request->get('to') ?? now()->format('Y-m-d');

        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate   = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        $invoices = \App\Models\Invoice::with([
            'insuranceCompany',
            'items',
            'transaction.patient',
        ])
        ->whereBetween('created_at', [$fromDate, $toDate])
        ->whereNotNull('insurance_company_id')
        ->orderBy('created_at', 'asc')
        ->get();

        $rapport = [];

        foreach ($invoices as $invoice) {
            $assurance = $invoice->insuranceCompany;

            if (!$assurance) {
                continue;
            }

            $assuranceNom = trim($assurance->name ?? 'Assurance non définie');

            foreach ($invoice->items as $item) {
                $montantAssurance = (float) ($item->insurance_covered_amount ?? 0);

                // On ne prend que les actes réellement couverts par assurance
                if ($montantAssurance <= 0) {
                    continue;
                }

                $acte = trim($item->description ?? 'Acte non défini');
                $quantite = (int) ($item->quantity ?? 1);
                $montantPatient = (float) ($item->patient_amount ?? 0);
                $montantTotal = (float) ($item->total_amount ?? 0);

                $key = $assuranceNom . '||' . $acte;

                if (!isset($rapport[$key])) {
                    $rapport[$key] = [
                        'assurance'        => $assuranceNom,
                        'acte'             => $acte,
                        'nb_actes'         => 0,
                        'nb_factures'      => 0,
                        'patients'         => [],
                        'total_assurance'  => 0,
                        'total_patient'    => 0,
                        'total_general'    => 0,
                    ];
                }

                $rapport[$key]['nb_actes'] += $quantite;
                $rapport[$key]['total_assurance'] += $montantAssurance;
                $rapport[$key]['total_patient'] += $montantPatient;
                $rapport[$key]['total_general'] += $montantTotal;
                $rapport[$key]['nb_factures'] += 1;

                $patientId = $invoice->transaction?->patient?->id;
                if ($patientId) {
                    $rapport[$key]['patients'][$patientId] = true;
                }
            }
        }

        $rapport = array_map(function ($row) {
            $row['nb_patients'] = count($row['patients']);
            unset($row['patients']);
            return $row;
        }, $rapport);

        $rapport = array_values($rapport);

        usort($rapport, function ($a, $b) {
            if ($a['assurance'] === $b['assurance']) {
                return $b['nb_actes'] <=> $a['nb_actes'];
            }

            return strcmp($a['assurance'], $b['assurance']);
        });

        $totaux = [
            'nb_actes'        => array_sum(array_column($rapport, 'nb_actes')),
            'nb_factures'     => array_sum(array_column($rapport, 'nb_factures')),
            'nb_patients'     => array_sum(array_column($rapport, 'nb_patients')),
            'total_assurance' => array_sum(array_column($rapport, 'total_assurance')),
            'total_patient'   => array_sum(array_column($rapport, 'total_patient')),
            'total_general'   => array_sum(array_column($rapport, 'total_general')),
        ];

        $kpi = [
            'nb_assurances'    => collect($rapport)->pluck('assurance')->unique()->count(),
            'nb_types_actes'   => collect($rapport)->pluck('acte')->unique()->count(),
            'nb_lignes'        => count($rapport),
            'nb_actes'         => $totaux['nb_actes'],
            'total_assurance'  => $totaux['total_assurance'],
            'total_general'    => $totaux['total_general'],
        ];

        return view('reports.tools.actes_par_assurance_detail', compact(
            'rapport',
            'totaux',
            'kpi',
            'from',
            'to'
        ));
    }

    public function bordereauAssurance(Request $request)
    {
        // $request->validate([
        //     'insurance_company_id' => 'required|exists:insurance_companies,id',
        //     'from' => 'required|date',
        //     'to'   => 'nullable|date|after_or_equal:from',
        // ], [
        //     'insurance_company_id.required' => 'Veuillez sélectionner une assurance',
        //     'to.after_or_equal' => 'La date de fin doit être supérieure ou égale à la date de début',
        // ]);

        $insuranceCompanyId = $request->get('insurance_company_id') ?? 1;

        $from = $request->get('from') ?? now()->format('Y-m-d');
        $to   = $request->get('to') ?? now()->format('Y-m-d');

        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate   = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();
        

        $insuranceCompany = InsuranceCompany::findOrFail($insuranceCompanyId);

        $invoices = Invoice::with([
            'insuranceCompany',
            'patientInsurance',
            'items',
            'transaction.patient',
        ])
        ->where('insurance_company_id', $insuranceCompanyId)
        ->whereBetween('created_at', [$fromDate, $toDate])
        ->orderBy('created_at', 'asc')
        ->get();

        $lignes = [];

        foreach ($invoices as $invoice) {
            $transaction = $invoice->transaction;
            $patient = $transaction?->patient;
            $patientInsurance = $invoice->patientInsurance;

            $nomAssurePrincipal = trim(($patient?->first_name ?? '') . ' ' . ($patient?->last_name ?? ''));

            $beneficiaire = $nomAssurePrincipal;

            $numeroCarte = $patientInsurance?->policy_number
                ?? '—';

            foreach ($invoice->items as $item) {
                $montantAssureur = (float) ($item->insurance_covered_amount ?? 0);

                // On ne garde que les lignes réellement couvertes par assurance
                if ($montantAssureur <= 0) {
                    continue;
                }

                $lignes[] = [
                    'date'               => optional($invoice->created_at)->format('d/m/Y'),
                    'nom_assure'         => $nomAssurePrincipal ?: 'Patient inconnu',
                    'beneficiaire'       => $beneficiaire ?: 'Patient inconnu',
                    'numero_carte'       => $numeroCarte,
                    'nature_prestation'  => $item->description ?? 'Acte non défini',
                    'montant_prestation' => (float) ($item->total_amount ?? 0),
                    'part_assure'        => (float) ($item->patient_amount ?? 0),
                    'part_assureur'      => $montantAssureur,
                ];
            }
        }

        $totaux = [
            'montant_prestation' => array_sum(array_column($lignes, 'montant_prestation')),
            'part_assure'        => array_sum(array_column($lignes, 'part_assure')),
            'part_assureur'      => array_sum(array_column($lignes, 'part_assureur')),
        ];

        return view('reports.tools.bordereau_assurance', compact(
            'insuranceCompany',
            'lignes',
            'totaux',
            'from',
            'to'
        ));
    }

}
