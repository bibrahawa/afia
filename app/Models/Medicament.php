<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    protected $fillable = ['nom', 'forme', 'dosage', 'frequence', 'duree','amount', 'instructions'];
    
    public function coverage()
    {
        return $this->morphOne(InsuranceCoverage::class, 'coverageable');
    }
}
