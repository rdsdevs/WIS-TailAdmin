<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApplyContractProrogaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('contract');

        return $this->user()->can('applyProroga', $contract);
    }

    public function rules(): array
    {
        return [
            'extension_type' => ['required', Rule::in(['tiempo', 'valor', 'tiempo_y_valor'])],
            'extension_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'extension_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'extension_value' => ['nullable', 'numeric', 'min:0'],
            'committed_value_id' => ['nullable', 'uuid', 'exists:committed_values,id'],
            'approval_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $type = $this->input('extension_type');

            if ($type === 'valor' || $type === 'tiempo_y_valor') {
                if (empty($this->input('extension_value'))) {
                    $v->errors()->add(
                        'extension_value',
                        'El valor de la prórroga es obligatorio cuando el tipo incluye valor.',
                    );
                }
            }

            if ($type === 'tiempo' || $type === 'tiempo_y_valor') {
                $months = $this->input('extension_months');
                $days = $this->input('extension_days');

                if (empty($months) && empty($days)) {
                    $v->errors()->add(
                        'extension_months',
                        'Debe indicar meses o días cuando el tipo de prórroga incluye tiempo.',
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'extension_type.required' => 'El tipo de prórroga es obligatorio.',
            'extension_type.in' => 'El tipo de prórroga seleccionado no es válido.',
            'extension_months.integer' => 'Los meses de prórroga deben ser un número entero.',
            'extension_months.min' => 'Los meses de prórroga no pueden ser negativos.',
            'extension_months.max' => 'Los meses de prórroga no pueden superar 120.',
            'extension_days.integer' => 'Los días de prórroga deben ser un número entero.',
            'extension_days.min' => 'Los días de prórroga no pueden ser negativos.',
            'extension_days.max' => 'Los días de prórroga no pueden superar 365.',
            'extension_value.numeric' => 'El valor de la prórroga debe ser un número.',
            'extension_value.min' => 'El valor de la prórroga no puede ser negativo.',
            'committed_value_id.uuid' => 'El centro de costo seleccionado no es válido.',
            'committed_value_id.exists' => 'El centro de costo seleccionado no existe.',
            'approval_date.required' => 'La fecha de aprobación es obligatoria.',
            'approval_date.date' => 'La fecha de aprobación no tiene un formato válido.',
            'reason.max' => 'El motivo no puede superar 1000 caracteres.',
        ];
    }
}
