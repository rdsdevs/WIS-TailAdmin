<?php

declare(strict_types=1);

namespace App\Services\Certificados;

use App\Models\Certificados\Certificate;
use App\Models\RH\CertificateSignature;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\User;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\Font;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

final class CertificateService
{
    /**
     * Lista paginada de certificados de la institución.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAll(string $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Certificate::query()
            ->where('institution_id', $institutionId)
            ->with(['collaborator', 'signature', 'issuedBy']);

        if (! empty($filters['tipo'])) {
            $query->where('certificate_type', $filters['tipo']);
        }

        if (! empty($filters['buscar'])) {
            $buscar = $filters['buscar'];
            $query->whereHas('collaborator', function ($q) use ($buscar): void {
                $q->where('first_name', 'like', "%{$buscar}%")
                    ->orWhere('first_surname', 'like', "%{$buscar}%")
                    ->orWhere('second_surname', 'like', "%{$buscar}%")
                    ->orWhere('document_number', 'like', "%{$buscar}%")
                    ->orWhere('company_name', 'like', "%{$buscar}%");
            });
        }

        if (! empty($filters['fecha_desde'])) {
            $query->where('issued_at', '>=', Carbon::parse($filters['fecha_desde'])->startOfDay());
        }

        if (! empty($filters['fecha_hasta'])) {
            $query->where('issued_at', '<=', Carbon::parse($filters['fecha_hasta'])->endOfDay());
        }

        return $query->orderByDesc('issued_at')->paginate($perPage);
    }

    /**
     * Genera un certificado laboral para un colaborador empleado.
     */
    public function generateEmployee(
        Collaborator $collaborator,
        CertificateSignature $signature,
        array $contractIds,
        array $options,
        ?string $addressedTo,
        User $issuedBy
    ): Certificate {
        return DB::transaction(function () use (
            $collaborator, $signature, $contractIds, $options, $addressedTo, $issuedBy
        ): Certificate {
            $collaborator->loadMissing('documentType');

            $collaboratorSnapshot = $this->buildCollaboratorSnapshot($collaborator);

            $contractsSnapshot = [];
            foreach ($contractIds as $contractId) {
                $contract = Contract::with(['contractType', 'position'])->findOrFail($contractId);

                $positionChanges = [];
                if (! empty($options['show_position_history'])) {
                    $positionChanges = $contract->positionChangeHistory()
                        ->with(['previousPosition', 'newPosition'])
                        ->orderBy('created_at')
                        ->get()
                        ->map(fn ($h) => [
                            'from_position' => $h->previousPosition?->name ?? '—',
                            'to_position' => $h->newPosition?->name ?? '—',
                            'new_salary' => $h->new_salary,
                            'change_date' => $h->created_at?->format('d/m/Y'),
                            'observations' => $h->observations,
                        ])
                        ->toArray();
                }

                $contractsSnapshot[] = [
                    'contract_code' => $contract->contract_code ?? '',
                    'contract_type' => $contract->contractType?->name ?? '',
                    'start_date' => $contract->start_date?->format('d/m/Y'),
                    'end_date' => $contract->end_date?->format('d/m/Y'),
                    'status' => $contract->status ?? '',
                    'position' => $contract->position?->name ?? '',
                    'department' => '',
                    'salary' => $contract->salary,
                    'position_changes' => $positionChanges,
                ];
            }

            return Certificate::create([
                'institution_id' => $collaborator->institution_id,
                'collaborator_id' => $collaborator->id,
                'certificate_signature_id' => $signature->id,
                'issued_by' => $issuedBy->id,
                'verification_code' => (string) Str::uuid(),
                'certificate_type' => 'empleado',
                'addressed_to' => $addressedTo ?: null,
                'issued_at' => now()->setTimezone('America/Bogota'),
                'collaborator_snapshot' => $collaboratorSnapshot,
                'contracts_snapshot' => $contractsSnapshot,
                'options_snapshot' => [
                    'show_salary' => (bool) ($options['show_salary'] ?? false),
                    'show_position_history' => (bool) ($options['show_position_history'] ?? false),
                ],
            ]);
        });
    }

