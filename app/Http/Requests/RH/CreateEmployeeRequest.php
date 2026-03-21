<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\RH\Employee::class);
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'position_id' => ['required', 'uuid', 'exists:positions,id'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'document_type' => ['required', 'string', 'in:CC,CE,PA,TI'],
            'document_number' => [
                'required',
                'string',
                'max:30',
                'unique:employees,document_number,NULL,id,institution_id,'.$this->input('institution_id'),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.required' => 'La institución es obligatoria.',
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'position_id.required' => 'El cargo es obligatorio.',
            'position_id.exists' => 'El cargo seleccionado no existe.',
            'department_id.required' => 'La dependencia es obligatoria.',
            'department_id.exists' => 'La dependencia seleccionada no existe.',
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_type.in' => 'El tipo de documento debe ser CC, CE, PA o TI.',
            'document_number.required' => 'El número de cédula es obligatorio.',
            'document_number.max' => 'El número de cédula no puede tener más de :max caracteres.',
            'document_number.unique' => 'Ya existe un empleado con este número de cédula en la institución.',
            'first_name.required' => 'El primer nombre es obligatorio.',
            'first_name.max' => 'El nombre no puede tener más de :max caracteres.',
            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.max' => 'El apellido no puede tener más de :max caracteres.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'phone.max' => 'El teléfono no puede tener más de :max caracteres.',
            'birth_date.date' => 'La fecha de nacimiento no tiene un formato válido.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'salary.required' => 'El salario es obligatorio.',
            'salary.numeric' => 'El salario debe ser un valor numérico.',
            'salary.min' => 'El salario no puede ser negativo.',
        ];
    }
}
