<?php

namespace App\Models\Labo;

use App\Models\Test;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/**
 * Correspondance entre un examen du laboratoire partenaire et l'acte
 * équivalent au catalogue de la clinique. C'est cet acte que voient les
 * conventions d'assurance de la clinique.
 */
class LaboPartenariatActe extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_partenariat_actes';

    protected $fillable = ['etablissement_id', 'partenariat_id', 'examen_id', 'test_id', 'cree_automatiquement'];

    protected $casts = ['cree_automatiquement' => 'boolean'];

    public function partenariat() { return $this->belongsTo(LaboPartenariat::class, 'partenariat_id'); }
    public function examen() { return $this->belongsTo(LaboExamen::class, 'examen_id'); }
    public function acte() { return $this->belongsTo(Test::class, 'test_id'); }
}
