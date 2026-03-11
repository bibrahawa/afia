<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;
use App\Models\Service;
use App\Models\Department;
use App\Models\Transaction;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
                'solde' => $soldeLigne, // Solde de la ligne
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

    // ================================================================
    // À ajouter dans : App\Http\Controllers\ReportController
    // S'inspire de rapportActes() et services() déjà existants
    // ================================================================


    public function situationParActe_old(Request $request)
    {
        // ── 1. Validation (même pattern que rapportActes) ─────────────
        $request->validate([
            'from' => 'required|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->get('from');
        $to   = $request->get('to') ?? date('Y-m-d');

        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate   = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        // ── 2. Récupération des consultations (même pattern que services()) ──
        //
        // On charge toutes les relations nécessaires en eager loading
        // pour éviter le N+1 (comme tu le fais avec ->with(['patient']))
        // ─────────────────────────────────────────────────────────────────
        $consultations = Consultation::with([
                'services',
                'packages',
                'tests',
                'medicaments',
                'patient',
                'department',
                'transaction',
                'transaction.paiements',          // ← source de paiement ici
                'transaction.invoice',
                'transaction.invoice.items',
                'transaction.invoice.insuranceCompany',
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderBy('created_at')
            ->get();

        // ── 3. Construction du récapitulatif par service ───────────────
        //
        // Structure identique à l'Excel Aprosafe :
        //   service → nb_actes | espece | pm | tpe | chq | assurance | total
        //
        // La source de paiement (ESPECE / PM / TPE / CHQ) est stockée dans
        // invoice_items (via coverage_type polymorphe).
        // On regroupe les items par coverage_type_type pour identifier
        // à quel acte (service, package, test…) correspond chaque paiement.
        // ─────────────────────────────────────────────────────────────────
        $situationParService = []; // clé = nom du service

        foreach ($consultations as $consultation) {

            $transaction = $consultation->transaction ?? null;
            $invoice     = $transaction?->invoice ?? null;

            // -- Récupère les invoice_items et les paiements de la transaction --
            $items     = $invoice?->items    ?? collect();
            $paiements = $transaction?->paiements ?? collect();

            // Ventilation par source : ESPECE / PM / TPE / CHQ
            // On groupe les paiements par source et on somme les montants
            $montantParSource = $paiements
                ->where('type', 'paiement')
                ->groupBy(fn($p) => strtoupper($p->source))
                ->map(fn($groupe) => $groupe->sum('montant'));

            // -- Itération sur chaque invoice_item --
            // Si aucun item → fallback sur les services de la consultation
            if ($items->isNotEmpty()) {

                foreach ($items as $item) {
                    $nomService    = $item->description ?? 'Non défini';
                    $montantItem   = (float) ($item->total_amount             ?? 0);
                    $partPatient   = (float) ($item->patient_amount           ?? 0);
                    $partAssurance = (float) ($item->insurance_covered_amount ?? 0);

                    // Proportion du montant patient ventilée par source
                    // Si plusieurs sources (paiement mixte), on répartit au prorata
                    $totalPaye = $montantParSource->sum();
                    $ratio     = $totalPaye > 0 ? $partPatient / $totalPaye : 0;

                    $espece = ($montantParSource->get('CASH', 0)) * $ratio;
                    $pm     = ($montantParSource->get('MOBILE',     0)) * $ratio;
                    $tpe    = ($montantParSource->get('CARD',    0)) * $ratio;
                    $chq    = ($montantParSource->get('CHQ',    0)) * $ratio;

                    if (!isset($situationParService[$nomService])) {
                        $situationParService[$nomService] = [
                            'service'         => $nomService,
                            'nb_actes'        => 0,
                            'total_espece'    => 0,
                            'total_pm'        => 0,
                            'total_tpe'       => 0,
                            'total_chq'       => 0,
                            'total_assurance' => 0,
                            'total_general'   => 0,
                        ];
                    }

                    $situationParService[$nomService]['nb_actes']++;
                    $situationParService[$nomService]['total_espece']    += $espece;
                    $situationParService[$nomService]['total_pm']        += $pm;
                    $situationParService[$nomService]['total_tpe']       += $tpe;
                    $situationParService[$nomService]['total_chq']       += $chq;
                    $situationParService[$nomService]['total_assurance'] += $partAssurance;
                    $situationParService[$nomService]['total_general']   += $montantItem;
                }

            } else {
                // -- Fallback : pas d'invoice_items → on lit les services
                //    directement sur la consultation (même logique que services())
                $tousLesActes = collect()
                    ->merge($consultation->services  ?? collect())
                    ->merge($consultation->packages  ?? collect())
                    ->merge($consultation->tests     ?? collect());

                if ($tousLesActes->isEmpty()) {
                    $tousLesActes = collect([
                        (object)['name' => 'Consultation générale', 'amount' => $transaction?->total ?? 0]
                    ]);
                }

                $montantTotal  = (float) ($transaction?->total ?? 0);
                $partAssurance = (float) ($invoice?->insurance_amount ?? 0);
                $partPatient   = $montantTotal - $partAssurance;

                $totalPaye = $montantParSource->sum();
                $ratio     = $totalPaye > 0 ? $partPatient / $totalPaye : 0;

                $espece = ($montantParSource->get('CASH', 0)) * $ratio;
                $pm     = ($montantParSource->get('MOBILE',     0)) * $ratio;
                $tpe    = ($montantParSource->get('CARD',    0)) * $ratio;
                $chq    = ($montantParSource->get('CHQ',    0)) * $ratio;

                foreach ($tousLesActes as $acte) {
                    $nomService = $acte->name ?? 'Non défini';

                    if (!isset($situationParService[$nomService])) {
                        $situationParService[$nomService] = [
                            'service'         => $nomService,
                            'nb_actes'        => 0,
                            'total_espece'    => 0,
                            'total_pm'        => 0,
                            'total_tpe'       => 0,
                            'total_chq'       => 0,
                            'total_assurance' => 0,
                            'total_general'   => 0,
                        ];
                    }

                    $situationParService[$nomService]['nb_actes']++;
                    $situationParService[$nomService]['total_espece']    += $espece;
                    $situationParService[$nomService]['total_pm']        += $pm;
                    $situationParService[$nomService]['total_tpe']       += $tpe;
                    $situationParService[$nomService]['total_chq']       += $chq;
                    $situationParService[$nomService]['total_assurance'] += $partAssurance;
                    $situationParService[$nomService]['total_general']   += $montantTotal;
                }
            }
        }

        // Trier par total décroissant (comme ORDER BY total_general DESC)
        usort($situationParService, fn($a, $b) => $b['total_general'] <=> $a['total_general']);

        // ── 4. Totaux ligne de bas de tableau ─────────────────────────
        $totaux = [
            'nb_actes'   => array_sum(array_column($situationParService, 'nb_actes')),
            'espece'     => array_sum(array_column($situationParService, 'total_espece')),
            'pm'         => array_sum(array_column($situationParService, 'total_pm')),
            'tpe'        => array_sum(array_column($situationParService, 'total_tpe')),
            'chq'        => array_sum(array_column($situationParService, 'total_chq')),
            'assurance'  => array_sum(array_column($situationParService, 'total_assurance')),
            'general'    => array_sum(array_column($situationParService, 'total_general')),
        ];

        // ── 5. KPI cards ──────────────────────────────────────────────
        $kpi = [
            'total_patients'    => $consultations->pluck('patient_id')->unique()->count(),
            'total_actes'       => $totaux['nb_actes'],
            'total_espece'      => $totaux['espece'],
            'total_pm'          => $totaux['pm'],
            'total_tpe'         => $totaux['tpe'],
            'total_chq'         => $totaux['chq'],
            'total_assurance'   => $totaux['assurance'],
            'grand_total'       => $totaux['general'],

            // Montant restant à encaisser (debit - credit, comme rapportActes)
            'reste_a_encaisser' => $consultations->sum(function ($c) {
                $t = $c->transaction;
                return max(($t?->total ?? 0) - ($t?->montant_payer ?? 0), 0);
            }),
        ];

        // ── 6. Retour à la vue ────────────────────────────────────────
        return view('reports.tools.compta', compact(
            'situationParService',
            'totaux',
            'kpi',
            'from',
            'to',
        ));
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
        ->orderBy('created_at', 'asc')
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

}
