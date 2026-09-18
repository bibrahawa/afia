<?php

namespace App\Models\Labo;

use App\Enums\Labo\ModeFacturation;
use App\Models\Etablissement;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Convention entre un laboratoire et une clinique prescriptrice.
 *
 * La ligne appartient au LABORATOIRE (`etablissement_id`) : c'est lui qui
 * ouvre l'accès à son catalogue. La clinique la lit hors scope, par
 * `clinique_id`, via LaboReseauService.
 */
class LaboPartenariat extends Model
{
    use BelongsToEtablissement;

    public const PROPOSE = 'propose';   // le laboratoire a proposé, la clinique doit accepter
    public const ACTIF = 'actif';
    public const SUSPENDU = 'suspendu';
    public const REFUSE = 'refuse';

    protected $table = 'labo_partenariats';

    protected $fillable = [
        'etablissement_id', 'clinique_id', 'statut', 'mode_facturation_defaut', 'clinique_facture_patient',
        'remise_pourcentage', 'delai_paiement_jours', 'contact_nom', 'contact_telephone', 'notes', 'cree_par',
        'propose_le', 'accepte_le', 'accepte_par', 'motif_refus',
    ];

    protected $casts = [
        'remise_pourcentage' => 'decimal:2',
        'clinique_facture_patient' => 'boolean',
        'propose_le' => 'datetime',
        'accepte_le' => 'datetime',
    ];

    public function estPropose(): bool
    {
        return $this->statut === self::PROPOSE;
    }

    public function libelleStatut(): string
    {
        return match ($this->statut) {
            self::PROPOSE => 'En attente de la clinique',
            self::ACTIF => 'Actif',
            self::SUSPENDU => 'Suspendu',
            self::REFUSE => 'Refusé par la clinique',
            default => $this->statut,
        };
    }

    public function laboratoire()
    {
        return $this->belongsTo(Etablissement::class, 'etablissement_id');
    }

    public function clinique()
    {
        return $this->belongsTo(Etablissement::class, 'clinique_id');
    }

    /** La clinique facture son patient (avec ses propres conventions) et paie le labo sur relevé. */
    public function cliniqueFacturePatient(): bool
    {
        return $this->modeFacturation() === ModeFacturation::PARTENAIRE && (bool) $this->clinique_facture_patient;
    }

    public function correspondances()
    {
        return $this->hasMany(LaboPartenariatActe::class, 'partenariat_id');
    }

    public function demandes()
    {
        return $this->hasMany(LaboDemande::class, 'partenariat_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function estActif(): bool
    {
        return $this->statut === self::ACTIF;
    }

    public function modeFacturation(): ModeFacturation
    {
        return $this->mode_facturation_defaut === 'partenaire' ? ModeFacturation::PARTENAIRE : ModeFacturation::LABO;
    }

    /** Prix négocié : prix catalogue du laboratoire moins la remise du partenariat. */
    public function prixNegocie(float $prixCatalogue): float
    {
        return round($prixCatalogue * (100 - (float) $this->remise_pourcentage) / 100);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('statut', self::ACTIF);
    }
}
