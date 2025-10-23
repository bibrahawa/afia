<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppointmentSlot extends Model
{
    use HasFactory;


    protected $fillable = [
        'employee_id',
        'date',
        'time',
        'is_available',
        'reason_unavailable'
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'time' => 'datetime:H:i',
        'is_available' => 'boolean'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function appointment()
    {
        return $this->hasOne(Appointment::class, 'employee_id', 'employee_id')
                            ->where('appointment_date', $this->date)
                            ->where('appointment_time', $this->time);
    }
}
