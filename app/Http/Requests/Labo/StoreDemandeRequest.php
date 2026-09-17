<?php

namespace App\Http\Requests\Labo;

use App\Enums\Labo\ModeFacturation;
use App\Enums\Labo\OrigineDemande;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('labo.demande.create');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'origine' => ['required', Rule::enum(OrigineDemande::class)],
            'consultation_id' => ['nullable', 'integer', 'exists_etablissement:consultations,id'],
            'prescripteur_employee_id' => ['nullable', 'required_if:origine,interne', 'integer', 'exists:employees,id'],
            'prescripteur_externe' => ['nullable', 'required_if:origine,externe', 'string', 'max:255'],
            'prescripteur_telephone' => ['nullable', 'string', 'max:30'],
            'renseignements_cliniques' => ['nullable', 'string', 'max:2000'],
            'grossesse' => ['sometimes', 'boolean'],
            'semaines_amenorrhee' => ['nullable', 'integer', 'min:1', 'max:45'],
            'a_jeun_confirme' => ['nullable', 'boolean'],
            'urgence' => ['sometimes', 'boolean'],
            // « consultation » (déjà facturée avec la consultation) seulement si la demande en provient.
            'mode_facturation' => ['required', Rule::in(array_filter([
                ModeFacturation::LABO->value,
                ModeFacturation::GRATUIT->value,
                $this->filled('consultation_id') ? ModeFacturation::CONSULTATION->value : null,
            ]))],
            'resultats_retenus_si_impaye' => ['sometimes', 'boolean'],
            'examens' => ['array'],
            'examens.*' => ['integer'],
            'bilans' => ['array'],
            'bilans.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'prescripteur_employee_id.required_if' => 'Choisissez le médecin prescripteur.',
            'prescripteur_externe.required_if' => 'Indiquez le médecin ou l\'établissement prescripteur.',
            'patient_id.required' => 'Sélectionnez ou créez le patient.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'grossesse' => $this->boolean('grossesse'),
            'urgence' => $this->boolean('urgence'),
            'resultats_retenus_si_impaye' => $this->boolean('resultats_retenus_si_impaye'),
        ]);
    }
}
