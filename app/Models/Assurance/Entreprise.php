<?php

namespace App\Models\Assurance;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/**
 * Employeur qui souscrit un contrat d'assurance pour son personnel. Il ne
 * règle jamais les soins : la facture se partage entre l'organisme payeur et
 * le patient.
 */
class Entreprise extends Model
{
    use BelongsToEtablissement;

    protected $table = 'entreprises';

    protected $fillable = [
        'etablissement_id', 'nom', 'nif', 'secteur', 'adresse', 'contact_nom',
        'telephone', 'email', 'actif', 'notes',
    ];

    protected $casts = ['actif' => 'boolean'];

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
