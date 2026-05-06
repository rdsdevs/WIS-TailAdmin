<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use Illuminate\Foundation\Http\FormRequest;

class CreateCommittedValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('contrato');

        if (! $contract instanceof Contract) {
            return false;
        }

        return $this->user()?->can('create', [CommittedValue::class, $contract]) ?? false;
    }

    public function rules(): array
    {
        return [
            'accounting_account' => ['required', 'string', 'max:200'],
            'cost_center' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'accounting_account.required' => 'La cuenta contable es obligatoria.',
            'accounting_account.max' => 'La cuenta contable no puede tener más de :max caracteres.',
            'cost_center.required' => 'El centro de costo es obligatorio.',
            'cost_center.max' => 'El centro de costo no puede tener más de :max caracteres.',
            'amount.required' => 'El valor es obligatorio.',
            'amount.numeric' => 'El valor debe ser numérico.',
            'amount.min' => 'El valor no puede ser negativo.',
            'amount.max' => 'El valor excede el monto máximo permitido.',
        ];
    }
}
