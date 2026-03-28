<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;

class CreateCollaboratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\RH\Collaborator::class);
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'document_type_id' => ['required_if:is_company,false', 'nullable', 'uuid', 'exists:document_types,id'],
            'document_number' => [
                'required_if:is_company,false',
                'nullable',
                'string',
                'max:20',
                'unique:collaborators,document_number,NULL,id,institution_id,'.$this->input('institution_id'),
            ],
            'document_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'first_name' => ['required_if:is_company,false', 'nullable', 'string', 'max:60'],
            'second_name' => ['nullable', 'string', 'max:60'],
            'first_surname' => ['required_if:is_company,false', 'nullable', 'string', 'max:60'],
            'second_surname' => ['nullable', 'string', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:F,M,O'],
            'is_company' => ['boolean'],
            'company_name' => ['required_if:is_company,true', 'nullable', 'string', 'max:100'],
            'legal_representative' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:Empleado,Contratista'],
            'status_id' => ['required', 'uuid', 'exists:collaborator_statuses,id'],

            // Perfil de Empleado (Opcional)
            'employee_profile.eps' => ['nullable', 'string', 'max:100'],
            'employee_profile.pension_fund' => ['nullable', 'string', 'max:100'],
            'employee_profile.arl' => ['nullable', 'string', 'max:100'],
            'employee_profile.compensation_fund' => ['nullable', 'string', 'max:100'],
            'employee_profile.severance_fund' => ['nullable', 'string', 'max:100'],
            'employee_profile.blood_type' => ['nullable', 'string', 'max:5'],
            'employee_profile.emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'employee_profile.emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'employee_profile.background_check_verified_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.required' => 'La institución es obligatoria.',
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'document_type_id.required' => 'El tipo de documento es obligatorio.',
            'document_type_id.exists' => 'El tipo de documento seleccionado no existe.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.max' => 'El número de documento no puede tener más de :max caracteres.',
            'document_number.unique' => 'Ya existe un colaborador con este número de documento en la institución.',
            'document_issued_at.date' => 'La fecha de expedición no tiene un formato válido.',
            'document_issued_at.before_or_equal' => 'La fecha de expedición no puede ser una fecha futura.',
            'first_name.required_if' => 'El primer nombre es obligatorio para personas naturales.',
            'first_surname.required_if' => 'El primer apellido es obligatorio para personas naturales.',
            'birth_date.date' => 'La fecha de nacimiento no tiene un formato válido.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'gender.in' => 'El género debe ser F (femenino), M (masculino) u O (otro).',
            'company_name.required_if' => 'La razón social es obligatoria para personas jurídicas.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'type.required' => 'El tipo de colaborador es obligatorio.',
            'type.in' => 'El tipo debe ser Empleado o Contratista.',
            'status_id.required' => 'El estado del colaborador es obligatorio.',
            'status_id.exists' => 'El estado seleccionado no existe.',
        ];
    }
}
