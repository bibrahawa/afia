<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Prise de rendez-vous depuis la page PUBLIQUE (patient non authentifié).
 *
 * Différence volontaire avec StoreAppointmentRequest (réception) : le client
 * n'envoie JAMAIS de patient_id. Le serveur retrouve le dossier à partir du
 * téléphone. Avant, n'importe qui pouvait réserver au nom de n'importe quel
 * patient en envoyant un identifiant numérique (énumérable).
 */
class PrendreRdvPublicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['telephone' => preg_replace('/\D/', '', (string) $this->input('telephone'))]);
    }

    public function rules(): array
    {
        return [
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'],
            'employee_id' => ['required', 'exists_etablissement:employees,id'],
            'motif_rdv_id' => ['required', 'exists_etablissement:motifs_rdv,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
            'employee_id.required' => 'Le médecin est obligatoire.',
            'motif_rdv_id.required' => 'Le motif du rendez-vous est obligatoire.',
            'appointment_date.required' => 'La date du rendez-vous est obligatoire.',
            'appointment_date.after_or_equal' => 'La date du rendez-vous doit être aujourd’hui ou dans le futur.',
            'appointment_time.required' => 'L’heure du rendez-vous est obligatoire.',
            'appointment_time.date_format' => 'Le format de l’heure doit être HH:mm.',
        ];
    }
}
