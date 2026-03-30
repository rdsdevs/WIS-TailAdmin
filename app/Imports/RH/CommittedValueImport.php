<?php

declare(strict_types=1);

namespace App\Imports\RH;

use App\Models\RH\CommittedValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class CommittedValueImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithTitle
{
    use SkipsFailures;

    private int $imported = 0;

    private int $skipped = 0;

    private array $rowErrors = [];

    public function __construct(
        private readonly string $institutionId,
        private readonly array $contractCodeMap,
    ) {}

    public function title(): string
    {
        return 'Valores_comprometidos';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->processRow($row->toArray(), (int) $index);
        }
    }

    private function processRow(array $row, int $rowNumber): void
    {
        $codigoContrato = trim((string) ($row['codigo_contrato'] ?? ''));

        // Skip filas sin código de contrato
        if ($codigoContrato === '') {
            return;
        }

        $contractId = $this->contractCodeMap[$codigoContrato] ?? null;

        if ($contractId === null) {
            $this->rowErrors[] = [
                'fila' => $rowNumber + 2,
                'campo' => 'codigo_contrato',
                'mensaje' => "El código de contrato '{$codigoContrato}' no fue encontrado en la hoja Contratos ni existe previamente en el sistema.",
            ];
            $this->skipped++;

            return;
        }

        $cuentaContable = trim((string) ($row['cuenta_contable'] ?? ''));
        $centroDeCosto = trim((string) ($row['centro_de_costo'] ?? ''));
        $valor = $row['valor'] ?? null;

        if (! is_numeric($valor) || (float) $valor < 0) {
            $this->rowErrors[] = [
                'fila' => $rowNumber + 2,
                'campo' => 'valor',
                'mensaje' => 'El valor comprometido debe ser un número mayor o igual a 0.',
            ];
            $this->skipped++;

            return;
        }

        if ($cuentaContable === '') {
            $this->rowErrors[] = [
                'fila' => $rowNumber + 2,
                'campo' => 'cuenta_contable',
                'mensaje' => 'La cuenta contable es obligatoria.',
            ];
            $this->skipped++;

            return;
        }

        if ($centroDeCosto === '') {
            $this->rowErrors[] = [
                'fila' => $rowNumber + 2,
                'campo' => 'centro_de_costo',
                'mensaje' => 'El centro de costo es obligatorio.',
            ];
            $this->skipped++;

            return;
        }

        try {
            DB::transaction(function () use ($contractId, $cuentaContable, $centroDeCosto, $valor): void {
                CommittedValue::create([
                    'institution_id' => $this->institutionId,
                    'contract_id' => $contractId,
                    'accounting_account' => $cuentaContable,
                    'cost_center' => $centroDeCosto,
                    'amount' => (float) $valor,
                ]);

                $this->imported++;
            });
        } catch (\Exception $e) {
            Log::error('[CommittedValueImport] Error al guardar valor comprometido.', [
                'fila' => $rowNumber + 2,
                'codigo' => $codigoContrato,
                'error' => $e->getMessage(),
            ]);
            $this->rowErrors[] = [
                'fila' => $rowNumber + 2,
                'campo' => 'general',
                'mensaje' => 'Ocurrió un error inesperado al guardar el registro. Por favor intente de nuevo.',
            ];
            $this->skipped++;
        }
    }

    public function getImported(): int
    {
        return $this->imported;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getRowErrors(): array
    {
        return $this->rowErrors;
    }
}
