<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use App\Models\RH\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApplyPositionChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('contrato');

        return $contract instanceof Contract
            && $this->user()->can('applyPositionChange', $contract);
    }

    public function rules(): array
    {
        /** @var Contract $contract */
        $contract = $this->route('contrato');

        return self::rulesFor($contract);
    }

    /**
     * Reglas reutilizables para validar un cambio de cargo. Usadas también
     * desde el componente Livewire del modal.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(Contract $contract): array
    {
        $today = now()->format('Y-m-d');
        $endDate = $contract->end_date?->format('Y-m-d');
        $maxDate = ($endDate !== null && $endDate < $today) ? $endDate : $today;

        return [
            'new_position_id' => [
                'required',
                'uuid',
                Rule::exists('positions', 'id')
                    ->where('institution_id', $contract->institution_id)
                    ->whereNull('deleted_at'),
                Rule::notIn([(string) $contract->position_id]),
            ],
            'change_date' => [
                'required',
                'date',
                'after_or_equal:'.$contract->start_date->format('Y-m-d'),
                'before_or_equal:'.$maxDate,
            ],
            'adjust_compensation' => ['required', 'boolean'],
            'new_amount' => ['nullable', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($this->boolean('adjust_compensation')) {
                $amount = $this->input('new_amount');
                if ($amount === null || $amount === '') {
                    $v->errors()->add(
                        'new_amount',
                        'Debe indicar el nuevo monto cuando ajusta la compensación.',
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        /** @var Contract $contract */
        $contract = $this->route('contrato');

        return [
            'new_position_id.required' => 'Debe seleccionar el nuevo cargo.',
            'new_position_id.uuid' => 'El cargo seleccionado no es válido.',
            'new_position_id.exists' => 'El cargo no pertenece a su institución.',
            'new_position_id.not_in' => 'El nuevo cargo debe ser distinto al cargo actual.',
            'change_date.required' => 'La fecha del cambio es obligatoria.',
            'change_date.date' => 'La fecha del cambio no tiene un formato válido.',
            'change_date.after_or_equal' => 'La fecha del cambio no puede ser anterior al inicio del contrato ('
                .$contract->start_date->format('d/m/Y').').',
            'change_date.before_or_equal' => 'La fecha del cambio no puede ser posterior a hoy.',
            'new_amount.numeric' => 'El nuevo monto debe ser un número.',
            'new_amount.min' => 'El nuevo monto no puede ser negativo.',
            'observations.max' => 'Las observaciones no pueden superar 1000 caracteres.',
        ];
    }
}
