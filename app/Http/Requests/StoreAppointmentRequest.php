<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Le médecin est obligatoire.',
            'employee_id.exists' => 'Le médecin sélectionné est invalide.',
            'patient_id.required' => 'Le patient est obligatoire.',
            'patient_id.exists' => 'Le patient sélectionné est invalide.',
            'appointment_date.required' => 'La date du rendez-vous est obligatoire.',
            'appointment_date.after_or_equal' => 'La date du rendez-vous doit être aujourd’hui ou dans le futur.',
            'appointment_time.required' => 'L’heure du rendez-vous est obligatoire.',
            'appointment_time.date_format' => 'Le format de l’heure doit être HH:ii.',
            'reason.required' => 'Le motif du rendez-vous est obligatoire.',
        ];
    }
}