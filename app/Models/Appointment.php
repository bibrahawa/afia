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
        'motif_rdv_id',
        'duree_minutes',
        'appointment_date',
        'appointment_time',
        'appointment_datetime',
        'description',
        'status',
        'notes',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'reminder_sent_at',
        'last_minute_reminder_sent_at',
        'confirmation_sent_at',
        'patient_confirmed'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i',
        'appointment_datetime' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'last_minute_reminder_sent_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'patient_confirmed' => 'boolean'
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function motifRdv()
    {
        return $this->belongsTo(MotifRdv::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(AppointmentSmsLog::class);
    }

    // ==========================================
    // OBSERVERS POUR AUTO-REMPLIR appointment_datetime
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($appointment) {
            if ($appointment->appointment_date && $appointment->appointment_time) {
                $appointment->appointment_datetime = Carbon::parse(
                    $appointment->appointment_date->format('Y-m-d') . ' ' .
                    $appointment->appointment_time->format('H:i:s')
                );
            }
        });
    }

    // ==========================================
    // SCOPES OPTIMISÉS POUR LES RAPPELS
    // ==========================================

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_datetime', '>', now())
                    ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeNeedingReminder($query)
    {
        $start = now()->addHours(22);
        $end = now()->addHours(26);

        return $query
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('appointment_datetime', [$start, $end])
            ->whereNull('reminder_sent_at')
            ->orderBy('appointment_datetime');
    }

    public function scopeNeedingLastMinuteReminder($query)
    {
        $start = now()->addMinutes(90);
        $end = now()->addMinutes(150);

        return $query
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('appointment_datetime', [$start, $end])
            ->whereNull('last_minute_reminder_sent_at')
            ->orderBy('appointment_datetime');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_datetime', today());
    }

    public function scopeTomorrow($query)
    {
        return $query->whereDate('appointment_datetime', today()->addDay());
    }

    // ==========================================
    // ATTRIBUTS / ACCESSEURS
    // ==========================================

    public function getFormattedDateAttribute()
    {
        return $this->appointment_datetime->format('d/m/Y à H:i');
    }

    public function getFormattedDateShortAttribute()
    {
        return $this->appointment_datetime->format('d/m/Y');
    }

    public function getFormattedTimeAttribute()
    {
        return $this->appointment_datetime->format('H:i');
    }

    // ==========================================
    // MÉTHODES UTILITAIRES
    // ==========================================

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'confirmed']) &&
               $this->appointment_datetime > now()->addHours(2);
    }

    public function isToday()
    {
        return $this->appointment_datetime->isToday();
    }

    public function isTomorrow()
    {
        return $this->appointment_datetime->isTomorrow();
    }

    public function isPast()
    {
        return $this->appointment_datetime < now();
    }

    public function getTimeUntilAppointment()
    {
        return $this->appointment_datetime->diffForHumans();
    }

    public function markReminderSent()
    {
        $this->update(['reminder_sent_at' => now()]);
    }

    public function markLastMinuteReminderSent()
    {
        $this->update(['last_minute_reminder_sent_at' => now()]);
    }

    public function markConfirmationSent()
    {
        $this->update(['confirmation_sent_at' => now()]);
    }

    public function hasReminderBeenSent()
    {
        return !is_null($this->reminder_sent_at);
    }

    public function hasLastMinuteReminderBeenSent()
    {
        return !is_null($this->last_minute_reminder_sent_at);
    }

    public function hasConfirmationBeenSent()
    {
        return !is_null($this->confirmation_sent_at);
    }
}
