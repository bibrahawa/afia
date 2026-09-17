<?php

namespace App\Models\Assurance;

use App\Models\InsuranceClaim;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Document joint à une réclamation (carte, bon, ordonnance, feuille de soins signée…). */
class PieceJustificative extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    public const TYPES = [
        'feuille_soins' => 'Feuille de soins signée',
        'carte' => 'Carte d\'assuré',
        'bon' => 'Bon de prise en charge',
        'ordonnance' => 'Ordonnance / prescription',
        'compte_rendu' => 'Compte rendu / résultats',
        'autre' => 'Autre',
    ];

    /** Stockage privé : jamais servi directement, uniquement via un contrôleur authentifié. */
    public const DISQUE = 'local';

    protected static array $etablissementDepuis = ['insurance_claim_id' => InsuranceClaim::class];

    protected $table = 'assurance_pieces_justificatives';

    protected $fillable = [
        'etablissement_id', 'insurance_claim_id', 'type', 'libelle', 'chemin',
        'nom_original', 'mime', 'taille', 'ajoute_par',
    ];

    protected static function booted(): void
    {
        static::deleted(fn (PieceJustificative $p) => Storage::disk(self::DISQUE)->delete($p->chemin));
    }

    public function reclamation()
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'ajoute_par');
    }

    public function libelleType(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
