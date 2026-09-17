<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Enums\Labo\StatutEchantillon;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboEchantillon extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_echantillons';

    protected $fillable = [
        'etablissement_id', 'demande_id', 'code_barres', 'type_echantillon', 'tube', 'statut',
        'preleve_par', 'preleve_le', 'recu_par', 'recu_le', 'rejete_par', 'rejete_le',
        'motif_rejet', 'commentaire', 'remplace_echantillon_id',
    ];

    protected $casts = [
        'statut' => StatutEchantillon::class,
        'preleve_le' => 'datetime',
        'recu_le' => 'datetime',
        'rejete_le' => 'datetime',
    ];

    public function demande()
    {
        return $this->belongsTo(LaboDemande::class, 'demande_id');
    }

    public function examens()
    {
        return $this->belongsToMany(LaboDemandeExamen::class, 'labo_echantillon_examen', 'echantillon_id', 'demande_examen_id');
    }

    public function preleveur()
    {
        return $this->belongsTo(User::class, 'preleve_par');
    }

    public function libelleContenant(): string
    {
        $type = LaboExamen::TYPES_ECHANTILLON[$this->type_echantillon] ?? $this->type_echantillon;

        return $this->tube ? $type . ' — ' . (LaboExamen::TUBES[$this->tube] ?? $this->tube) : $type;
    }
}
