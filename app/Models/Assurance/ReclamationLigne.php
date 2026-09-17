<?php

namespace App\Models\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Models\InsuranceClaim;
use App\Models\InvoiceItem;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Détail d'une réclamation : un acte, ce qui est réclamé, ce que l'assureur accepte. */
class ReclamationLigne extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['insurance_claim_id' => InsuranceClaim::class];

    protected $table = 'insurance_claim_lignes';

    protected $fillable = [
        'etablissement_id', 'insurance_claim_id', 'invoice_item_id', 'description', 'famille_acte',
        'quantite', 'montant_acte', 'taux', 'montant_reclame', 'montant_accepte', 'motif_rejet',
    ];

    protected $casts = [
        'famille_acte' => FamilleActe::class,
        'montant_acte' => 'decimal:2',
        'taux' => 'decimal:2',
        'montant_reclame' => 'decimal:2',
        'montant_accepte' => 'decimal:2',
    ];

    public function reclamation()
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id');
    }

    public function ligneFacture()
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    public function ecart(): float
    {
        return $this->montant_accepte === null ? 0.0 : max(0.0, round((float) $this->montant_reclame - (float) $this->montant_accepte, 2));
    }
}
