<?php

namespace App\Services;

use App\Models\EmployeeBreak;
use DomainException;

/**
 * SIMPLIFIÉ à l'extrême par rapport à l'original : l'ancienne version
 * gérait manuellement la suppression/restauration de lignes
 * `appointment_slots` à chaque activation/désactivation d'une pause.
 * DisponibiliteService lit directement les pauses actives — activer ou
 * désactiver une pause change immédiatement le résultat du calcul, sans
 * aucune donnée dérivée à maintenir en synchronisation.
 */
class DoctorBreakService
{
    public function create(int $employeeId, array $data): EmployeeBreak
    {
        if ($this->hasOverlap($employeeId, $data['day_of_week'], $data['start_time'], $data['end_time'])) {
            throw new DomainException('Une pause existe déjà dans cette plage horaire pour ce jour.');
        }

        return EmployeeBreak::create([
            'employee_id' => $employeeId,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'label' => $data['label'] ?? 'Pause',
            'is_active' => true,
        ]);
    }

    public function update(EmployeeBreak $break, int $employeeId, array $data): void
    {
        if ($break->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        if ($this->hasOverlap($employeeId, $data['day_of_week'], $data['start_time'], $data['end_time'], $break->id)) {
            throw new DomainException('Une pause existe déjà dans cette plage horaire.');
        }

        $break->update([
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'label' => $data['label'] ?? 'Pause',
        ]);
    }

    public function delete(EmployeeBreak $break, int $employeeId): void
    {
        if ($break->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        $break->delete();
    }

    public function toggle(EmployeeBreak $break, int $employeeId, bool $isActive): string
    {
        if ($break->employee_id !== $employeeId) {
            throw new DomainException('Non autorisé.');
        }

        $break->update(['is_active' => $isActive]);

        return $isActive ? 'Pause activée.' : 'Pause désactivée.';
    }

    protected function hasOverlap(int $employeeId, string $dayOfWeek, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $query = EmployeeBreak::where('employee_id', $employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(fn ($q) => $q->where('start_time', '<=', $startTime)->where('end_time', '>', $startTime))
                    ->orWhere(fn ($q) => $q->where('start_time', '<', $endTime)->where('end_time', '>=', $endTime))
                    ->orWhere(fn ($q) => $q->where('start_time', '>=', $startTime)->where('end_time', '<=', $endTime));
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
