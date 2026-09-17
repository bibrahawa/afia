<?php

namespace App\Models\Assurance;

use App\Models\Invoice;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Part d'un bon de prise en charge consommée par une facture. */
class PecUtilisation extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['prise_en_charge_id' => PriseEnCharge::class];

    protected $table = 'assurance_pec_utilisations';

    protected $fillable = ['etablissement_id', 'prise_en_charge_id', 'invoice_id', 'montant'];

    protected $casts = ['montant' => 'decimal:2'];

    public function priseEnCharge()
    {
        return $this->belongsTo(PriseEnCharge::class, 'prise_en_charge_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
