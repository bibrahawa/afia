<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'patient_id', 'department_id',
        'motif', 'signes_cliniques', 'diagnostic', 'medecin_id',
        'observation', 'prochain_rdv', 'prochain_medecin', 'est_facturee'
    ];

    protected $casts = ['signes_cliniques' => 'array'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function medecin()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function medicaments()
    {
        return $this->belongsToMany(Medicament::class, 'consultation_medicament');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'consultation_service');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'consultation_package');
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class, 'consultation_test');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function fichiers()
    {
        return $this->hasMany(FichierConsultation::class);
    }
}

