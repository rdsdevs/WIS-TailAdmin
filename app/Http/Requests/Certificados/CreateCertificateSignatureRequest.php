<?php

declare(strict_types=1);

namespace App\Http\Requests\Certificados;

use Illuminate\Foundation\Http\FormRequest;

class CreateCertificateSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'signer_name' => ['required', 'string', 'max:200'],
            'signer_position' => ['required', 'string', 'max:200'],
            'signature_image' => ['required', 'string'],
            'replacement_name' => ['nullable', 'string', 'max:200'],
            'replacement_position' => ['nullable', 'string', 'max:200'],
            'replacement_signature_image' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'signer_name.required' => 'El nombre del firmante es requerido.',
            'signer_position.required' => 'El cargo del firmante es requerido.',
            'signature_image.required' => 'La imagen de la firma es requerida.',
        ];
    }
}
