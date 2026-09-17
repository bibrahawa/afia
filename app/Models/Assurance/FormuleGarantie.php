<?php

namespace App\Models\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Règles d'une formule pour une famille d'actes (écarts par rapport au taux général). */
class FormuleGarantie extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['formule_id' => Formule::class];

    protected $table = 'assurance_formule_garanties';

    protected $fillable = ['etablissement_id', 'formule_id', 'famille_acte', 'taux', 'plafond_par_acte', 'exclu', 'accord_prealable'];

    protected $casts = [
        'famille_acte' => FamilleActe::class,
        'taux' => 'decimal:2',
        'plafond_par_acte' => 'decimal:2',
        'exclu' => 'boolean',
        'accord_prealable' => 'boolean',
    ];

    public function formule()
    {
        return $this->belongsTo(Formule::class);
    }
}
