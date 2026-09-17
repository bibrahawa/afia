<?php

namespace App\Models\Parcours;

use Illuminate\Database\Eloquent\Model;

/**
 * Table OMS (méthode LMS) importée par l'établissement : elle sert à calculer
 * les z-scores de l'enfant. Référence commune à toutes les cliniques, donc pas
 * de cloisonnement par établissement.
 */
class NormeCroissance extends Model
{
    public const POIDS_AGE = 'poids_age';
    public const TAILLE_AGE = 'taille_age';
    public const IMC_AGE = 'imc_age';

    protected $table = 'normes_croissance';

    protected $fillable = ['indicateur', 'sexe', 'mois', 'l', 'm', 's'];

    protected $casts = ['l' => 'float', 'm' => 'float', 's' => 'float', 'mois' => 'integer'];
}
