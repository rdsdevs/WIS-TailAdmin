<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;

class CreatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['super-admin', 'admin', 'rh-manager', 'employee-manager']);
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:150'],
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
            'institution_id.required' => 'La institución es obligatoria.',
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'department_id.required' => 'La dependencia es obligatoria.',
            'department_id.exists' => 'La dependencia seleccionada no existe.',
            'name.required' => 'El nombre del cargo es obligatorio.',
            'name.max' => 'El nombre del cargo no puede tener más de :max caracteres.',
        ];
    }
}
