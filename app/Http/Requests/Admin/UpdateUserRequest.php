<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $usuario */
        $usuario = $this->route('usuario');

        return $this->user()->can('update', $usuario);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $usuario */
        $usuario = $this->route('usuario');
        $isSuperAdmin = $this->user()->hasRole('super-admin');

        $rules = [
            'name'               => ['required', 'string', 'max:150'],
            'email'              => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario->id)],
            'document_type'      => ['required', 'string', Rule::in(['CC', 'CE', 'NIT', 'PP', 'TI'])],
            'document_number'    => ['required', 'string', 'max:20', Rule::unique('users', 'document_number')->ignore($usuario->id)],
            'document_issued_at' => ['required', 'date', 'before_or_equal:today'],
            'password'           => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles'              => ['required', 'array', 'min:1'],
            'roles.*'            => ['required', 'string', Rule::exists('roles', 'name')],
        ];

        if ($isSuperAdmin) {
            $rules['institution_id'] = ['required', 'uuid', Rule::exists('institutions', 'id')];
        } else {
            $rules['institution_id'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'               => 'El nombre es obligatorio.',
            'name.max'                    => 'El nombre no puede tener más de :max caracteres.',
            'email.email'                 => 'El correo electrónico no tiene un formato válido.',
            'email.unique'                => 'Ya existe un usuario con este correo electrónico.',
            'document_type.required'      => 'El tipo de documento es obligatorio.',
            'document_type.in'            => 'El tipo de documento debe ser CC, CE, NIT, PP o TI.',
            'document_number.required'    => 'El número de documento es obligatorio.',
            'document_number.max'         => 'El número de documento no puede tener más de :max caracteres.',
            'document_number.unique'      => 'Ya existe un usuario con este número de documento.',
            'document_issued_at.required' => 'La fecha de expedición del documento es obligatoria.',
            'document_issued_at.date'     => 'La fecha de expedición no tiene un formato válido.',
            'document_issued_at.before_or_equal' => 'La fecha de expedición no puede ser una fecha futura.',
            'password.min'                => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed'          => 'La confirmación de contraseña no coincide.',
            'institution_id.required'     => 'La institución es obligatoria.',
            'institution_id.uuid'         => 'El identificador de institución no es válido.',
            'institution_id.exists'       => 'La institución seleccionada no existe.',
            'roles.required'              => 'Debe asignar al menos un rol al usuario.',
            'roles.min'                   => 'Debe asignar al menos un rol al usuario.',
            'roles.*.exists'              => 'Uno de los roles seleccionados no existe.',
        ];
    }

    /**
     * Validaciones adicionales: impide asignar 'super-admin' si el editor no es super-admin.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            if ($this->user()->hasRole('super-admin')) {
                return;
            }

            $roles = $this->input('roles', []);
            if (in_array('super-admin', (array) $roles, strict: true)) {
                $v->errors()->add('roles', 'No está autorizado para asignar el rol de super-admin.');
            }
        });
    }
}
