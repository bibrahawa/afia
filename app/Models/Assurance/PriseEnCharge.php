<?php

namespace App\Models\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Bon de prise en charge / accord préalable délivré par l'organisme payeur
 * pour un bénéficiaire (hospitalisation, imagerie coûteuse…).
 */
class PriseEnCharge extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    public const ACCORDE = 'accorde';
    public const ANNULE = 'annule';

    protected static array $etablissementDepuis = ['beneficiaire_id' => Beneficiaire::class];

    protected $table = 'assurance_prises_en_charge';

    protected $fillable = [
        'etablissement_id', 'beneficiaire_id', 'numero', 'famille_acte', 'montant_accorde',
        'date_debut', 'date_fin', 'statut', 'notes', 'enregistre_par',
    ];

    protected $casts = [
        'famille_acte' => FamilleActe::class,
        'montant_accorde' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function beneficiaire()
    {
        return $this->belongsTo(Beneficiaire::class);
    }

    public function utilisations()
    {
        return $this->hasMany(PecUtilisation::class, 'prise_en_charge_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }

    public function scopeValidesLe(Builder $query, Carbon $date): Builder
    {
        return $query->where('statut', self::ACCORDE)
            ->whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date);
    }

    public function couvre(FamilleActe $famille): bool
    {
        return $this->famille_acte === null || $this->famille_acte === $famille;
    }

    public function montantUtilise(?int $exclureInvoiceId = null): float
    {
        return (float) PecUtilisation::withoutGlobalScope('etablissement')
            ->where('prise_en_charge_id', $this->id)
            ->when($exclureInvoiceId, fn ($q) => $q->where('invoice_id', '!=', $exclureInvoiceId))
            ->sum('montant');
    }

    /** null = pas de plafond propre au bon. */
    public function resteDisponible(?int $exclureInvoiceId = null): ?float
    {
        return $this->montant_accorde === null
            ? null
            : max(0.0, round((float) $this->montant_accorde - $this->montantUtilise($exclureInvoiceId), 2));
    }
}
