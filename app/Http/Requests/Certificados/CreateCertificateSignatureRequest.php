<?php

declare(strict_types=1);

namespace App\Http\Requests\Certificados;

use App\Models\RH\CertificateSignature;
use Illuminate\Foundation\Http\FormRequest;

class CreateCertificateSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CertificateSignature::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'signer_name' => ['required', 'string', 'max:200'],
            'signer_position' => ['required', 'string', 'max:200'],
            // El base64 entrante: ~5 MB (3.7 MB de imagen real + 33% overhead de codificación)
            'signature_image' => ['required', 'string', 'max:5000000', $this->dataUrlImageRule()],
            'replacement_name' => ['nullable', 'string', 'max:200'],
            'replacement_position' => ['nullable', 'string', 'max:200'],
            'replacement_signature_image' => ['nullable', 'string', 'max:5000000', $this->dataUrlImageRule()],
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
            'signature_image.max' => 'La imagen de la firma supera el tamaño máximo permitido (~3.7 MB).',
            'replacement_signature_image.max' => 'La imagen de la firma de reemplazo supera el tamaño máximo permitido (~3.7 MB).',
        ];
    }

    /**
     * Closure que valida que el valor sea un data URL base64 de PNG o JPEG.
     */
    private function dataUrlImageRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || ! preg_match('/^data:image\/(png|jpe?g);base64,/', $value)) {
                $fail('La imagen debe ser un archivo PNG o JPEG válido.');
            }
        };
    }
}
