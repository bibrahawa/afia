<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToEtablissement;

    protected $fillable = ['etablissement_id', 'name'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function activeEmployees()
    {
        return $this->hasMany(Employee::class)->where('is_active', true);
    }

    public function motifsRdv()
    {
        return $this->hasMany(MotifRdv::class);
    }
}
