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

    /** Toute modification change les créneaux proposables : invalide le cache du médecin. */
    protected static function booted(): void
    {
        $invalider = fn ($modele) => \App\Support\CacheDisponibilite::invalider($modele->employee_id);

        static::saved($invalider);
        static::deleted($invalider);
    }

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
