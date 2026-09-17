<?php

namespace App\Models\Labo;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LaboResultatHistorique extends Model
{
    public $timestamps = false; // append-only

    protected $table = 'labo_resultat_historiques';

    protected $fillable = ['resultat_id', 'ancienne_valeur', 'nouvelle_valeur', 'modifie_par', 'motif'];

    protected $casts = ['created_at' => 'datetime'];

    public function auteur()
    {
        return $this->belongsTo(User::class, 'modifie_par');
    }
}
