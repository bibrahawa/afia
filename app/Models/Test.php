<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
	protected $fillable = ['name', 'report_type', 'description', 'amount'];


	public function test_references()
	{
		return $this->belongsToMany('App\Models\TestReference');
	}

	public function test_reports()
	{
		return $this->hasMany('App\Models\TestReport');
	}

	public function test_examination()
	{
		return $this->hasOne('App\Models\TestExamination');
	}

	public function test_antibiotics()
	{
		return $this->belongsToMany('App\Models\TestAntibiotic');
	}

	public function test_stain()
	{
		return $this->hasOne('App\Models\TestStain');
	}

	public function test_results()
	{
		return $this->hasMany('App\Models\TestResult');
	}

    public function consultations()
    {
        return $this->belongsToMany(Consultation::class);
    }

	public function coverage()
    {
        return $this->morphOne(InsuranceCoverage::class, 'coverageable');
    }

}
