<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Enums\Labo\StatutExamen;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboDemandeExamen extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_demande_examens';

    protected $fillable = [
        'etablissement_id', 'demande_id', 'examen_id', 'bilan_id', 'examen_nom', 'prix_applique',
        'statut', 'sous_traite', 'laboratoire_sous_traitant', 'envoye_sous_traitant_le',
        'valide_technique_par', 'valide_technique_le', 'valide_biologique_par', 'valide_biologique_le',
        'commentaire_biologiste', 'publie_le', 'nombre_rectifications', 'motif_derniere_rectification', 'annule_le', 'motif_annulation',
    ];

    protected $casts = [
        'statut' => StatutExamen::class,
        'sous_traite' => 'boolean',
        'prix_applique' => 'decimal:2',
        'envoye_sous_traitant_le' => 'datetime',
        'valide_technique_le' => 'datetime',
        'valide_biologique_le' => 'datetime',
        'publie_le' => 'datetime',
        'annule_le' => 'datetime',
    ];

    public function demande()
    {
        return $this->belongsTo(LaboDemande::class, 'demande_id');
    }

    public function examen()
    {
        return $this->belongsTo(LaboExamen::class, 'examen_id');
    }

    public function resultats()
    {
        return $this->hasMany(LaboResultat::class, 'demande_examen_id');
    }

    public function echantillons()
    {
        return $this->belongsToMany(LaboEchantillon::class, 'labo_echantillon_examen', 'demande_examen_id', 'echantillon_id');
    }

    public function germesIsoles()
    {
        return $this->hasMany(LaboGermeIsole::class, 'demande_examen_id');
    }

    public function validateurTechnique()
    {
        return $this->belongsTo(User::class, 'valide_technique_par');
    }

    public function validateurBiologique()
    {
        return $this->belongsTo(User::class, 'valide_biologique_par');
    }

    public function estVerrouille(): bool
    {
        return $this->statut->estVerrouille();
    }

    /** Heure limite de rendu, pour le suivi des retards (TAT). */
    public function echeance(): ?\Illuminate\Support\Carbon
    {
        $recu = $this->echantillons->whereNotNull('recu_le')->min('recu_le');

        return $recu ? \Illuminate\Support\Carbon::parse($recu)->addHours($this->examen?->delai_rendu_heures ?? 24) : null;
    }
}
