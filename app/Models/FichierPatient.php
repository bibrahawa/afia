<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichierPatient extends Model
{
    protected $fillable = ['patient_id', 'nom_fichier', 'chemin_fichier', 'used_by', 'statut_fichier'];

    public function patient() {
        return $this->belongsTo(Patient::class);
    }

    // Dans le modèle FichierPatient
    public function consultations()
    {
        return $this->belongsToMany(
            Consultation::class,
            'consultation_fichier_patient',
            'fichier_patient_id',
            'consultation_id'
        )->withTimestamps();
    }
}
