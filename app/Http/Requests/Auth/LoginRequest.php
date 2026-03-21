<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'max:30'],
            'document_issued_at' => ['required', 'date', 'before_or_equal:today'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'El número de cédula es obligatorio.',
            'document_number.string' => 'El número de cédula debe ser texto.',
            'document_number.max' => 'El número de cédula no puede tener más de :max caracteres.',
            'document_issued_at.required' => 'La fecha de expedición del documento es obligatoria.',
            'document_issued_at.date' => 'La fecha de expedición no tiene un formato válido.',
            'document_issued_at.before_or_equal' => 'La fecha de expedición no puede ser una fecha futura.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ];
    }
}
