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
     * Comportement permissif par défaut : appartenir au département du
     * motif suffit, SAUF si une association explicite existe dans
     * `medecin_motif` avec `actif = false` (restriction), ou si le motif
     * a au moins une association et que ce médecin n'en fait pas partie
     * (liste blanche implicite dès qu'une clinique choisit de l'utiliser).
     */
    public function peutPratiquerMotif(MotifRdv $motif): bool
    {
        if ($motif->department_id !== $this->department_id) {
            return false;
        }

        $association = $this->motifsAssocies()->where('motifs_rdv.id', $motif->id)->first();

        if (! $association) {
            // Aucune règle explicite pour ce médecin sur ce motif : permis
            // par défaut via l'appartenance au département — SAUF si
            // d'autres médecins ont, eux, une association active pour ce
            // motif (signe que la clinique a choisi le mode restrictif).
            return ! $motif->medecinsAssocies()->wherePivot('actif', true)->exists();
        }

        return (bool) $association->pivot->actif;
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
