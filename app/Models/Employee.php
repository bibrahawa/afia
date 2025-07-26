<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Employee extends Model
{

    use HasFactory;

	protected $fillable =
	[
        'first_name', 'middle_name', 'last_name', 'education', 'description', 'certificate', 'speciality', 'address',
        'type' , 'department_id', 'user_id','is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function availabilities()
    {
        return $this->hasMany(EmployeeAvailability::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function slots()
    {
        return $this->hasMany(AppointmentSlot::class);
    }

    public function leaves()
    {
        return $this->hasMany(EmployeeLeave::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAvailableSlots($date)
    {
        return $this->slots()
            ->where('date', $date)
            ->where('is_available', true)
            ->orderBy('time')
            ->get();
    }

    public function isAvailableOn($date, $time)
    {
        // Vérifier si en congé
        $onLeave = $this->leaves()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($onLeave) {
            return false;
        }

        // Vérifier si le slot existe et est disponible
        $slot = $this->slots()
            ->where('date', $date)
            ->where('time', $time)
            ->first();

        return $slot && $slot->is_available;
    }


}
