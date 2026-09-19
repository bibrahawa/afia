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
        'visite_id', 'appointment_id', 'grossesse_id', 'statut',
        'patient_id', 'department_id',
        'motif', 'signes_cliniques', 'diagnostic', 'medecin_id',
        'observation', 'prochain_rdv', 'prochain_medecin', 'est_facturee'
    ];

    protected $casts = ['signes_cliniques' => 'array'];

    public const EN_COURS = 'en_cours';
    public const TERMINEE = 'terminee';

    /** Le patient est reparti sans être vu : consultation close, jamais remplie. */
    public const ANNULEE = 'annulee';

    /** Passage du patient (lot 3a) : arrivée, file d'attente, constantes. */
    public function visite()
    {
        return $this->belongsTo(\App\Models\Parcours\Visite::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /** Suivi de grossesse auquel cette consultation prénatale est rattachée (lot 3c). */
    public function grossesse()
    {
        return $this->belongsTo(\App\Models\Parcours\Grossesse::class);
    }

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
        return $this->belongsToMany(Medicament::class, 'consultation_medicament')->withPivot('quantity', 'dose', 'frequence', 'duree', 'instructions');
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

