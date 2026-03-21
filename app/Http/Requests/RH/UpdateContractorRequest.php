<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractorRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\RH\Contractor $contractor */
        $contractor = $this->route('contratista');

        return $this->user()->can('update', $contractor);
    }

    public function rules(): array
    {
        /** @var \App\Models\RH\Contractor $contractor */
        $contractor = $this->route('contratista');

        return [
            'document_type' => ['required', 'string', 'in:CC,CE,PA,NIT'],
            'document_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('contractors', 'document_number')
                    ->where('institution_id', $contractor->institution_id)
                    ->ignore($contractor->id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_type.in' => 'El tipo de documento debe ser CC, CE, PA o NIT.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.max' => 'El número de documento no puede tener más de :max caracteres.',
            'document_number.unique' => 'Ya existe un contratista con este documento en la institución.',
            'first_name.required' => 'El primer nombre es obligatorio.',
            'first_name.max' => 'El nombre no puede tener más de :max caracteres.',
            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.max' => 'El apellido no puede tener más de :max caracteres.',
            'company_name.max' => 'El nombre de la empresa no puede tener más de :max caracteres.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'phone.max' => 'El teléfono no puede tener más de :max caracteres.',
        ];
    }
}
