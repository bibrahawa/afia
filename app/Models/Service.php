<?php

namespace App\Models;

use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['department_id' => Department::class];

    
    
	protected $fillable = ['name', 'amount', 'department_id', 'famille_acte'];

    public function department()
    {
        return $this->belongsTo('App\Models\Department');
    }

    public function tests()
    {
    	return $this->hasMany('App\Models\Test');
    }

    public function service_sales()
    {
        return $this->hasMany('App\Models\ServiceSale');
    }

    public function coverage()
    {
        return $this->morphOne(InsuranceCoverage::class, 'coverageable');
    }
}
