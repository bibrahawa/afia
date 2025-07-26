<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{


	protected $fillable = ['name'];

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


}
