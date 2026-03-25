<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Certificado — WIS ASCUN</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="w-full max-w-2xl">
        {{-- Logo/Marca --}}
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-gray-900">WIS ASCUN</h1>
            <p class="text-sm text-gray-500 mt-1">Sistema de verificación de certificados</p>
        </div>

        @if ($notFound)
            {{-- No encontrado --}}
            <div class="rounded-2xl border border-red-200 bg-white p-8 shadow-sm text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-50">
                    <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Certificado no encontrado</h2>
                <p class="mt-2 text-sm text-gray-500">El código de verificación proporcionado no corresponde a ningún certificado válido en nuestro sistema.</p>
            </div>
        @else
            {{-- Certificado encontrado --}}
            @php
                $snap = $certificate->collaborator_snapshot;
                $tipo = $certificate->certificate_type === 'empleado' ? 'Certificado Laboral' : 'Certificado de Contratación';
                $nombre = $snap['is_company'] ? ($snap['company_name'] ?? $snap['full_name'] ?? '') : ($snap['full_name'] ?? '');
                $tipoDocLabel = $snap['document_type_name'] ?? 'Cédula de Ciudadanía';
                $numDoc = $snap['document_number'] ?? '';
            @endphp
            <div class="rounded-2xl border border-green-200 bg-white shadow-sm overflow-hidden">
                {{-- Header verde --}}
                <div class="bg-green-50 border-b border-green-200 px-6 py-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-green-800">Certificado válido</p>
                        <p class="text-sm text-green-600">Este certificado ha sido verificado exitosamente</p>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    {{-- Tipo --}}
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Tipo de certificado</span>
                        <p class="mt-0.5 font-semibold text-gray-900">{{ $tipo }}</p>
                    </div>

                    {{-- Titular --}}
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Titular</span>
                        <p class="mt-0.5 font-semibold text-gray-900">{{ strtoupper($nombre) }}</p>
                        <p class="text-sm text-gray-500">{{ $tipoDocLabel }} N° {{ number_format((float) $numDoc, 0, ',', '.') }}</p>
                    </div>

                    {{-- Institución --}}
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Institución</span>
                        <p class="mt-0.5 font-semibold text-gray-900">{{ strtoupper($certificate->institution?->name ?? 'ASCUN') }}</p>
                    </div>

                    {{-- Fecha de emisión --}}
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Fecha de emisión</span>
                        <p class="mt-0.5 font-semibold text-gray-900">{{ $certificate->issued_at->setTimezone('America/Bogota')->format('d/m/Y H:i') }}</p>
                    </div>

                    {{-- Código de verificación --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Código de verificación</span>
                        <p class="mt-0.5 font-mono text-sm text-gray-700 break-all">{{ $certificate->verification_code }}</p>
                    </div>

                    {{-- Firmante --}}
                    @if ($certificate->signature)
                        <div>
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Firmado por</span>
                            <p class="mt-0.5 font-semibold text-gray-900">{{ strtoupper($certificate->signature->signer_name) }}</p>
                            <p class="text-sm text-gray-500">{{ $certificate->signature->signer_position }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <p class="mt-6 text-center text-xs text-gray-400">
            Para mayor información comuníquese con Gestión Documental al 6231580 Ext. 603
        </p>
    </div>
</body>
</html>
