<?php

namespace App\Models;

use App\Traits\HeriteEtablissement;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    /** Établissement repris du parent quand la ligne est créée hors session (job, callback). */
    protected static array $etablissementDepuis = ['department_id' => Department::class];

	protected $fillable = ['name', 'description', 'department_id','price'];

        /**
     * Relation avec Department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'package_services');
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class, 'package_tests');
    }

    /**
     * Calculer automatiquement le montant total
     */
    public function calculateTotalAmount()
    {
        $serviceTotal = $this->services()->sum('amount');
        $testTotal = $this->tests()->sum('amount');

        $this->update(['total_amount' => $serviceTotal + $testTotal]);

        return $this->total_amount;
    }

    /**
     * Accessor pour formater le montant
     */
    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 0, ',', ' ') . ' FG';
    }

    /**
     * Scope pour filtrer par département
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function coverage()
    {
        return $this->morphOne(InsuranceCoverage::class, 'coverageable');
    }

}
