<?php

namespace App\Models\Labo;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboBilan extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_bilans';

    protected $fillable = ['etablissement_id', 'modele_id', 'code', 'nom', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function examens()
    {
        return $this->belongsToMany(LaboExamen::class, 'labo_bilan_examen', 'bilan_id', 'examen_id');
    }
}
