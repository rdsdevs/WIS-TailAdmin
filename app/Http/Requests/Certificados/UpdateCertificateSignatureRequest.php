<?php

declare(strict_types=1);

namespace App\Http\Requests\Certificados;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificateSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'signer_name' => ['sometimes', 'string', 'max:200'],
            'signer_position' => ['sometimes', 'string', 'max:200'],
            'signature_image' => ['sometimes', 'string'],
            'replacement_name' => ['nullable', 'string', 'max:200'],
            'replacement_position' => ['nullable', 'string', 'max:200'],
            'replacement_signature_image' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
