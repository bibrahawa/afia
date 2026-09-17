<?php

namespace App\Models\Assurance;

use App\Models\InsuranceCompany;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/**
 * Entreprise cliente : employeur qui souscrit un contrat d'assurance pour son
 * personnel, et/ou qui règle directement les soins (convention directe, via
 * son organisme payeur de type « entreprise »).
 */
class Entreprise extends Model
{
    use BelongsToEtablissement;

    protected $table = 'entreprises';

    protected $fillable = [
        'etablissement_id', 'nom', 'nif', 'secteur', 'adresse', 'contact_nom',
        'telephone', 'email', 'organisme_payeur_id', 'actif', 'notes',
    ];

    protected $casts = ['actif' => 'boolean'];

    public function organismePayeur()
    {
        return $this->belongsTo(InsuranceCompany::class, 'organisme_payeur_id');
    }

    public function emplois()
    {
        return $this->hasMany(PatientEmploi::class);
    }

    public function emploisEnCours()
    {
        return $this->emplois()->enCours();
    }

    public function contrats()
    {
        return $this->hasMany(Contrat::class);
    }
}
