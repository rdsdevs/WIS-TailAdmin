<?php

declare(strict_types=1);

namespace App\Imports\RH;

use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\DocumentType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CollaboratorImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading
{
    use SkipsFailures;

    private int $imported = 0;
    private int $updated  = 0;
    private int $skipped  = 0;
    private array $documentTypeMap = [];
    private array $statusMap = [];

    public function __construct(
        private readonly string $institutionId,
        private readonly string $type,
        private readonly bool $overwrite,
    ) {
        $this->documentTypeMap = DocumentType::pluck('id', 'code')->toArray();
        $this->statusMap = CollaboratorStatus::pluck('id', 'name')->toArray();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->processRow($row->toArray());
        }
    }

    private function processRow(array $row): void
    {
        $isCompany = $this->parseBoolean($row['es_empresa'] ?? '');

        if ($isCompany) {
            $documentTypeId = $this->documentTypeMap['NIT'] ?? null;
            $documentNumber = $this->normalizeString($row['nit'] ?? '');
        } else {
            $tipoCode = strtoupper(trim($row['tipo_documento'] ?? ''));
            $documentTypeId = $this->documentTypeMap[$tipoCode] ?? null;
            $documentNumber = $this->normalizeString($row['numero_documento'] ?? '');
        }

        $estadoNombre = ucfirst(strtolower(trim($row['estado'] ?? '')));
        $statusId = $this->statusMap[$estadoNombre] ?? null;

        $data = [
            'institution_id'       => $this->institutionId,
            'type'                 => $this->type,
            'is_company'           => $isCompany,
            'document_type_id'     => $documentTypeId,
            'document_number'      => $documentNumber ?: null,
            'document_issued_at'   => $this->parseFecha($row['fecha_expedicion'] ?? ''),
            'first_name'           => $isCompany ? null : $this->normalizeString($row['primer_nombre'] ?? ''),
            'second_name'          => $isCompany ? null : ($this->normalizeString($row['segundo_nombre'] ?? '') ?: null),
            'first_surname'        => $isCompany ? null : $this->normalizeString($row['primer_apellido'] ?? ''),
            'second_surname'       => $isCompany ? null : ($this->normalizeString($row['segundo_apellido'] ?? '') ?: null),
            'birth_date'           => $this->parseFecha($row['fecha_nacimiento'] ?? ''),
            'gender'               => $isCompany ? null : $this->parseGenero($row['genero'] ?? ''),
            'company_name'         => $isCompany ? $this->normalizeString($row['razon_social'] ?? '') : null,
            'legal_representative' => $isCompany ? ($this->normalizeString($row['representante_legal'] ?? '') ?: null) : null,
            'email'                => $this->normalizeString($row['correo'] ?? '') ?: null,
            'phone'                => $this->normalizeString($row['telefono'] ?? '') ?: null,
            'address'              => $this->normalizeString($row['direccion'] ?? '') ?: null,
            'status_id'            => $statusId,
        ];

        try {
            DB::transaction(function () use ($data): void {
                $uniqueKey = [
                    'institution_id'   => $this->institutionId,
                    'document_number'  => $data['document_number'],
                    'document_type_id' => $data['document_type_id'],
                ];

                if ($this->overwrite && $data['document_number']) {
                    Collaborator::updateOrCreate($uniqueKey, $data);
                    $this->updated++;
                } else {
                    $exists = $data['document_number']
                        ? Collaborator::where($uniqueKey)->exists()
                        : false;

                    if ($exists) {
                        $this->skipped++;
                    } else {
                        Collaborator::create($data);
                        $this->imported++;
                    }
                }
            });
        } catch (\Exception) {
            // Registrado via SkipsFailures
        }
    }

    public function rules(): array
    {
        return [
            'estado' => 'required|string',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'estado.required' => 'La fila :attribute no tiene estado. Use: Activo, Inactivo, Extrabajador, Pensionado o Fallecido.',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getImported(): int
    {
        return $this->imported;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    private function normalizeString(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function parseBoolean(mixed $value): bool
    {
        return in_array(strtoupper(trim((string) $value)), ['SI', 'SÍ', 'S', '1', 'TRUE', 'VERDADERO'], true);
    }

    private function parseFecha(mixed $raw): ?string
    {
        if (blank($raw)) {
            return null;
        }
        $raw = trim((string) $raw);
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $raw)->format('Y-m-d');
            } catch (\Exception) {
            }
        }

        return null;
    }

    private function parseGenero(mixed $value): ?string
    {
        $map = [
            'MASCULINO' => 'M',
            'FEMENINO'  => 'F',
            'OTRO'      => 'O',
            'M'         => 'M',
            'F'         => 'F',
            'O'         => 'O',
        ];
        $upper = strtoupper(trim((string) $value));

        return $map[$upper] ?? null;
    }
}
