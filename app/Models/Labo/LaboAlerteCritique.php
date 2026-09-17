<?php

namespace App\Models\Labo;

use App\Models\User;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboAlerteCritique extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_alertes_critiques';

    protected $fillable = ['etablissement_id', 'resultat_id', 'signale_par', 'personne_contactee', 'moyen', 'commentaire', 'signale_le'];

    protected $casts = ['signale_le' => 'datetime'];

    public const MOYENS = [
        'telephone' => 'Appel téléphonique',
        'en_personne' => 'En personne',
        'sms' => 'SMS',
        'deja_informe' => 'Prescripteur déjà informé (patient hospitalisé / sur place)',
    ];

    public function resultat()
    {
        return $this->belongsTo(LaboResultat::class, 'resultat_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'signale_par');
    }
}
