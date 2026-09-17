<?php

namespace App\Models\Parcours;

use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Trace d'un SMS de rappel de consultation prénatale, pour ne pas l'envoyer deux fois. */
class GrossesseRappel extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['grossesse_id' => Grossesse::class];

    protected $table = 'grossesse_rappels';

    protected $fillable = ['etablissement_id', 'grossesse_id', 'semaines', 'envoye_le', 'telephone', 'succes'];

    protected $casts = ['envoye_le' => 'datetime', 'succes' => 'boolean'];

    public function grossesse()
    {
        return $this->belongsTo(Grossesse::class);
    }
}
