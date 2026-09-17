<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Maladie à déclaration obligatoire détectée sur un examen validé. Une seule par ligne d'examen. */
class LaboDeclarationMdo extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_declarations_mdo';

    public const A_DECLARER = 'a_declarer';
    public const DECLAREE = 'declaree';
    public const SANS_OBJET = 'sans_objet';

    /** Délais de notification INDICATIFS, à confirmer avec l'autorité sanitaire. */
    public const DELAI_IMMEDIAT_HEURES = 24;
    public const DELAI_HEBDOMADAIRE_HEURES = 168;

    public const STATUTS = [
        self::A_DECLARER => 'À déclarer',
        self::DECLAREE => 'Déclarée',
        self::SANS_OBJET => 'Sans objet (résultat rectifié)',
    ];

    protected $fillable = [
        'etablissement_id', 'demande_examen_id', 'maladie', 'immediate', 'statut',
        'declaree_par', 'declaree_le', 'destinataire', 'reference', 'commentaire',
    ];

    protected $casts = ['immediate' => 'boolean', 'declaree_le' => 'datetime'];

    public function demandeExamen()
    {
        return $this->belongsTo(LaboDemandeExamen::class, 'demande_examen_id');
    }

    public function declareePar()
    {
        return $this->belongsTo(User::class, 'declaree_par');
    }

    /** Délai écoulé depuis la validation, pour prioriser la liste. */
    public function enRetard(): bool
    {
        if ($this->statut !== self::A_DECLARER) {
            return false;
        }

        $valideLe = $this->demandeExamen?->valide_biologique_le ?? $this->created_at;

        return $valideLe->lt(now()->subHours($this->immediate ? self::DELAI_IMMEDIAT_HEURES : self::DELAI_HEBDOMADAIRE_HEURES));
    }
}
