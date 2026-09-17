<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/**
 * Document émis. JAMAIS modifié après création (voir le guard dans booted()) :
 * une correction produit une nouvelle version marquée rectificative.
 */
class LaboCompteRendu extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_comptes_rendus';

    protected $fillable = [
        'etablissement_id', 'demande_id', 'version', 'est_partiel', 'est_rectificatif', 'motif_rectification',
        'contenu', 'empreinte', 'pdf_path', 'publie_par', 'publie_le', 'sms_envoye_le',
    ];

    protected $casts = [
        'contenu' => 'array',
        'est_partiel' => 'boolean',
        'est_rectificatif' => 'boolean',
        'publie_le' => 'datetime',
        'sms_envoye_le' => 'datetime',
    ];

    /** Seules ces colonnes techniques peuvent évoluer après émission. */
    private const MODIFIABLES = ['pdf_path', 'sms_envoye_le', 'updated_at'];

    protected static function booted(): void
    {
        static::updating(function (self $cr) {
            $interdites = array_diff(array_keys($cr->getDirty()), self::MODIFIABLES);
            if ($interdites) {
                throw new \LogicException('Un compte rendu émis ne peut pas être modifié : émettez un rectificatif.');
            }
        });

        static::deleting(function () {
            throw new \LogicException('Un compte rendu émis ne peut pas être supprimé.');
        });
    }

    public function demande()
    {
        return $this->belongsTo(LaboDemande::class, 'demande_id');
    }

    public function publiePar()
    {
        return $this->belongsTo(User::class, 'publie_par');
    }

    public function estDerniereVersion(): bool
    {
        return ! static::where('demande_id', $this->demande_id)->where('version', '>', $this->version)->exists();
    }
}
