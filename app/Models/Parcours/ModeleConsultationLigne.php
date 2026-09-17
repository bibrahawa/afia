<?php

namespace App\Models\Parcours;

use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Un acte d'un modèle : service, package, examen ou médicament avec sa posologie. */
class ModeleConsultationLigne extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['modele_id' => ModeleConsultation::class];

    protected $table = 'modele_consultation_lignes';

    protected $fillable = ['etablissement_id', 'modele_id', 'type', 'acte_id', 'quantite', 'dose', 'frequence', 'duree', 'instructions'];

    public function modele()
    {
        return $this->belongsTo(ModeleConsultation::class, 'modele_id');
    }

    public function posologie(): array
    {
        return ['dose' => $this->dose, 'frequence' => $this->frequence, 'duree' => $this->duree, 'instructions' => $this->instructions];
    }
}
