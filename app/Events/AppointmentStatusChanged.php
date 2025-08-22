<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;
    public $oldStatus;
    public $newStatus;

    public function __construct(Appointment $appointment, string $oldStatus, string $newStatus)
    {
        $this->appointment = $appointment;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}