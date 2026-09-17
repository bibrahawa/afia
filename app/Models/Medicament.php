<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    use BelongsToEtablissement;

    protected $fillable = ['nom', 'forme', 'dosage', 'frequence', 'duree','amount', 'instructions'];
    
    public function coverage()
    {
        return $this->morphOne(InsuranceCoverage::class, 'coverageable');
    }
}
