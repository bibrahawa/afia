<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antecedent extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'antecedents_medicaux',
        'antecedents_chirurgicaux',
        'antecedents_gyneco_obstetricaux',
        'antecedents_familiaux',
        'allergies',
        'traitements_cours',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
