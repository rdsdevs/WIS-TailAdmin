<?php

declare(strict_types=1);

namespace App\Observers\Certificados;

use App\Models\RH\CertificateSignature;
use Illuminate\Support\Facades\Storage;

final class CertificateSignatureObserver
{
    /**
     * Borra los archivos físicos de firma cuando el registro se elimina definitivamente.
     * No se ejecuta en soft-delete: los archivos persisten para permitir restore().
     */
    public function forceDeleted(CertificateSignature $signature): void
    {
        $paths = array_filter([
            $signature->signature_image,
            $signature->replacement_signature_image,
        ]);

        if ($paths === []) {
            return;
        }

        Storage::disk('public')->delete($paths);
    }
}
