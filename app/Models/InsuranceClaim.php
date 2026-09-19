<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class InsuranceClaim extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['invoice_id' => Invoice::class, 'insurance_company_id' => InsuranceCompany::class];

    use HasFactory;

    protected $fillable = [
        'claim_number',
        'invoice_id',
        'insurance_company_id',
        'bordereau_id',
        'patient_insurance_id',
        'patient_id',
        'claimed_amount',
        'approved_amount',
        'paid_amount',
        'montant_transfere_patient',
        'status',
        'submission_date',
        'approval_date',
        'payment_date',
        'rejection_reason',
        'documents', // Le champ JSON
    ];

    /**
     * Reste dû et écart stockés (reste_du_calcule, ecart_calcule), recalculés à
     * chaque enregistrement : l'écran des créances les lit en une requête au lieu
     * de recalculer chaque réclamation. Les écrans de détail gardent le calcul
     * en direct (resteDu(), ecartEnAttente()), qui reste la référence.
     */
    protected static function booted(): void
    {
        static::saving(function (InsuranceClaim $reclamation) {
            try {
                [$reste, $ecart] = $reclamation->montantsCalcules();
                $reclamation->reste_du_calcule = $reste;
                $reclamation->ecart_calcule = $ecart;
                $reclamation->montants_calcules_le = now();
            } catch (\Throwable $e) {
                // Jamais bloquer l'enregistrement : le recalcul nocturne rattrapera.
                \Illuminate\Support\Facades\Log::warning('Réclamation : montants non recalculés', ['id' => $reclamation->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /** [reste dû, écart en attente], arrondis au centime. */
    public function montantsCalcules(): array
    {
        return [round($this->resteDu(), 2), round($this->ecartEnAttente(), 2)];
    }

    protected $casts = [
        'reste_du_calcule' => 'decimal:2',
        'ecart_calcule' => 'decimal:2',
        'montants_calcules_le' => 'datetime',
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'montant_transfere_patient' => 'decimal:2',
        'submission_date' => 'date',
        'approval_date' => 'date',
        'payment_date' => 'date',
        'documents' => 'array', // Cast pour gérer le JSON comme un tableau PHP
    ];

    /**
     * Get the invoice associated with the insurance claim.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    /**
     * Get the insurance company associated with the insurance claim.
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Get the patient associated with the insurance claim.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class); // Assure-toi que le modèle Patient existe
    }

    // ------------------------------------------------------------------ Lot 2c

    public function lignes()
    {
        return $this->hasMany(\App\Models\Assurance\ReclamationLigne::class, 'insurance_claim_id');
    }

    public function pieces()
    {
        return $this->hasMany(\App\Models\Assurance\PieceJustificative::class, 'insurance_claim_id');
    }

    public function bordereau(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Assurance\Bordereau::class, 'bordereau_id');
    }

    public function settlementItems()
    {
        return $this->hasMany(InsuranceSettlementItem::class, 'insurance_claim_id');
    }

    /** Montant que l'organisme doit encore solder (payer ou passer en écart) : réclamé − part transférée au patient. */
    public function montantDu(): float
    {
        return max(0.0, round((float) $this->claimed_amount - (float) $this->montant_transfere_patient, 2));
    }

    /**
     * Déjà soldé par règlement : écarts + parts payées dont l'encaissement n'a
     * pas été annulé.
     */
    public function montantRegle(): float
    {
        return round((float) \Illuminate\Support\Facades\DB::table('insurance_settlement_items as i')
            ->leftJoin('paiements as p', 'p.id', '=', 'i.paiement_id')
            ->where('i.insurance_claim_id', $this->id)
            ->selectRaw('COALESCE(SUM(i.applied_discount_amount + CASE WHEN i.paiement_id IS NULL OR p.annule_le IS NULL THEN i.applied_paid_amount ELSE 0 END), 0) as total')
            ->value('total'), 2);
    }

    public function montantPaye(): float
    {
        return round((float) \Illuminate\Support\Facades\DB::table('insurance_settlement_items as i')
            ->join('paiements as p', 'p.id', '=', 'i.paiement_id')
            ->where('i.insurance_claim_id', $this->id)
            ->whereNull('p.annule_le')
            ->sum('i.applied_paid_amount'), 2);
    }

    public function resteDu(): float
    {
        $reste = max(0.0, round($this->montantDu() - $this->montantRegle(), 2));

        // Historique : des parts assurance ont pu être encaissées avant le lot 2c
        // sans être rattachées à une réclamation. Le reste d'une réclamation ne
        // dépasse jamais ce qui reste dû sur la facture.
        $transaction = $this->invoice?->transaction;

        return $transaction
            ? min($reste, \App\Support\Facturation\SoldeTransaction::pour($transaction)->resteDuAssurance())
            : $reste;
    }

    /** Écart entre le réclamé et l'accepté, pas encore transféré ni soldé. */
    public function ecartEnAttente(): float
    {
        if ($this->approved_amount === null) {
            return 0.0;
        }

        return max(0.0, min($this->resteDu(), round($this->montantDu() - (float) $this->approved_amount, 2)));
    }

    public function estEnvoyee(): bool
    {
        return $this->status !== 'draft';
    }

    /** Statut recalculé à partir des montants (jamais saisi à la main). */
    public function rafraichirStatut(): void
    {
        $du = $this->montantDu();
        $regle = $this->montantRegle();
        $envoyee = $this->status !== 'draft'
            || ($this->bordereau_id && $this->bordereau?->statut === \App\Models\Assurance\Bordereau::ENVOYE);

        $this->paid_amount = $this->montantPaye();

        $this->status = match (true) {
            $this->approved_amount !== null && (float) $this->approved_amount < 0.01 => 'rejected',
            $du < 0.01 || $du - $regle < 0.01 || $this->resteDu() < 0.01 => 'paid',
            $this->approved_amount !== null => 'approved',
            $envoyee => 'submitted',
            default => 'draft',
        };

        $this->save();
    }
}
