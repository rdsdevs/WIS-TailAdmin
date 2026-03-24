<?php

declare(strict_types=1);

namespace App\Imports\RH;

use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class ContractImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithTitle
{
    use SkipsFailures;

    // Sin WithChunkReading — necesario para que WithTitle filtre correctamente la hoja

    private int $imported = 0;
    private int $updated  = 0;
    private int $skipped  = 0;

    private array $rowErrors            = [];
    private array $categoryCounts       = ['historico' => 0, 'intermedio' => 0, 'reciente' => 0];
    private array $createdContractCodes = [];

    private array $collaboratorMap  = [];
    private array $contractTypeMap  = [];

    private const VALID_STATUSES = ['Vigente', 'Terminado', 'Liquidado', 'Vencido', 'Borrador'];

    public function __construct(
        private readonly string $institutionId,
        private readonly bool $overwrite = false,
    ) {
        $this->collaboratorMap = Collaborator::where('institution_id', $institutionId)
            ->whereNull('deleted_at')
            ->pluck('id', 'document_number')
            ->toArray();

        // Mapa case-insensitive: strtolower(nombre) → uuid
        $this->contractTypeMap = ContractType::where('institution_id', $institutionId)
            ->whereNull('deleted_at')
            ->get()
            ->mapWithKeys(fn (ContractType $ct): array => [strtolower(trim($ct->name)) => $ct->id])
            ->toArray();
    }

    public function title(): string
    {
        return 'Contratos';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->processRow($row->toArray(), (int) $index);
        }
    }

    private function processRow(array $row, int $rowNumber): void
    {
        // Saltar silenciosamente filas vacías (del sheet Catálogos u otras hojas)
        $docColaborador = trim((string) ($row['documento_colaborador'] ?? ''));
        $fechaInicio    = trim((string) ($row['fecha_inicio_ddmmyyyy'] ?? ''));
        $tipoContrato   = trim((string) ($row['tipo_contrato'] ?? ''));
        $objeto         = trim((string) ($row['objeto'] ?? ''));

        if ($docColaborador === '' && $fechaInicio === '' && $tipoContrato === '' && $objeto === '') {
            return;
        }

        $errors = [];

        // ── Parseo de fechas ─────────────────────────────────────────────────
        $startDate = $this->parseDate($row['fecha_inicio_ddmmyyyy'] ?? null);
        $endDate   = $this->parseDate($row['fecha_fin_ddmmyyyy'] ?? null);

        if ($startDate === null) {
            $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'fecha_inicio', 'mensaje' => 'La fecha de inicio es obligatoria y debe tener formato dd/mm/yyyy o yyyy-mm-dd.'];
        }

        // ── Categoría por año ────────────────────────────────────────────────
        $category = null;
        $year     = null;

        if ($startDate !== null) {
            $year = (int) $startDate->format('Y');

            if ($year < 2019) {
                $category = 'historico';
            } elseif ($year <= 2024) {
                $category = 'intermedio';
            } else {
                $category = 'reciente';
            }
        }

        // ── Validaciones condicionales (>= 2019) ─────────────────────────────
        $numContrato    = trim((string) ($row['num_contrato_solo_2019'] ?? ''));
        $codigoContrato = trim((string) ($row['codigo_contrato_solo_2019'] ?? ''));

        if ($year !== null && $year >= 2019) {
            if ($numContrato === '') {
                $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'num_contrato', 'mensaje' => 'El número de contrato es obligatorio para contratos desde 2019. Formato: 3 dígitos (ej: 001).'];
            } elseif (! preg_match('/^\d{3}$/', $numContrato)) {
                $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'num_contrato', 'mensaje' => 'El número de contrato debe tener exactamente 3 dígitos (ej: 001).'];
            }

            if ($codigoContrato === '') {
                $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'codigo_contrato', 'mensaje' => 'El código de contrato es obligatorio para contratos desde 2019. Formato: NNN-AAAA (ej: 001-2022).'];
            } elseif (! preg_match('/^\d{3}-\d{4}$/', $codigoContrato)) {
                $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'codigo_contrato', 'mensaje' => 'El código de contrato debe tener el formato NNN-AAAA (ej: 001-2022).'];
            } elseif ($startDate !== null) {
                // Verificar que el año en el código coincide con el año de inicio
                $yearInCode = (int) substr($codigoContrato, -4);
                if ($yearInCode !== $year) {
                    $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'codigo_contrato', 'mensaje' => "El año en el código de contrato ({$yearInCode}) no coincide con el año de fecha_inicio ({$year})."];
                }
            }
        }

        // ── Resolver collaborator_id ─────────────────────────────────────────
        $documentoColaborador = trim((string) ($row['documento_colaborador'] ?? ''));
        $collaboratorId       = $this->collaboratorMap[$documentoColaborador] ?? null;

        if ($collaboratorId === null) {
            $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'documento_colaborador', 'mensaje' => "No se encontró ningún colaborador activo con el documento '{$documentoColaborador}'."];
        }

        // ── Resolver contract_type_id ────────────────────────────────────────
        $tipoContrato   = trim((string) ($row['tipo_contrato'] ?? ''));
        $contractTypeId = $this->contractTypeMap[strtolower($tipoContrato)] ?? null;

        if ($contractTypeId === null) {
            $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'tipo_contrato', 'mensaje' => "El tipo de contrato '{$tipoContrato}' no existe en el catálogo de la institución."];
        }

        // ── Validar honorarios y salario ─────────────────────────────────────
        $honorarios = $row['honorarios'] ?? null;
        $salario    = $row['salario'] ?? null;

        if ($honorarios !== null && $honorarios !== '' && (! is_numeric($honorarios) || (float) $honorarios < 0)) {
            $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'honorarios', 'mensaje' => 'Los honorarios deben ser un número mayor o igual a 0.'];
        }

        if ($salario !== null && $salario !== '' && (! is_numeric($salario) || (float) $salario < 0)) {
            $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'salario', 'mensaje' => 'El salario debe ser un número mayor o igual a 0.'];
        }

        // ── Validar estado ───────────────────────────────────────────────────
        $estado = trim((string) ($row['estado'] ?? ''));

        if (! in_array($estado, self::VALID_STATUSES, true)) {
            $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'estado', 'mensaje' => 'El estado debe ser uno de: Vigente, Terminado, Liquidado, Vencido, Borrador.'];
        }

        // ── Validar objeto ───────────────────────────────────────────────────
        $objeto = trim((string) ($row['objeto'] ?? ''));

        if ($objeto === '') {
            $errors[] = ['fila' => $rowNumber + 2, 'campo' => 'objeto', 'mensaje' => 'El objeto del contrato es obligatorio.'];
        }

        // ── Si hay errores, skip ─────────────────────────────────────────────
        if (! empty($errors)) {
            foreach ($errors as $error) {
                $this->rowErrors[] = $error;
            }
            $this->skipped++;

            return;
        }

        // ── Persistir ────────────────────────────────────────────────────────
        $data = [
            'institution_id'  => $this->institutionId,
            'collaborator_id' => $collaboratorId,
            'contract_type_id' => $contractTypeId,
            'start_date'      => $startDate->format('Y-m-d'),
            'end_date'        => $endDate?->format('Y-m-d'),
            'object'          => $objeto,
            'obligations'     => trim((string) ($row['obligaciones'] ?? '')) ?: null,
            'fees'            => ($honorarios !== null && $honorarios !== '') ? (float) $honorarios : null,
            'salary'          => ($salario !== null && $salario !== '') ? (float) $salario : null,
            'position_email'  => trim((string) ($row['correo_cargo'] ?? '')) ?: null,
            'status'          => $estado,
            'contract_number' => ($year !== null && $year >= 2019) ? $numContrato : null,
            'contract_code'   => ($year !== null && $year >= 2019) ? $codigoContrato : null,
        ];

        try {
            DB::transaction(function () use ($data, $category, $codigoContrato, $year): void {
                if ($year !== null && $year >= 2019 && $codigoContrato !== '') {
                    // Clave única para contratos con código
                    $uniqueKey = [
                        'institution_id' => $this->institutionId,
                        'contract_code'  => $codigoContrato,
                    ];

                    if ($this->overwrite) {
                        $contract = Contract::updateOrCreate($uniqueKey, $data);
                        $wasCreated = $contract->wasRecentlyCreated;
                    } else {
                        $exists = Contract::where($uniqueKey)->exists();
                        if ($exists) {
                            $this->skipped++;

                            return;
                        }
                        $contract = Contract::create($data);
                        $wasCreated = true;
                    }
                } else {
                    // Históricos: clave única por institución + colaborador + fecha_inicio
                    $uniqueKey = [
                        'institution_id'  => $this->institutionId,
                        'collaborator_id' => $data['collaborator_id'],
                        'start_date'      => $data['start_date'],
                    ];

                    if ($this->overwrite) {
                        $contract = Contract::updateOrCreate($uniqueKey, $data);
                        $wasCreated = $contract->wasRecentlyCreated;
                    } else {
                        $exists = Contract::where($uniqueKey)->exists();
                        if ($exists) {
                            $this->skipped++;

                            return;
                        }
                        $contract = Contract::create($data);
                        $wasCreated = true;
                    }
                }

                if ($wasCreated) {
                    $this->imported++;
                } else {
                    $this->updated++;
                }

                $this->categoryCounts[$category]++;

                // Guardar mapa código → contract_id para CommittedValueImport
                if ($codigoContrato !== '') {
                    $this->createdContractCodes[$codigoContrato] = $contract->id;
                }
            });
        } catch (\Exception $e) {
            Log::error('[ContractImport] Error al guardar contrato.', [
                'fila'  => $rowNumber + 2,
                'error' => $e->getMessage(),
            ]);
            $this->rowErrors[] = [
                'fila'    => $rowNumber + 2,
                'campo'   => 'general',
                'mensaje' => 'Ocurrió un error inesperado al guardar el registro. Por favor intente de nuevo.',
            ];
            $this->skipped++;
        }
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Instancia Carbon o DateTime (maatwebsite/excel puede pasar directamente)
        if ($value instanceof Carbon) {
            $year = (int) $value->format('Y');

            return ($year >= 1950 && $year <= 2100) ? $value->copy()->startOfDay() : null;
        }

        if ($value instanceof \DateTime) {
            $carbon = Carbon::instance($value);
            $year   = (int) $carbon->format('Y');

            return ($year >= 1950 && $year <= 2100) ? $carbon->startOfDay() : null;
        }

        // Número serial de Excel — los seriales representan días en UTC, se pasa timezone explícito
        if (is_numeric($value) && (float) $value > 1 && (float) $value < 100000) {
            try {
                $date = Carbon::createFromTimestamp(((float) $value - 25569) * 86400, 'UTC');
                $year = (int) $date->format('Y');
                if ($year < 1950 || $year > 2100) {
                    return null;
                }

                return $date;
            } catch (\Exception) {
                return null;
            }
        }

        $raw = trim((string) $value);

        foreach (['d/m/Y', 'Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'd/m/y', 'd-m-y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $raw);
                if ($date === false) {
                    continue;
                }
                $year = (int) $date->format('Y');
                if ($year < 1950 || $year > 2100) {
                    return null;
                }

                return $date->startOfDay();
            } catch (\Exception) {
                continue;
            }
        }

        return null;
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

    public function getRowErrors(): array
    {
        return $this->rowErrors;
    }

    public function getCategoryCounts(): array
    {
        return $this->categoryCounts;
    }

    public function getCreatedContractCodes(): array
    {
        return $this->createdContractCodes;
    }
}
