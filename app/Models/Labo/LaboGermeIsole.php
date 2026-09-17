<?php

namespace App\Models\Labo;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboGermeIsole extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_germes_isoles';

    protected $fillable = ['etablissement_id', 'demande_examen_id', 'germe_id', 'germe_nom', 'numeration'];

    public function antibiogramme()
    {
        return $this->hasMany(LaboAntibiogramme::class, 'germe_isole_id');
    }

    public function demandeExamen()
    {
        return $this->belongsTo(LaboDemandeExamen::class, 'demande_examen_id');
    }
}
