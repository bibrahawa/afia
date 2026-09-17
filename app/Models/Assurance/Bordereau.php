<?php

namespace App\Models\Assurance;

use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Envoi groupé des réclamations d'un organisme payeur (relevé). */
class Bordereau extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    public const BROUILLON = 'brouillon';
    public const ENVOYE = 'envoye';

    protected static array $etablissementDepuis = ['insurance_company_id' => InsuranceCompany::class];

    protected $table = 'assurance_bordereaux';

    protected $fillable = [
        'etablissement_id', 'insurance_company_id', 'numero', 'periode_debut', 'periode_fin',
        'statut', 'date_envoi', 'notes', 'cree_par',
    ];

    protected $casts = [
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'date_envoi' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Bordereau $bordereau) {
            if (! $bordereau->numero && $bordereau->etablissement_id) {
                $bordereau->numero = app(\App\Services\NumerotationDocumentService::class)
                    ->numero($bordereau->etablissement_id, 'BRD', 'bordereau-assurance');
            }
        });
    }

    public function organisme()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function reclamations()
    {
        return $this->hasMany(InsuranceClaim::class, 'bordereau_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function estBrouillon(): bool
    {
        return $this->statut === self::BROUILLON;
    }

    public function montantTotal(): float
    {
        return (float) $this->reclamations()->sum('claimed_amount');
    }
}
