<?php

namespace App\Models\Parcours;

use App\Models\Department;
use App\Models\Employee;
use App\Models\MotifRdv;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Protocole réutilisable : diagnostic, signes, ordonnance et examens types.
 * Le médecin l'enregistre depuis une consultation qu'il vient de faire, puis
 * l'applique en un clic — c'est le cœur du gain de temps.
 */
class ModeleConsultation extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['department_id' => Department::class];

    protected $table = 'modeles_consultation';

    protected $fillable = [
        'etablissement_id', 'department_id', 'medecin_id', 'motif_rdv_id', 'libelle',
        'diagnostic', 'signes_cliniques', 'observation', 'utilisations', 'actif', 'cree_par',
    ];

    protected $casts = ['signes_cliniques' => 'array', 'actif' => 'boolean'];

    public function lignes() { return $this->hasMany(ModeleConsultationLigne::class, 'modele_id'); }
    public function medecin() { return $this->belongsTo(Employee::class, 'medecin_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function motifRdv() { return $this->belongsTo(MotifRdv::class); }
    public function auteur() { return $this->belongsTo(User::class, 'cree_par'); }

    /** Modèles visibles par un médecin : les siens et ceux partagés dans son département. */
    public function scopePour(Builder $query, Employee $medecin): Builder
    {
        return $query->where('actif', true)
            ->where('department_id', $medecin->department_id)
            ->where(fn ($q) => $q->whereNull('medecin_id')->orWhere('medecin_id', $medecin->id));
    }

    public function estPartage(): bool
    {
        return $this->medecin_id === null;
    }
}
