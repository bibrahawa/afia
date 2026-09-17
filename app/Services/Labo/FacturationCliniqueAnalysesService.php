<?php

namespace App\Services\Labo;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Labo\LaboPartenariatActe;
use App\Models\Test;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InsuranceCalculationService;
use App\Services\InsuranceConsumptionService;
use App\Services\InvoiceService;
use App\Services\PatientAccountService;
use App\Services\TransactionStatusService;
use App\Support\ContexteTemporaire;
use App\Support\Facturation\TypesFacturables;
use Illuminate\Support\Facades\DB;

/**
 * Analyses envoyées à un laboratoire partenaire, facturées PAR LA CLINIQUE.
 *
 * Pourquoi : l'assurance du patient est enregistrée dans la clinique, avec les
 * conventions négociées par la clinique. Le laboratoire, lui, ne voit ni ce
 * contrat ni ces conventions. Quand le partenariat prévoit « la clinique
 * facture son patient », la facture est donc établie dans la clinique, sur SES
 * actes de catalogue, et le laboratoire lui adresse ensuite son relevé (lot 4b).
 */
class FacturationCliniqueAnalysesService
{
    public function __construct(
        private InsuranceCalculationService $assurance,
        private PatientAccountService $comptes,
        private InvoiceService $factures,
        private InsuranceConsumptionService $consommation,
        private TransactionStatusService $statuts,
    ) {
    }

    /**
     * Facture la demande dans la clinique prescriptrice.
     * À appeler DANS le contexte de la clinique.
     */
    public function facturer(LaboDemande $demande, LaboPartenariat $partenariat, ?User $auteur = null): Transaction
    {
        if ($this->transaction($demande)) {
            throw new OperationLaboImpossible('Ces analyses sont déjà facturées.');
        }

        $items = $this->lignes($demande, $partenariat);

        if (! $items) {
            throw new OperationLaboImpossible('Aucun examen facturable dans cette demande.');
        }

        return DB::transaction(function () use ($demande, $items, $auteur) {
            $demande->loadMissing('patient');

            $calcul = $this->assurance->calculateInsuranceCoverage($demande->patient_id, $items);
            $compte = $this->comptes->credit($demande->patient, (float) $calcul['total_amount']);

            $transaction = Transaction::create([
                'transactionable_type' => TypesFacturables::alias(LaboDemande::class),
                'transactionable_id' => $demande->id,
                'user_id' => $auteur?->id ?? auth()->id(),
                'account_id' => $compte->id,
                'patient_id' => $demande->patient_id,
                'description' => 'Analyses envoyées au laboratoire — ' . $demande->numero,
                'tax_amount' => 0,
                'discount' => 0,
                'sub_total' => $calcul['total_amount'],
                'total' => $calcul['total_amount'],
                'status' => 'pending',
            ]);

            $facture = $this->factures->createOrUpdateInvoice($transaction, $calcul, $items);
            $this->consommation->applyConsumptionsAndClaims($facture, $calcul['insurances_used'] ?? [], $transaction->patient_id);
            $this->statuts->refresh($transaction);

            return $transaction->fresh(['invoice', 'paiements']);
        });
    }

    /** Annulation d'une demande : la facture de la clinique est annulée si rien n'a été encaissé. */
    public function annuler(LaboDemande $demande, LaboPartenariat $partenariat): void
    {
        ContexteTemporaire::pour($partenariat->clinique_id, function () use ($demande) {
            $transaction = $this->transaction($demande);

            if (! $transaction) {
                return;
            }

            if ($transaction->paiements()->exists()) {
                throw new OperationLaboImpossible('La clinique a déjà encaissé ces analyses : traitez le remboursement avant d\'annuler.');
            }

            DB::transaction(function () use ($transaction) {
                if ($facture = $transaction->invoice()->first()) {
                    $this->consommation->rollbackConsumption($facture);
                }

                $this->comptes->retirerTransaction($transaction);
                $transaction->update(['status' => 'cancel']);
            });
        });
    }

    /** Correspondances examen du labo → acte de la clinique, créées au besoin. */
    public function lignes(LaboDemande $demande, LaboPartenariat $partenariat): array
    {
        // Les lignes d'examen appartiennent au LABORATOIRE : dans le contexte de
        // la clinique, le cloisonnement les masque. On les lit explicitement.
        $lignes = \App\Models\Labo\LaboDemandeExamen::withoutGlobalScopes()
            ->where('demande_id', $demande->id)
            ->get();

        $items = [];

        foreach ($lignes as $ligne) {
            if ($ligne->statut->value === 'annule') {
                continue;
            }

            $acte = $this->acteClinique($partenariat, (int) $ligne->examen_id, $ligne->examen_nom, (float) $ligne->prix_applique);
            $prix = (float) $acte->amount;

            $items[] = [
                'acte_type' => TypesFacturables::alias(Test::class),
                'acte_id' => $acte->id,
                'description' => $acte->name,
                'unit_price' => $prix,
                'quantity' => 1,
                'total' => $prix,
            ];
        }

        return $items;
    }

    /**
     * Acte du catalogue de la clinique correspondant à l'examen du laboratoire :
     * correspondance existante, sinon même nom au catalogue, sinon acte créé
     * automatiquement au prix négocié (la clinique le retarifera si elle veut).
     */
    public function acteClinique(LaboPartenariat $partenariat, int $examenId, string $nom, float $prixLabo): Test
    {
        $correspondance = LaboPartenariatActe::where('partenariat_id', $partenariat->id)
            ->where('examen_id', $examenId)
            ->first();

        if ($correspondance && ($acte = Test::find($correspondance->test_id))) {
            return $acte;
        }

        $acte = Test::where('name', $nom)->first();
        $creeAutomatiquement = false;

        if (! $acte) {
            $acte = Test::create([
                'name' => $nom,
                'report_type' => 'numerique',
                'description' => 'Analyse envoyée à ' . ($partenariat->laboratoire?->nom ?? 'un laboratoire partenaire'),
                'amount' => $partenariat->prixNegocie($prixLabo),
            ]);
            $creeAutomatiquement = true;
        }

        LaboPartenariatActe::updateOrCreate(
            ['partenariat_id' => $partenariat->id, 'examen_id' => $examenId],
            ['test_id' => $acte->id, 'cree_automatiquement' => $creeAutomatiquement]
        );

        return $acte;
    }

    private function transaction(LaboDemande $demande): ?Transaction
    {
        return Transaction::whereIn('transactionable_type', TypesFacturables::variantes(LaboDemande::class))
            ->where('transactionable_id', $demande->id)
            ->where('status', '!=', 'cancel')
            ->first();
    }
}
