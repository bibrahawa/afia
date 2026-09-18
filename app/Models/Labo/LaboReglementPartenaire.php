<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Paiement reçu d'une clinique partenaire, imputé sur ses créances. */
class LaboReglementPartenaire extends Model
{
    use BelongsToEtablissement;

    public const MODES = ['virement' => 'Virement', 'especes' => 'Espèces', 'cheque' => 'Chèque', 'mobile' => 'Mobile money'];

    protected $table = 'labo_reglements_partenaires';

    protected $fillable = [
        'etablissement_id', 'partenariat_id', 'montant', 'mode', 'reference', 'recu_le', 'notes',
        'enregistre_par', 'annule_le', 'motif_annulation', 'annule_par',
    ];

    protected $casts = ['montant' => 'decimal:2', 'recu_le' => 'date', 'annule_le' => 'datetime'];

    public function estAnnule(): bool
    {
        return $this->annule_le !== null;
    }

    public function partenariat() { return $this->belongsTo(LaboPartenariat::class, 'partenariat_id'); }
    public function imputations() { return $this->hasMany(LaboReglementImputation::class, 'reglement_id'); }
    public function auteur() { return $this->belongsTo(User::class, 'enregistre_par'); }
}
