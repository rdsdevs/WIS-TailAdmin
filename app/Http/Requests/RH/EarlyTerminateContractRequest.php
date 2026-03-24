<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;

class EarlyTerminateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('contract');

        return $this->user()->can('earlyTerminate', $contract);
    }

    public function rules(): array
    {
        return [
            'early_termination_date' => ['required', 'date', 'before_or_equal:today'],
            'early_termination_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'early_termination_date.required' => 'La fecha de terminación anticipada es obligatoria.',
            'early_termination_date.date' => 'La fecha de terminación anticipada no tiene un formato válido.',
            'early_termination_date.before_or_equal' => 'La fecha de terminación anticipada no puede ser una fecha futura.',
            'early_termination_reason.required' => 'El motivo de terminación anticipada es obligatorio.',
            'early_termination_reason.min' => 'El motivo debe tener al menos :min caracteres.',
            'early_termination_reason.max' => 'El motivo no puede superar :max caracteres.',
        ];
    }
}
