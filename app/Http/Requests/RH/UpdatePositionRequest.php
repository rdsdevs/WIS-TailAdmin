<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['super-admin', 'admin', 'rh-manager']);
    }

    public function rules(): array
    {
        /** @var \App\Models\RH\Position $position */
        $position = $this->route('cargo');

        return [
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('positions', 'name')
                    ->where('institution_id', $position->institution_id)
                    ->ignore($position->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],

            // Correos y Funciones (Opcionales)
            'emails' => ['nullable', 'array'],
            'emails.*' => ['required', 'email', 'max:150'],
            'functions' => ['nullable', 'array'],
            'functions.*' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'La dependencia es obligatoria.',
            'department_id.exists' => 'La dependencia seleccionada no existe.',
            'name.required' => 'El nombre del cargo es obligatorio.',
            'name.max' => 'El nombre del cargo no puede tener más de :max caracteres.',
            'name.unique' => 'Ya existe un cargo con este nombre en la institución.',
        ];
    }
}
