<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
