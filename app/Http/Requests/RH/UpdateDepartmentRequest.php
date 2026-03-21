<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['super-admin', 'admin']);
    }

    public function rules(): array
    {
        /** @var \App\Models\RH\Department $department */
        $department = $this->route('departamento');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('departments', 'name')
                    ->where('institution_id', $department->institution_id)
                    ->ignore($department->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la dependencia es obligatorio.',
            'name.max' => 'El nombre no puede tener más de :max caracteres.',
            'name.unique' => 'Ya existe una dependencia con este nombre en la institución.',
        ];
    }
}
