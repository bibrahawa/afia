<?php

namespace App\Http\Controllers\Facturation;

use App\Exceptions\Facturation\OperationFacturationImpossible;
use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Transaction;
use App\Services\BillingService;
use App\Services\Facturation\RemiseService;
use App\Services\PaymentService;
use App\Support\Facturation\SoldeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Caisse : encaissement de la part patient.
 *
 * La part assurance de chaque facture est calculée UNE fois, à la facturation,
 * par le moteur de prise en charge (conventions, plafonds, double assurance).
 * La caisse ne recalcule rien : elle affiche ce que la facture dit, et
 * n'encaisse que la part patient restant due, via PaymentService.
 *
 * Remplace « Paiements en attente » (payments/unpaid) : mêmes fonctions —
 * remises ligne par ligne, recalcul de la prise en charge quand le patient
 * présente sa carte après coup — sans sélection manuelle d'assurances.
 * Remises et recalcul passent par les services existants (RemiseService,
 * BillingService::recalculate), avec leurs garde-fous (facture déjà
 * réclamée, part patient déjà encaissée).
 */
class CaisseController extends Controller
{
    public const MODES = [
        'CASH' => 'Espèces',
        'MOBILE' => 'Mobile Money',
        'CARD' => 'Carte bancaire',
        'TRANSFER' => 'Virement',
    ];

    /** Patients ayant une part patient à régler, du plus gros reste dû au plus petit. */
    public function index(Request $request)
    {
        $pieces = Transaction::whereIn('status', ['pending', 'partial'])
            ->where('total', '>', 0)
            ->with(['patient', 'invoice'])
            ->orderBy('created_at')
            ->get();

        // Versements patient de toutes les pièces en UNE requête (et non deux par
        // pièce) : même règle que SoldeTransaction::resteDuPatient(), paiements
        // annulés exclus par la portée globale de Paiement.
        $verse = Paiement::whereIn('transaction_id', $pieces->pluck('id'))
            ->where('type', Paiement::TYPE_PATIENT)
            ->selectRaw('transaction_id, SUM(montant) as total')
            ->groupBy('transaction_id')
            ->pluck('total', 'transaction_id');

        $resteDu = function (Transaction $t) use ($verse) {
            $partPatient = $t->invoice ? (float) $t->invoice->patient_amount : (float) $t->total;

            return max(0.0, round($partPatient - (float) ($verse[$t->id] ?? 0), 2));
        };

        $patients = $pieces->groupBy('patient_id')
            ->map(function (Collection $liste) use ($resteDu) {
                $reste = $liste->sum($resteDu);

                return [
                    'patient' => $liste->first()->patient,
                    'reste' => round($reste, 2),
                    'pieces' => $liste->count(),
                    'depuis' => $liste->first()->created_at,
                    'assure' => $liste->contains(fn (Transaction $t) => (float) ($t->invoice?->insurance_amount ?? 0) > 0),
                ];
            })
            ->filter(fn ($ligne) => $ligne['patient'] && $ligne['reste'] >= 0.01)
            ->sortByDesc('reste')
            ->values();

        return view('caisse.index', [
            'patients' => $patients,
            'totalDu' => round($patients->sum('reste'), 2),
        ]);
    }

    /** Détail d'un patient : chaque facture, avec la part que l'assurance prend en charge. */
    public function show(int $patientId)
    {
        $patient = Patient::suivisParEtablissement()->findOrFail($patientId);
        $lignes = $this->lignesOuvertes($patient);

        return view('caisse.show', [
            'patient' => $patient,
            'lignes' => $lignes,
            'resteDu' => round($lignes->sum('reste_patient'), 2),
            'modes' => self::MODES,
        ]);
    }

