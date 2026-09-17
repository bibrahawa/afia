<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory, BelongsToEtablissement;

    protected $fillable = [
        'etablissement_id', 'first_name', 'middle_name', 'last_name', 'education',
        'description', 'certificate', 'speciality', 'address',
        'type', 'department_id', 'user_id', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function availabilities()
    {
        return $this->hasMany(EmployeeAvailability::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Legacy : ne sert plus à générer les créneaux proposables (voir
     * DisponibiliteService), uniquement à bloquer manuellement un horaire
     * précis (indisponibilité ponctuelle non couverte par un congé/pause).
     */
    public function slots()
    {
        return $this->hasMany(AppointmentSlot::class);
    }

    public function leaves()
    {
        return $this->hasMany(EmployeeLeave::class);
    }

    public function breaks()
    {
        return $this->hasMany(EmployeeBreak::class);
    }

    public function motifsAssocies()
    {
        return $this->belongsToMany(MotifRdv::class, 'medecin_motif')
            ->withPivot(['duree_minutes', 'actif'])
            ->withTimestamps();
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * RÈGLE RÉVISÉE (21/09/2026) — explicite et sans effet de bord :
     *  - le médecin doit appartenir au département du motif ;
     *  - il pratique le motif SAUF si une ligne `medecin_motif` le marque
     *    actif = false pour lui.
     *
     * La surcharge de durée (duree_minutes) n'a plus AUCUN effet sur l'accès.
     * Avant, une simple surcharge de durée pour un médecin A basculait tout
     * le motif en liste blanche et excluait silencieusement le médecin B.
     * La migration 2026_09_21_090002 a matérialisé ces exclusions implicites
     * pour ne rien changer en production.
     */
    public function peutPratiquerMotif(MotifRdv $motif): bool
    {
        if ((int) $motif->department_id !== (int) $this->department_id) {
            return false;
        }

        $association = $this->motifsAssocies()->where('motifs_rdv.id', $motif->id)->first();

        return ! $association || (bool) $association->pivot->actif;
    }

    // Legacy — conservé pour compatibilité, ne plus utiliser pour le calcul
    // de disponibilité réel (voir DisponibiliteService).
    public function getAvailableSlots($date)
    {
        return $this->slots()
            ->where('date', $date)
            ->where('is_available', true)
            ->orderBy('time')
            ->get();
    }
}
