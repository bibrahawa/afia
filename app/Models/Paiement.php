<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use BelongsToEtablissement;

    // use HasFactory;

    /** Versement du patient (part patient). */
    public const TYPE_PATIENT = 'paiement';

    /** Règlement par l'assureur (part assurance) — nom historique de la valeur en base. */
    public const TYPE_ASSURANCE = 'remboursement';

    protected $fillable = [
        'etablissement_id',
        'user_id', 'patient_id','transaction_id',
        'source', 'paiement_no', 'description','type',
        'montant', 'annule_le', 'annule_par', 'motif_annulation',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'annule_le' => 'datetime',
    ];

    protected static function booted()
    {
        // Un paiement annulé reste en base (traçabilité) mais ne compte plus
        // nulle part : relations, sommes, reçus, rapports. Pour le voir :
        // Paiement::avecAnnules() ou ->annules().
        static::addGlobalScope('valides', fn (Builder $q) => $q->whereNull($q->getModel()->getTable() . '.annule_le'));

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

    public function scopeAvecAnnules(Builder $query): Builder
    {
        return $query->withoutGlobalScope('valides');
    }

    public function scopeAnnules(Builder $query): Builder
    {
        return $query->withoutGlobalScope('valides')->whereNotNull($this->getTable() . '.annule_le');
    }

    public function estAnnule(): bool
    {
        return $this->annule_le !== null;
    }

    public function annulePar() { return $this->belongsTo(User::class, 'annule_par'); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function utilisateur() { return $this->belongsTo(User::class, 'user_id'); }
}
