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

    /**
     * Valeurs autorisées par la colonne `employees.type` (ENUM stricte) et leur libellé.
     * Toute l'application teste `type === 'Doctor'` pour reconnaître un médecin.
     */
    public const TYPES = [
        'Doctor' => 'Médecin',
        'Nurse' => 'Infirmier / infirmière',
        'Laboratory' => 'Laboratoire',
        'Reception' => 'Accueil',
        'Secretary' => 'Secrétariat',
        'Accountant' => 'Comptabilité / caisse',
        'Pharmacy' => 'Pharmacie',
        'Admin' => 'Administration',
        'Other' => 'Autre',
    ];

    /**
     * CORRIGÉ — les formulaires envoyaient « Docteur », « Secretaire »… (écran Employés)
     * ou le nom du rôle « medecin », « secretaire »… (création d'utilisateur), valeurs
     * refusées par la colonne ENUM : en mode strict la création échouait, sinon le type
     * était vidé et le médecin n'apparaissait dans aucune liste de médecins.
     * Toute saisie est ramenée ici à une valeur autorisée.
     */
    public function setTypeAttribute($valeur): void
    {
        $this->attributes['type'] = self::normaliserType($valeur);
    }

    public static function normaliserType($valeur): string
    {
        if ($valeur === null || $valeur === '') {
            return 'Other';
        }

        if (array_key_exists($valeur, self::TYPES)) {
            return $valeur;
        }

        $cle = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim((string) $valeur)));

        return match (true) {
            in_array($cle, ['medecin', 'docteur', 'doctor', 'dr', 'sage-femme', 'sage femme'], true) => 'Doctor',
            in_array($cle, ['infirmier', 'infirmiere', 'nurse'], true) => 'Nurse',
            in_array($cle, ['laboratoire', 'laborantin', 'biologiste', 'laboratory', 'labo'], true) => 'Laboratory',
            in_array($cle, ['accueil', 'reception', 'receptionniste'], true) => 'Reception',
            in_array($cle, ['secretaire', 'secretariat', 'secretary'], true) => 'Secretary',
            in_array($cle, ['comptable', 'caissier', 'caissiere', 'caisse', 'accountant'], true) => 'Accountant',
            in_array($cle, ['pharmacie', 'pharmacien', 'pharmacy'], true) => 'Pharmacy',
            in_array($cle, ['admin', 'administrateur', 'administration', 'super-admin'], true) => 'Admin',
            default => 'Other',
        };
    }

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Autre';
    }

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
        return preg_replace('/\s+/u', ' ', trim($this->first_name . ' ' . $this->last_name));
    }

    /**
     * Nom à afficher pour un soignant : « Dr Alpha Barry ».
     *
     * Le titre n'est ajouté que s'il manque — plusieurs fiches portent déjà
     * « Dr » dans le prénom, d'où les « Dr Dr » vus à l'écran.
     */
    public function getNomAfficheAttribute(): string
    {
        $nom = $this->full_name;

        if ($this->type !== 'Doctor') {
            return $nom;
        }

        return preg_match('/^(dr|docteur|pr|professeur)\b/iu', $nom) ? $nom : 'Dr ' . $nom;
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