    public function encaisser(Request $request, int $patientId, PaymentService $paiements)
    {
        $patient = Patient::suivisParEtablissement()->findOrFail($patientId);

        $donnees = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'source' => ['required', 'in:' . implode(',', array_keys(self::MODES))],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'montant.required' => 'Indiquez le montant reçu.',
            'montant.min' => 'Le montant doit être supérieur à zéro.',
        ]);

        $resteDu = round($this->lignesOuvertes($patient)->sum('reste_patient'), 2);
        $montant = round((float) $donnees['montant'], 2);

        if ($resteDu < 0.01) {
            return redirect()->route('caisse.index')->with('error', 'Ce patient n\'a plus rien à régler.');
        }

        // On n'encaisse jamais plus que le reste dû : la monnaie se rend au guichet.
        if ($montant - $resteDu >= 0.01) {
            return back()->withInput()->with('error',
                'Le montant dépasse le reste à payer (' . number_format($resteDu, 0, ',', ' ') . ' GNF). Encaissez ce montant et rendez la monnaie.');
        }

        try {
            $resultat = $paiements->payPatientForPatient(
                patient: $patient,
                amount: $montant,
                paymentMethod: $donnees['source'],
                description: $donnees['description'] ?? null,
            );
        } catch (InvalidArgumentException|OperationFacturationImpossible $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $encaisse = (float) $resultat['paid_amount'];
        $reste = round($resteDu - $encaisse, 2);

        $message = 'Encaissé : ' . number_format($encaisse, 0, ',', ' ') . ' GNF.';
        $message .= $reste >= 0.01
            ? ' Reste à payer : ' . number_format($reste, 0, ',', ' ') . ' GNF.'
            : ' Le patient est à jour.';

        return $reste >= 0.01
            ? redirect()->route('caisse.show', $patient->id)->with('success', $message)
            : redirect()->route('caisse.index')->with('success', $patient->full_name . ' — ' . $message);
    }

    /**
     * Remises ligne par ligne sur une facture. Le montant saisi est la remise
     * de la ligne (pas un ajout) ; elle ne réduit que la part patient.
     */
    public function remises(Request $request, int $transactionId, RemiseService $remises)
    {
        $transaction = Transaction::with('invoice')->findOrFail($transactionId);

        $donnees = $request->validate([
            'remises' => ['required', 'array'],
            'remises.*' => ['array'],
            'remises.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $remises->appliquer($transaction, $donnees['remises']);
        } catch (InvalidArgumentException|OperationFacturationImpossible $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('caisse.show', $transaction->patient_id)
            ->with('success', 'Remises enregistrées. Le reste à payer a été mis à jour.');
    }

    /**
     * Recalcul de la prise en charge par le moteur, par exemple quand le
     * patient présente sa carte d'assurance après la facturation.
     */
    public function recalculer(int $transactionId, BillingService $facturation)
    {
        $transaction = Transaction::with('invoice')->findOrFail($transactionId);

        try {
            $facturation->recalculate($transaction);
        } catch (InvalidArgumentException|OperationFacturationImpossible $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('caisse.show', $transaction->patient_id)
            ->with('success', 'Prise en charge recalculée selon les droits du patient à la date des soins.');
    }

    /**
     * Factures du patient dont la part patient n'est pas soldée, avec le
     * découpage calculé par le moteur à la facturation.
     *
     * @return Collection<int, array>
     */
    private function lignesOuvertes(Patient $patient): Collection
    {
        return $patient->transactions()
            ->whereIn('status', ['pending', 'partial'])
            ->where('total', '>', 0)
            ->with(['invoice.insuranceCompany', 'invoice.items'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (Transaction $t) {
                $solde = SoldeTransaction::pour($t);
                $total = (float) $t->total;
                $partAssurance = (float) ($t->invoice?->insurance_amount ?? 0);
                $partPatient = $solde->partPatient;
                $resteDu = (float) $solde->resteDuPatient();

                return [
                    'transaction' => $t,
                    'date' => $t->created_at,
                    'numero' => $t->invoice_no,
                    'libelle' => $t->description ?: ucfirst((string) $t->transactionable_type ?: 'Acte'),
                    'total' => $total,
                    'part_assurance' => $partAssurance,
                    'assureur' => $t->invoice?->insuranceCompany?->name,
                    'part_patient' => $partPatient,
                    'deja_paye' => $solde->payePatient,
                    'lignes' => $t->invoice ? $t->invoice->items : collect(),
                    'reste_patient' => $resteDu,
                    'alertes' => $this->alertes($t->invoice?->alertes_assurance),
                ];
            })
            ->filter(fn ($ligne) => $ligne['reste_patient'] >= 0.01)
            ->values();
    }

    /** Alertes du moteur (plafond atteint, accord préalable manquant…), en texte. */
    private function alertes($brut): array
    {
        return collect((array) $brut)
            ->map(fn ($a) => is_string($a) ? $a : ($a['message'] ?? $a['libelle'] ?? null))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
