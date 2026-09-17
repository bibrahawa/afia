<?php

namespace App\Models\Labo;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboSection extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_sections';

    protected $fillable = ['etablissement_id', 'modele_id', 'code', 'nom', 'ordre', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function examens()
    {
        return $this->hasMany(LaboExamen::class, 'section_id')->orderBy('ordre')->orderBy('nom');
    }
}
