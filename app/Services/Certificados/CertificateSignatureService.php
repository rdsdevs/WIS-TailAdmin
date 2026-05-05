<?php

declare(strict_types=1);

namespace App\Services\Certificados;

use App\Models\RH\CertificateSignature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CertificateSignatureService
{
    private const STORAGE_DISK = 'public';

    private const STORAGE_DIR = 'firmas';

    /**
     * Retorna todas las firmas activas de la institución.
     */
    public function getAll(string $institutionId): Collection
    {
        return CertificateSignature::query()
            ->where('institution_id', $institutionId)
            ->orderBy('signer_name')
            ->get();
    }

    /**
     * Retorna las firmas activas disponibles para seleccionar al generar un certificado.
     */
    public function getActive(string $institutionId): Collection
    {
        return CertificateSignature::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('signer_name')
            ->get();
    }

    /**
     * Crea una nueva firma digital.
     *
     * Atómico Storage + DB: si falla la transacción o cualquier upload,
     * se borran los archivos recién subidos antes de re-lanzar la excepción.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, string $institutionId): CertificateSignature
    {
        $createdFiles = [];

        try {
            return DB::transaction(function () use ($data, $institutionId, &$createdFiles): CertificateSignature {
                $signatureImage = $this->storeBase64Image($data['signature_image'], $createdFiles);

                $replacementImage = isset($data['replacement_signature_image'])
                    ? $this->storeBase64Image($data['replacement_signature_image'], $createdFiles)
                    : null;

                return CertificateSignature::create([
                    'institution_id' => $institutionId,
                    'signer_name' => $data['signer_name'],
                    'signer_position' => $data['signer_position'],
                    'signature_image' => $signatureImage,
                    'replacement_name' => $data['replacement_name'] ?? null,
                    'replacement_position' => $data['replacement_position'] ?? null,
                    'replacement_signature_image' => $replacementImage,
                    'is_active' => $data['is_active'] ?? true,
                ]);
            });
        } catch (\Throwable $e) {
            foreach ($createdFiles as $path) {
                $this->deleteFile($path);
            }
            throw $e;
        }
    }

    /**
     * Actualiza una firma digital existente.
     *
     * Atómico: los archivos viejos solo se borran tras commit DB.
     * Si la transacción falla, los nuevos archivos subidos se borran y los viejos persisten.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CertificateSignature $signature, array $data): CertificateSignature
    {
        $createdFiles = [];
        $oldFiles = [];

        try {
            $result = DB::transaction(function () use ($signature, $data, &$createdFiles, &$oldFiles): CertificateSignature {
                $fields = array_filter([
                    'signer_name' => $data['signer_name'] ?? null,
                    'signer_position' => $data['signer_position'] ?? null,
                    'replacement_name' => $data['replacement_name'] ?? null,
                    'replacement_position' => $data['replacement_position'] ?? null,
                ], fn ($v) => $v !== null);

                // Solo procesa la imagen si llega y es base64 nuevo (no si es el path actual)
                if (! empty($data['signature_image']) && $this->isBase64Image($data['signature_image'])) {
                    $fields['signature_image'] = $this->storeBase64Image($data['signature_image'], $createdFiles);
                    $oldFiles[] = $signature->signature_image;
                }

                if (array_key_exists('replacement_signature_image', $data)) {
                    $value = $data['replacement_signature_image'];
                    if ($value === null || $value === '') {
                        $fields['replacement_signature_image'] = null;
                        if ($signature->replacement_signature_image) {
                            $oldFiles[] = $signature->replacement_signature_image;
                        }
                    } elseif ($this->isBase64Image($value)) {
                        $fields['replacement_signature_image'] = $this->storeBase64Image($value, $createdFiles);
                        if ($signature->replacement_signature_image) {
                            $oldFiles[] = $signature->replacement_signature_image;
                        }
                    }
                }

                // is_active siempre se actualiza (un checkbox desmarcado no se envía en POST,
                // prepareForValidation() garantiza que llegue como bool false)
                $fields['is_active'] = (bool) ($data['is_active'] ?? false);

                $signature->update($fields);

                return $signature->fresh();
            });

            // Commit OK: ahora sí podemos borrar archivos viejos.
            foreach ($oldFiles as $path) {
                $this->deleteFile($path);
            }

            return $result;
        } catch (\Throwable $e) {
            foreach ($createdFiles as $path) {
                $this->deleteFile($path);
            }
            throw $e;
        }
    }

    /**
     * Elimina una firma. Lanza excepción si tiene certificados emitidos.
     *
     * Soft-delete: los archivos físicos NO se borran aquí. Permanecen para que
     * un restore() funcione. El borrado físico se delega al observer en forceDeleted.
     */
    public function delete(CertificateSignature $signature): void
    {
        if ($signature->certificates()->exists()) {
            throw new \RuntimeException(
                'No se puede eliminar la firma porque tiene certificados emitidos asociados.'
            );
        }

        $signature->delete();
    }

    /**
     * Decodifica un data URL base64 y guarda el archivo en storage/app/public/firmas/.
     * Retorna el path relativo al disco "public" (e.g. "firmas/abc123.png").
     *
     * @param  list<string>|null  $tracker  Si se pasa por referencia, registra el path creado
     *                                      para permitir rollback en caso de error.
     */
    private function storeBase64Image(string $dataUrl, ?array &$tracker = null): string
    {
        if (! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $dataUrl, $matches)) {
            throw new \InvalidArgumentException('La imagen debe ser un data URL válido (PNG o JPEG).');
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $binary = base64_decode($matches[2], strict: true);

        if ($binary === false) {
            throw new \InvalidArgumentException('La imagen base64 está corrupta.');
        }

        $filename = self::STORAGE_DIR.'/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk(self::STORAGE_DISK)->put($filename, $binary);

        if ($tracker !== null) {
            $tracker[] = $filename;
        }

        return $filename;
    }

    /**
     * Detecta si el valor recibido es un data URL base64 nuevo (vs. un path ya guardado).
     */
    private function isBase64Image(string $value): bool
    {
        return str_starts_with($value, 'data:image/');
    }

    /**
     * Borra un archivo del disco "public" si existe.
     */
    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk(self::STORAGE_DISK)->exists($path)) {
            Storage::disk(self::STORAGE_DISK)->delete($path);
        }
    }
}
