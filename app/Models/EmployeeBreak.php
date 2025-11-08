<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBreak extends Model
{
    use HasFactory;

    protected $table = 'employee_breaks';

    protected $fillable = [
        'employee_id',
        'day_of_week',
        'start_time',
        'end_time',
        'label',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec l'employé
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Scope pour obtenir les pauses actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour obtenir les pauses d'un jour spécifique
     */
    public function scopeForDay($query, $day)
    {
        return $query->where('day_of_week', $day);
    }
}