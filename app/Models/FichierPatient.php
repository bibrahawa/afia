<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichierPatient extends Model
{
    protected $fillable = ['patient_id', 'nom_fichier', 'chemin_fichier', 'used_by', 'statut_fichier'];

    public function patient() {
        return $this->belongsTo(Patient::class);
    }
}
