<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeAvailability extends Model
{

    use HasFactory;

    protected $fillable = [
        'employee_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration',
        'is_active'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean'
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

    public function generateSlots()
    {
        $slots = [];
        $current = clone $this->start_time;

        while ($current < $this->end_time) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($this->slot_duration);
        }

        return $slots;
    }

}
