<?php

namespace App\Models\Labo;

use App\Enums\Labo\TypeResultat;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboParametre extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_parametres';

    protected $fillable = [
        'etablissement_id', 'examen_id', 'code', 'libelle', 'groupe', 'type_resultat',
        'unite', 'decimales', 'formule', 'options', 'valeur_defaut',
        'obligatoire', 'imprimable', 'ordre',
    ];

    protected $casts = [
        'type_resultat' => TypeResultat::class,
        'options' => 'array',
        'obligatoire' => 'boolean',
        'imprimable' => 'boolean',
    ];

    public function examen()
    {
        return $this->belongsTo(LaboExamen::class, 'examen_id');
    }

    public function valeursReference()
    {
        return $this->hasMany(LaboValeurReference::class, 'parametre_id');
    }
}
