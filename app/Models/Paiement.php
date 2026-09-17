<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use BelongsToEtablissement;

    // use HasFactory;

    protected $fillable = [
        'etablissement_id',
        'user_id', 'patient_id','transaction_id',
        'source', 'paiement_no', 'description','type',
        'montant'
    ];

    protected static function booted()
    {
        static::creating(function ($paiement) {
            if (empty($paiement->etablissement_id) && $paiement->transaction_id) {
                $paiement->etablissement_id = Transaction::withoutGlobalScopes()->whereKey($paiement->transaction_id)->value('etablissement_id');
            }

            if (empty($paiement->etablissement_id)) {
                throw new \LogicException('Paiement sans établissement : impossible de le numéroter.');
            }

            $paiement->paiement_no = app(\App\Services\NumerotationDocumentService::class)
                ->numero($paiement->etablissement_id, 'P', 'paiement');
        });
    }

    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function utilisateur() { return $this->belongsTo(User::class, 'user_id'); }
}
