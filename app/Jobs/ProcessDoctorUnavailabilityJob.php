<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDoctorUnavailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $doctorId;
    protected $unavailableFrom;
    protected $unavailableTo;
    protected $reason;

    public function __construct(int $doctorId, Carbon $unavailableFrom, Carbon $unavailableTo, string $reason = '')
    {
        $this->doctorId = $doctorId;
        $this->unavailableFrom = $unavailableFrom;
        $this->unavailableTo = $unavailableTo;
        $this->reason = $reason;
    }

    public function handle()
    {
        try {
            
            // Récupérer tous les RDV affectés
            $affectedAppointments = Appointment::where('employee_id', $this->doctorId)
                                        ->where('appointment_datetime', '>=', $this->unavailableFrom)
                                        ->where('appointment_datetime', '<=', $this->unavailableTo)
                                        ->whereIn('status', ['pending', 'confirmed'])
                                        ->with(['patient', 'employee'])
                                        ->get();

            Log::info("Traitement indisponibilité médecin", [
                'employee_id' => $this->doctorId,
                'period' => $this->unavailableFrom->format('d/m/Y H:i') . ' - ' . $this->unavailableTo->format('d/m/Y H:i'),
                'affected_appointments' => $affectedAppointments->count(),
                'reason' => $this->reason
            ]);

            foreach ($affectedAppointments as $appointment) {
                
                // Annuler le RDV et declancher le listner pour l'envoi d'sms
                $appointment->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => "Indisponibilité du médecin : " . $this->reason
                ]);

                Log::info("RDV annulé pour indisponibilité", [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'original_date' => $appointment->getFormattedDateAttribute()
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Erreur ProcessDoctorUnavailabilityJob", [
                'employee_id' => $this->doctorId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}