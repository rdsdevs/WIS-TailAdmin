<?php

declare(strict_types=1);

namespace App\Http\Requests\Certificados;

use App\Models\RH\CertificateSignature;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificateSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $signature = $this->route('signature');

        if (! $signature instanceof CertificateSignature) {
            return false;
        }

        return $this->user()?->can('update', $signature) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'signer_name' => ['sometimes', 'string', 'max:200'],
            'signer_position' => ['sometimes', 'string', 'max:200'],
            // El base64 entrante: ~5 MB (3.7 MB de imagen real + 33% overhead de codificación)
            'signature_image' => ['sometimes', 'string', 'max:5000000', $this->dataUrlImageRule(allowExisting: true)],
            'replacement_name' => ['nullable', 'string', 'max:200'],
            'replacement_position' => ['nullable', 'string', 'max:200'],
            'replacement_signature_image' => ['nullable', 'string', 'max:5000000', $this->dataUrlImageRule(allowExisting: true)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'signature_image.max' => 'La imagen de la firma supera el tamaño máximo permitido (~3.7 MB).',
            'replacement_signature_image.max' => 'La imagen de la firma de reemplazo supera el tamaño máximo permitido (~3.7 MB).',
        ];
    }

    /**
     * Closure que valida que el valor sea un data URL base64 PNG/JPEG, o (en update)
     * un path existente de la firma actual cuando el usuario no carga una nueva imagen.
     */
    private function dataUrlImageRule(bool $allowExisting = false): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($allowExisting): void {
            if (! is_string($value)) {
                $fail('La imagen debe ser un archivo PNG o JPEG válido.');

                return;
            }

            if (preg_match('/^data:image\/(png|jpe?g);base64,/', $value)) {
                return;
            }

            // En update, el frontend puede reenviar el path actual cuando no se cambia la firma.
            if ($allowExisting && str_starts_with($value, 'firmas/')) {
                return;
            }

            $fail('La imagen debe ser un archivo PNG o JPEG válido.');
        };
    }
}
