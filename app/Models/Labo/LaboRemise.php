<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Remise d'un compte rendu en main propre. Trace immuable : ni modification ni suppression. */
class LaboRemise extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_remises';

    protected $fillable = [
        'etablissement_id', 'demande_id', 'compte_rendu_id', 'version', 'beneficiaire', 'nom_beneficiaire',
        'lien_patient', 'piece_justificative', 'avant_reglement', 'motif_derogation', 'remis_par', 'remis_le',
    ];

    protected $casts = ['avant_reglement' => 'boolean', 'remis_le' => 'datetime'];

    public const BENEFICIAIRES = [
        'patient' => 'Le patient lui-même',
        'representant' => 'Un représentant (parent, conjoint, tuteur…)',
        'prescripteur' => 'Le médecin prescripteur ou son service',
    ];

    public const PIECES = [
        'cni' => 'Carte d\'identité', 'passeport' => 'Passeport', 'permis' => 'Permis de conduire',
        'carte_electeur' => 'Carte d\'électeur', 'recu_labo' => 'Reçu / bon du laboratoire',
        'connu' => 'Personne connue du personnel', 'autre' => 'Autre',
    ];

    public const LIENS = ['Parent', 'Conjoint(e)', 'Enfant', 'Frère / sœur', 'Tuteur légal', 'Autre'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Une remise enregistrée ne se modifie pas.'));
        static::deleting(fn () => throw new \LogicException('Une remise enregistrée ne se supprime pas.'));
    }

    public function demande()
    {
        return $this->belongsTo(LaboDemande::class, 'demande_id');
    }

    public function compteRendu()
    {
        return $this->belongsTo(LaboCompteRendu::class, 'compte_rendu_id');
    }

    public function remisPar()
    {
        return $this->belongsTo(User::class, 'remis_par');
    }
}
