<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{


	protected $fillable =
	[
        'first_name', 'middle_name', 'last_name', 'education', 'phone', 'description', 'certificate', 'speciality', 'address', 'working_day' , 'in_time' , 'out_time' ,
        'type' , 'department_id', 'user_id'
    ];

    public function department()
    {
        return $this->belongsTo('App\Models\Department');
    }

    public function doctor()
    {
        return $this->hasMany('App\Models\Doctor');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }


}
