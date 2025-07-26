<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichierConsultation extends Model
{
    protected $fillable = ['consultation_id', 'nom_fichier', 'chemin'];

    public function consultation() {
        return $this->belongsTo(Consultation::class);
    }
}

