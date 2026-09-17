<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Relevé périodique envoyé à une clinique partenaire : les créances d'une période. */
class LaboRelevePartenaire extends Model
{
    use BelongsToEtablissement;

    public const BROUILLON = 'brouillon';
    public const ENVOYE = 'envoye';
    public const SOLDE = 'solde';

    protected $table = 'labo_releves_partenaires';

    protected $fillable = [
        'etablissement_id', 'partenariat_id', 'numero', 'periode_debut', 'periode_fin', 'echeance',
        'montant_total', 'statut', 'date_envoi', 'notes', 'cree_par',
    ];

    protected $casts = [
        'periode_debut' => 'date', 'periode_fin' => 'date', 'echeance' => 'date',
        'date_envoi' => 'datetime', 'montant_total' => 'decimal:2',
    ];

    public function partenariat() { return $this->belongsTo(LaboPartenariat::class, 'partenariat_id'); }
    public function creances() { return $this->hasMany(LaboCreancePartenaire::class, 'releve_id'); }
    public function auteur() { return $this->belongsTo(User::class, 'cree_par'); }

    public function estEnvoye(): bool
    {
        return in_array($this->statut, [self::ENVOYE, self::SOLDE], true);
    }

    public function montantRegle(): float
    {
        return round((float) $this->creances()->sum('montant_regle'));
    }

    public function resteDu(): float
    {
        return max(0, round((float) $this->montant_total - $this->montantRegle()));
    }

    public function enRetard(): bool
    {
        return $this->statut === self::ENVOYE && $this->echeance && $this->echeance->isPast();
    }
}
