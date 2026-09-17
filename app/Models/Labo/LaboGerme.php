<?php

namespace App\Models\Labo;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboGerme extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_germes';

    protected $fillable = ['etablissement_id', 'modele_id', 'nom', 'actif'];

    protected $casts = ['actif' => 'boolean'];
}
