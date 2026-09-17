<?php

namespace App\Models\Assurance;

use App\Enums\Assurance\StatutCouverture;
use App\Models\InsuranceCompany;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Police d'assurance : un payeur, un souscripteur éventuel (entreprise), une période. */
class Contrat extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['insurance_company_id' => InsuranceCompany::class];

    protected $table = 'assurance_contrats';

    protected $fillable = [
        'etablissement_id', 'insurance_company_id', 'entreprise_id', 'numero_police',
        'libelle', 'date_debut', 'date_fin', 'statut', 'notes',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'statut' => StatutCouverture::class,
    ];

    protected static function booted(): void
    {
        static::saved(fn (Contrat $contrat) => app(\App\Services\Assurance\ProjectionCouvertureService::class)->pourContrat($contrat));
    }

    public function organismePayeur()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function formules()
    {
        return $this->hasMany(Formule::class)->orderBy('libelle');
    }

    public function adhesions()
    {
        return $this->hasManyThrough(Adhesion::class, Formule::class, 'contrat_id', 'formule_id');
    }

    public function estDeGroupe(): bool
    {
        return $this->entreprise_id !== null;
    }

    public function intitule(): string
    {
        return trim(($this->organismePayeur?->name ?? '') . ' — ' . ($this->libelle ?: 'Police ' . $this->numero_police));
    }
}
