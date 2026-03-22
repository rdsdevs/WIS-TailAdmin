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
            'contract_type_id' => ['required', 'uuid', 'exists:contract_types,id'],
            'position_id' => ['nullable', 'uuid', 'exists:positions,id'],
            'contract_number' => ['nullable', 'string', 'max:10'],
            'contract_code' => ['nullable', 'string', 'max:20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'object' => ['nullable', 'string'],
            'obligations' => ['nullable', 'string'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'position_email' => ['nullable', 'email', 'max:100'],
            'status' => ['required', 'in:Vigente,Liquidado,Terminado,Cambio de cargo'],
        ];

        if ($this->input('end_date') === null && $this->input('require_end_date') === 'true') {
            $rules['end_date'] = ['required', 'date', 'after:start_date'];
        }

        // Comprometidos — nullable para permitir actualizar el contrato sin tocar las líneas
        $rules['committed_values'] = ['nullable', 'array'];
        $rules['committed_values.*.accounting_account'] = ['required', 'string', 'max:200'];
        $rules['committed_values.*.cost_center'] = ['required', 'string', 'max:200'];
        $rules['committed_values.*.amount'] = ['required', 'numeric', 'min:0'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contract_type_id.required' => 'El tipo de contrato es obligatorio.',
            'contract_type_id.exists' => 'El tipo de contrato seleccionado no existe.',
            'start_date.required' => 'La fecha de inicio del contrato es obligatoria.',
            'start_date.date' => 'La fecha de inicio no tiene un formato válido.',
            'end_date.required' => 'La fecha de fin es obligatoria para este tipo de contrato.',
            'end_date.date' => 'La fecha de fin no tiene un formato válido.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'salary.numeric' => 'El salario debe ser un valor numérico.',
            'salary.min' => 'El salario no puede ser negativo.',
            'fees.numeric' => 'Los honorarios deben ser un valor numérico.',
            'fees.min' => 'Los honorarios no pueden ser negativos.',
            'status.required' => 'El estado del contrato es obligatorio.',
            'status.in' => 'El estado del contrato no es válido.',
            'committed_values.array' => 'Los valores comprometidos deben ser un listado.',
            'committed_values.*.accounting_account.required' => 'La cuenta contable es obligatoria en cada línea de comprometido.',
            'committed_values.*.accounting_account.max' => 'La cuenta contable no puede superar los 200 caracteres.',
            'committed_values.*.cost_center.required' => 'El centro de costo es obligatorio en cada línea de comprometido.',
            'committed_values.*.cost_center.max' => 'El centro de costo no puede superar los 200 caracteres.',
            'committed_values.*.amount.required' => 'El valor comprometido es obligatorio en cada línea.',
            'committed_values.*.amount.numeric' => 'El valor comprometido debe ser un número.',
            'committed_values.*.amount.min' => 'El valor comprometido no puede ser negativo.',
        ];
    }
}