    /**
     * Genera un certificado de contratación para un colaborador contratista.
     */
    public function generateContractor(
        Collaborator $collaborator,
        CertificateSignature $signature,
        array $contractIds,
        array $options,
        ?string $addressedTo,
        User $issuedBy
    ): Certificate {
        return DB::transaction(function () use (
            $collaborator, $signature, $contractIds, $options, $addressedTo, $issuedBy
        ): Certificate {
            $collaborator->loadMissing('documentType');

            $collaboratorSnapshot = $this->buildCollaboratorSnapshot($collaborator);

            $contractsSnapshot = [];
            foreach ($contractIds as $contractId) {
                $contract = Contract::with(['contractType'])->findOrFail($contractId);

                $extensions = [];
                if (! empty($options['show_prorrogas'])) {
                    $extensions = $contract->extensions()
                        ->orderBy('approval_date')
                        ->get()
                        ->map(fn ($e) => [
                            'extension_type' => $e->extension_type,
                            'extension_months' => $e->extension_months,
                            'extension_days' => $e->extension_days,
                            'extension_value' => $e->extension_value,
                            'new_end_date' => $e->new_end_date?->format('d/m/Y'),
                            'approval_date' => $e->approval_date?->format('d/m/Y'),
                            'reason' => $e->reason,
                        ])
                        ->toArray();
                }

                $contractsSnapshot[] = [
                    'contract_code' => $contract->contract_code ?? '',
                    'contract_type' => $contract->contractType?->name ?? '',
                    'object' => $contract->object ?? '',
                    'obligations' => $contract->obligations ?? '',
                    'start_date' => $contract->start_date?->format('d/m/Y'),
                    'end_date' => $contract->end_date?->format('d/m/Y'),
                    'salary' => $contract->salary,
                    'fees' => $contract->fees,
                    'status' => $contract->status ?? '',
                    'early_termination_date' => $contract->early_termination_date?->format('d/m/Y'),
                    'early_termination_reason' => $contract->early_termination_reason,
                    'extensions' => $extensions,
                ];
            }

            return Certificate::create([
                'institution_id' => $collaborator->institution_id,
                'collaborator_id' => $collaborator->id,
                'certificate_signature_id' => $signature->id,
                'issued_by' => $issuedBy->id,
                'verification_code' => (string) Str::uuid(),
                'certificate_type' => 'contratista',
                'addressed_to' => $addressedTo ?: null,
                'issued_at' => now()->setTimezone('America/Bogota'),
                'collaborator_snapshot' => $collaboratorSnapshot,
                'contracts_snapshot' => $contractsSnapshot,
                'options_snapshot' => [
                    'show_object' => (bool) ($options['show_object'] ?? false),
                    'show_obligations' => (bool) ($options['show_obligations'] ?? false),
                    'show_value' => (bool) ($options['show_value'] ?? false),
                    'show_prorrogas' => (bool) ($options['show_prorrogas'] ?? false),
                    'show_early_termination' => (bool) ($options['show_early_termination'] ?? false),
                ],
            ]);
        });
    }

    public function findByVerificationCode(string $code): ?Certificate
    {
        return Certificate::with(['signature', 'institution'])
            ->where('verification_code', $code)
            ->first();
    }

    /**
     * Construye y retorna el objeto PDF listo para descargar.
     */
    public function buildPdf(Certificate $certificate): \Mccarlosen\LaravelMpdf\LaravelMpdf
    {
        $certificate->loadMissing(['signature', 'institution']);

        $signature = $certificate->signature;
        $institution = $certificate->institution;

        $qrUrl = route('certificados.verificar', $certificate->verification_code);

        // Generar QR con etiqueta integrada usando Endroid
        $fontPath = resource_path('pdf-assets/fonts/arial.ttf');
        if (! file_exists($fontPath)) {
            $fontPath = storage_path('fonts/arial.ttf');
        }

        $qrResult = Builder::create()
            ->writer(new PngWriter)
            ->data($qrUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(400)
            ->margin(0)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->labelText('Escanea para validar el certificado')
            ->labelFont(new Font($fontPath, 19))
            ->labelAlignment(LabelAlignment::Center)
            ->build();

        $qrBase64 = $qrResult->getDataUri();

        $logoBase64 = $institution?->logo
            ? 'data:image/png;base64,'.$institution->logo
            : $this->loadAssetBase64('logo.png', 'image/png');

        $footerBase64 = $this->loadAssetBase64('footer.png', 'image/png');

        $signatureBase64 = null;
        if ($signature && $signature->signature_image) {
            $signatureBase64 = $this->loadSignatureBase64($signature->signature_image);
        }

        $viewName = $certificate->certificate_type === 'empleado'
            ? 'pdf.certificado-empleado'
            : 'pdf.certificado-contratista';

        $data = [
            'certificate' => $certificate,
            'collaborator_snapshot' => $certificate->collaborator_snapshot,
            'contracts_snapshot' => $certificate->contracts_snapshot,
            'options_snapshot' => $certificate->options_snapshot,
            'signature' => $signature,
            'signature_base64' => $signatureBase64,
            'qr_base64' => $qrBase64,
            'logo_base64' => $logoBase64,
            'footer_base64' => $footerBase64,
            'institution_name' => $institution?->name ?? 'ASCUN',
            'addressed_to' => $certificate->getAddressedToLabel(),
            'issued_at' => $certificate->issued_at,
            'date' => $certificate->issued_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
        ];

        return PDF::loadView($viewName, $data, [], [
            'format' => 'Letter',
            'margin_left' => 32,   /* Aumentado de 25 a 32 para estrechar el bloque de texto */
            'margin_right' => 32,  /* Aumentado de 25 a 32 */
            'margin_top' => 60,
            'margin_bottom' => 35, /* Aumentado para proteger el footer */
            'margin_header' => 10,
            'margin_footer' => 10,
            'default_font' => 'arial',
        ]);
    }

    private function loadAssetBase64(string $filename, string $mime): ?string
    {
        $path = resource_path('pdf-assets/'.$filename);
        if (! file_exists($path)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }

    /**
     * Lee el archivo de firma desde el disco "public" y lo retorna como data URL base64
     * para embeberlo en el PDF generado por DomPDF/Mpdf.
     */
    private function loadSignatureBase64(string $path): ?string
    {
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };

        if ($mime === null) {
            Log::warning('Extensión de firma no reconocida; se omite del PDF.', [
                'path' => $path,
                'extension' => $extension,
            ]);

            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    private function buildCollaboratorSnapshot(Collaborator $collaborator): array
    {
        return [
            'full_name' => $collaborator->full_name,
            'document_type' => $collaborator->documentType?->code ?? '',
            'document_type_name' => $collaborator->documentType?->name ?? 'Cédula de Ciudadanía',
            'document_number' => $collaborator->document_number,
            'gender' => $collaborator->gender ?? 'M',
            'type' => $collaborator->type,
            'is_company' => (bool) $collaborator->is_company,
            'company_name' => $collaborator->company_name,
        ];
    }
}
