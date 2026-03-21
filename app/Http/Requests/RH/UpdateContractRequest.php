<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\RH\Contract $contract */
        $contract = $this->route('contrato');

        return $this->user()->can('update', $contract);
    }

    public function rules(): array
    {
        $rules = [
            'contract_type' => ['required', 'string', 'in:indefinite,fixed_term,contractor,intern'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'salary' => ['required', 'numeric', 'min:0'],
            'position' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];

        // La fecha de fin es obligatoria para contratos a término fijo o prácticas
        if (in_array($this->input('contract_type'), ['fixed_term', 'intern'], true)) {
            $rules['end_date'] = ['required', 'date', 'after:start_date'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contract_type.required' => 'El tipo de contrato es obligatorio.',
            'contract_type.in' => 'El tipo de contrato no es válido.',
            'start_date.required' => 'La fecha de inicio del contrato es obligatoria.',
            'start_date.date' => 'La fecha de inicio no tiene un formato válido.',
            'end_date.required' => 'La fecha de fin es obligatoria para contratos a término fijo y prácticas.',
            'end_date.date' => 'La fecha de fin no tiene un formato válido.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'salary.required' => 'El salario es obligatorio.',
            'salary.numeric' => 'El salario debe ser un valor numérico.',
            'salary.min' => 'El salario no puede ser negativo.',
            'position.required' => 'El cargo en el contrato es obligatorio.',
            'position.max' => 'El cargo no puede tener más de :max caracteres.',
        ];
    }
}
