<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ComptePatient extends Authenticatable
{
    protected $table = 'comptes_patients';

    protected $fillable = [
        'telephone', 'email', 'statut',
        'code_otp', 'otp_expire_le', 'telephone_verifie_le',
    ];

    protected $hidden = ['code_otp', 'remember_token'];

    protected $casts = [
        'telephone_verifie_le' => 'datetime',
        'otp_expire_le' => 'datetime',
    ];

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'compte_patient')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function patientsEnTutelle()
    {
        return $this->patients()->wherePivot('role', 'tuteur');
    }
}
