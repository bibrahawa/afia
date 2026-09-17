<?php

namespace App\Models;

use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use App\Traits\RattachePatientEtablissement;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HeriteEtablissement, BelongsToEtablissement, RattachePatientEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['department_id' => Department::class];

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
        return $this->belongsToMany(Medicament::class, 'consultation_medicament')->withPivot('quantity');
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

    // Par celle-ci (relation many-to-many)
    public function fichiers()
    {
        return $this->belongsToMany(
            FichierPatient::class, 
            'consultation_fichier_patient', 
            'consultation_id', 
            'fichier_patient_id'
        )->withTimestamps();
    }

    public function invoice()
    {
        return $this->hasOneThrough(Invoice::class, Transaction::class, 'transactionable_id', 'transaction_id')
            ->whereIn('transactions.transactionable_type', \App\Support\Facturation\TypesFacturables::variantes(Consultation::class));
    }

}

