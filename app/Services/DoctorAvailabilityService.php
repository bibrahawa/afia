<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\EmployeeAvailability;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * SIMPLIFIÉ — cette classe déléguait auparavant à AppointmentSlotService
 * pour générer/supprimer des lignes `appointment_slots` à chaque
 * changement d'horaire. Ce mécanisme est mort depuis DisponibiliteService
 * (Phase Rdv) : la disponibilité se calcule maintenant à la volée à
 * partir de EmployeeAvailability/EmployeeBreak/EmployeeLeave/Appointment,
 * jamais d'une grille pré-générée. Le garder aurait continué à faire un
 * travail réel (écritures en base) pour un résultat que plus rien ne lit.
 *
 * `slot_duration` reste en base par compatibilité mais n'est plus utilisé
 * par le calcul de disponibilité (la durée vient désormais du motif) —
 * champ à retirer du formulaire côté vue si tu veux nettoyer complètement.
 */
class DoctorAvailabilityService
{
    public function create(int $employeeId, array $data): EmployeeAvailability
    {
        $exists = EmployeeAvailability::where('employee_id', $employeeId)
            ->where('day_of_week', $data['day_of_week'])
            ->exists();

        if ($exists) {
            throw new DomainException('Une disponibilité existe déjà pour ce jour.');
        }

        return EmployeeAvailability::create([
            'employee_id' => $employeeId,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_active' => true,
        ]);
    }

    public function update(EmployeeAvailability $availability, int $employeeId, array $data): int
    {
        if ($availability->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        return DB::transaction(function () use ($availability, $data) {
            $cancelledCount = $this->cancelAppointmentsOutsideNewSchedule(
                $availability->employee_id,
                $data['day_of_week'],
                $data['start_time'],
                $data['end_time']
            );

            // CORRIGÉ — la case « Active » du formulaire était validée puis ignorée :
            // un médecin ne pouvait pas suspendre un jour sans le supprimer.
            $availability->update([
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'is_active' => (bool) ($data['is_active'] ?? $availability->is_active),
            ]);

            return $cancelledCount;
        });
    }

    public function delete(EmployeeAvailability $availability, int $employeeId): void
    {
        if ($availability->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        $availability->delete();
    }

    /**
     * Toujours nécessaire : si un médecin retire un créneau horaire alors
     * que des rendez-vous y sont déjà pris, ces rendez-vous doivent être
     * signalés/annulés plutôt que de rester "fantômes" en dehors de tout
     * planning déclaré.
     */
    protected function cancelAppointmentsOutsideNewSchedule(
        int $employeeId,
        string $dayOfWeekFr,
        string $newStartTime,
        string $newEndTime
    ): int {
        $appointments = Appointment::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('appointment_date', '>=', Carbon::today())
            ->get()
            ->filter(function (Appointment $a) use ($dayOfWeekFr) {
                return self::JOURS_FR[$a->appointment_date->dayOfWeekIso] === $dayOfWeekFr;
            })
            ->filter(function (Appointment $a) use ($newStartTime, $newEndTime) {
                $heure = $a->appointment_time->format('H:i:s');
                return $heure < $newStartTime || $heure >= $newEndTime;
            });

        foreach ($appointments as $appointment) {
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Modification des horaires du médecin',
            ]);
        }

        return $appointments->count();
    }

    protected const JOURS_FR = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
    ];
}
