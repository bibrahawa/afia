<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => 'required|string|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            // 'slot_duration' RETIRÉ : la durée d'un rendez-vous vient
            // désormais du motif choisi (MotifRdv::duree_minutes_defaut),
            // plus d'une durée de créneau fixée par plage horaire. Ce champ
            // n'était plus lu par aucun service depuis la refonte du moteur
            // de disponibilité — l'exiger aurait continué à induire le
            // médecin en erreur sur ce qu'il configure réellement.
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
