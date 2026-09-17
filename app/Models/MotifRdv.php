<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class MotifRdv extends Model
{
    use BelongsToEtablissement;

    protected $table = 'motifs_rdv';

    protected $fillable = [
        'etablissement_id', 'department_id', 'code', 'nom',
        'duree_minutes_defaut', 'marge_tampon_minutes', 'couleur',
        'actif', 'ordre_affichage', 'service_id',
    ];

    protected $casts = ['actif' => 'boolean'];

    protected static function booted(): void
    {
        // etablissement_id est dénormalisé depuis le département — jamais
        // saisi manuellement, pour ne pas risquer une incohérence entre
        // le département choisi et le tenant réel.
        static::creating(function (MotifRdv $motif) {
            if ($motif->department_id && ! $motif->etablissement_id) {
                $motif->etablissement_id = Department::find($motif->department_id)?->etablissement_id;
            }
        });
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Médecins explicitement associés (restriction/surcharge de durée).
     * L'ABSENCE d'association ne signifie pas "aucun médecin ne peut faire
     * ce motif" — voir Employee::peutPratiquerMotif() pour le comportement
     * permissif par défaut basé sur l'appartenance au département.
     */
    public function medecinsAssocies()
    {
        return $this->belongsToMany(Employee::class, 'medecin_motif')
            ->withPivot(['duree_minutes', 'actif'])
            ->withTimestamps();
    }

    public function dureePour(?Employee $medecin = null): int
    {
        if ($medecin) {
            $surcharge = $this->medecinsAssocies()
                ->wherePivot('actif', true)
                ->where('employees.id', $medecin->id)
                ->first()?->pivot->duree_minutes;

            if ($surcharge) {
                return (int) $surcharge;
            }
        }

        return (int) $this->duree_minutes_defaut;
    }
}
