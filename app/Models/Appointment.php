<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'patient_id',
        'appointment_date',
        'appointment_time',
        'reason',
        'description',
        'status',
        'notes',
        'confirmed_at',
        'cancelled_at'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString());
    }

    public function scopeToday($query)
    {
        return $query->where('appointment_date', now()->toDateString());
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function canBeCancelled()
    {
        return $this->status === 'pending' || $this->status === 'confirmed';
    }

    public function getFormattedDateTimeAttribute()
    {
        return $this->appointment_date->format('d/m/Y') . ' à ' . $this->appointment_time->format('H:i');
    }
}
