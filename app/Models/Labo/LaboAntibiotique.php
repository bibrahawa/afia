<?php

namespace App\Models\Labo;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboAntibiotique extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_antibiotiques';

    protected $fillable = ['etablissement_id', 'modele_id', 'nom', 'famille', 'ordre', 'actif'];

    protected $casts = ['actif' => 'boolean'];
}
