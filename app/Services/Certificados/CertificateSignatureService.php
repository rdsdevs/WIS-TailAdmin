<?php

declare(strict_types=1);

namespace App\Services\Certificados;

use App\Models\RH\CertificateSignature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CertificateSignatureService
{
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
     * @param array<string, mixed> $data
     */
    public function create(array $data, string $institutionId): CertificateSignature
    {
        return DB::transaction(function () use ($data, $institutionId): CertificateSignature {
            return CertificateSignature::create([
                'institution_id'             => $institutionId,
                'signer_name'                => $data['signer_name'],
                'signer_position'            => $data['signer_position'],
                'signature_image'            => $data['signature_image'],
                'replacement_name'           => $data['replacement_name'] ?? null,
                'replacement_position'       => $data['replacement_position'] ?? null,
                'replacement_signature_image' => $data['replacement_signature_image'] ?? null,
                'is_active'                  => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Actualiza una firma digital existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(CertificateSignature $signature, array $data): CertificateSignature
    {
        return DB::transaction(function () use ($signature, $data): CertificateSignature {
            $signature->update(array_filter([
                'signer_name'                => $data['signer_name'] ?? null,
                'signer_position'            => $data['signer_position'] ?? null,
                'signature_image'            => $data['signature_image'] ?? null,
                'replacement_name'           => $data['replacement_name'] ?? null,
                'replacement_position'       => $data['replacement_position'] ?? null,
                'replacement_signature_image' => $data['replacement_signature_image'] ?? null,
                'is_active'                  => $data['is_active'] ?? null,
            ], fn ($v) => $v !== null));

            return $signature->fresh();
        });
    }

    /**
     * Elimina una firma. Lanza excepción si tiene certificados emitidos.
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
}
