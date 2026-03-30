<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;

class CreateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\RH\Contract::class);
    }

    public function rules(): array
    {
        $rules = [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'collaborator_id' => ['required', 'uuid', 'exists:collaborators,id'],
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

        // Para tipos de contrato a término fijo (FIAA, PTCT, APRE) la fecha de fin es obligatoria.
        $tiposConFechaFin = ['FIAA', 'PTCT', 'APRE'];
        $contractTypeId = $this->input('contract_type_id');
        if ($contractTypeId) {
            $contractType = \App\Models\RH\ContractType::find($contractTypeId);
            if ($contractType && in_array($contractType->code, $tiposConFechaFin, true)) {
                $rules['end_date'] = ['required', 'date', 'after:start_date'];
            }
        }

        // Comprometidos — solo aplica para contratos año >= 2025
        $rules['committed_values'] = ['nullable', 'array'];
        $rules['committed_values.*.accounting_account'] = ['required', 'string', 'max:200'];
        $rules['committed_values.*.cost_center'] = ['required', 'string', 'max:200'];
        $rules['committed_values.*.amount'] = ['required', 'numeric', 'min:0'];

        // Detalle de Nómina (Opcional)
        $rules['payroll_detail.base_salary'] = ['nullable', 'numeric', 'min:0'];
        $rules['payroll_detail.transport_allowance'] = ['nullable', 'numeric', 'min:0'];
        $rules['payroll_detail.non_statutory_bonuses'] = ['nullable', 'numeric', 'min:0'];
        $rules['payroll_detail.sena_rate'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        $rules['payroll_detail.icbf_rate'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        $rules['payroll_detail.compensation_fund_rate'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        $rules['payroll_detail.health_check_verified_at'] = ['nullable', 'date'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'institution_id.required' => 'La institución es obligatoria.',
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'collaborator_id.required' => 'Debe seleccionar un colaborador.',
            'collaborator_id.exists' => 'El colaborador seleccionado no existe.',
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
            'position_email.email' => 'El correo del cargo no tiene un formato válido.',
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
