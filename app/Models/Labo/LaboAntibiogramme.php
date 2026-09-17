<?php

namespace App\Models\Labo;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboAntibiogramme extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_antibiogrammes';

    protected $fillable = ['etablissement_id', 'germe_isole_id', 'antibiotique_id', 'antibiotique_nom', 'interpretation', 'valeur'];

    public const INTERPRETATIONS = ['S' => 'Sensible', 'I' => 'Intermédiaire', 'R' => 'Résistant'];
}
