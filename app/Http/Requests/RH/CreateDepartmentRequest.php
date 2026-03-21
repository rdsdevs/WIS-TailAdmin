<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;

class CreateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['super-admin', 'admin']);
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.required' => 'La institución es obligatoria.',
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'name.required' => 'El nombre de la dependencia es obligatorio.',
            'name.max' => 'El nombre no puede tener más de :max caracteres.',
        ];
    }
}
