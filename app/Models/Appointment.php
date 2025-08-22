<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

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
        'cancelled_at', 'cancellation_reason',
        'reminder_sent_at',
        'confirmation_sent_at',
        'patient_confirmed'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'patient_confirmed' => 'boolean'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }


    public function getFormattedDateAttribute()
    {
        return $this->appointment_date->format('d/m/Y') . ' à ' . $this->appointment_time->format('H:i');
    }

    public function smsLogs()
    {
        return $this->hasMany(AppointmentSmsLog::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_datetime', '>', now())
                    ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeNeedingReminder($query)
    {
        return $query->upcoming()
                    ->where('appointment_datetime', '<=', now()->addHours(24))
                    ->where('appointment_datetime', '>=', now()->addHours(23))
                    ->whereNull('reminder_sent_at');
    }

    public function scopeNeedingLastMinuteReminder($query)
    {
        return $query->upcoming()
                    ->where('appointment_datetime', '<=', now()->addHours(2))
                    ->where('appointment_datetime', '>=', now()->addHour())
                    ->whereNotNull('reminder_sent_at')
                    ->whereDoesntHave('smsLogs', function($q) {
                        $q->where('sms_type', 'reminder_2h')
                        ->where('status', 'sent');
                    });
    }

    // Méthodes utilitaires
    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'confirmed']) && 
               $this->appointment_date > now()->addHours(2);
    }

    public function isToday()
    {
        return $this->appointment_date->isToday();
    }

    public function isTomorrow()
    {
        return $this->appointment_date->isTomorrow();
    }

    public function markReminderSent()
    {
        $this->update(['reminder_sent_at' => now()]);
    }

    public function markConfirmationSent()
    {
        $this->update(['confirmation_sent_at' => now()]);
    }
}
