<?php

namespace App\Models\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MotifRdv;
use App\Models\Patient;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Un passage du patient dans la clinique : arrivée, attente, consultation, sortie. */
class Visite extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    public const ORDRE_ARRIVEE = 'arrivee';
    public const ORDRE_RENDEZ_VOUS = 'rendez_vous';

    protected static array $etablissementDepuis = ['department_id' => Department::class];

    protected $table = 'visites';

    protected $fillable = [
        'etablissement_id', 'patient_id', 'appointment_id', 'medecin_id', 'department_id', 'motif_rdv_id',
        'motif', 'statut', 'urgence', 'rang', 'arrivee_le', 'appele_le', 'terminee_le', 'notes_accueil', 'cree_par',
    ];

    protected $casts = [
        'statut' => StatutVisite::class,
        'urgence' => 'boolean',
        'arrivee_le' => 'datetime',
        'appele_le' => 'datetime',
        'terminee_le' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function medecin() { return $this->belongsTo(Employee::class, 'medecin_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function motifRdv() { return $this->belongsTo(MotifRdv::class); }
    public function consultation() { return $this->hasOne(Consultation::class); }
    public function auteur() { return $this->belongsTo(User::class, 'cree_par'); }

    public function constantes()
    {
        return $this->hasMany(Constante::class)->latest('mesure_le');
    }

    public function derniereConstante()
    {
        return $this->hasOne(Constante::class)->latestOfMany('mesure_le');
    }

    public function scopeDuJour(Builder $query): Builder
    {
        return $query->whereDate('arrivee_le', today());
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->whereIn('statut', [StatutVisite::EnAttente->value, StatutVisite::EnConsultation->value]);
    }

    /**
     * Ordre de la file :
     *  1. urgences ;
     *  2. ordre imposé par l'accueil (« faire passer maintenant », monter, descendre) ;
     *  3. règle par défaut de la clinique : ordre d'arrivée, ou heure de rendez-vous d'abord ;
     *  4. à défaut, ordre d'arrivée.
     */
    public function scopeOrdreFile(Builder $query, ?string $mode = null): Builder
    {
        $mode ??= \App\Support\EtablissementContext::current()?->ordre_file ?? self::ORDRE_ARRIVEE;

        $query->orderByDesc('urgence')->orderByRaw('rang IS NULL')->orderBy('rang');

        if ($mode === self::ORDRE_RENDEZ_VOUS) {
            // Les patients attendus à une heure précise passent avant les venues spontanées.
            $query->orderByRaw('appointment_id IS NULL')
                ->orderByRaw('(SELECT appointment_time FROM appointments WHERE appointments.id = visites.appointment_id)');
        }

        return $query->orderBy('arrivee_le');
    }

    public function minutesAttente(): int
    {
        return (int) $this->arrivee_le->diffInMinutes($this->appele_le ?? now(), true);
    }
}
