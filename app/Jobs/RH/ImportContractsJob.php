<?php

declare(strict_types=1);

namespace App\Jobs\RH;

use App\Imports\RH\CommittedValueImport;
use App\Imports\RH\ContractImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportContractsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        private readonly string $filePath,
        private readonly string $institutionId,
        private readonly bool $overwrite,
        private readonly string $userId,
    ) {}

    public function handle(): void
    {
        $absolutePath = Storage::path('private/'.$this->filePath);

        // ── Importar hoja "Contratos" ─────────────────────────────────────────
        $contractImport = new ContractImport(
            institutionId: $this->institutionId,
            overwrite: $this->overwrite,
        );

        Excel::import($contractImport, $absolutePath, null, \Maatwebsite\Excel\Excel::XLSX);

        $contractCodeMap = $contractImport->getCreatedContractCodes();

        // ── Importar hoja "Valores_comprometidos" ─────────────────────────────
        $committedImport = new CommittedValueImport(
            institutionId: $this->institutionId,
            contractCodeMap: $contractCodeMap,
        );

        Excel::import($committedImport, $absolutePath, null, \Maatwebsite\Excel\Excel::XLSX);

        // ── Guardar resultado en caché ────────────────────────────────────────
        Cache::put(
            "import_contracts_result_{$this->userId}",
            [
                'imported'                  => $contractImport->getImported(),
                'updated'                   => $contractImport->getUpdated(),
                'skipped'                   => $contractImport->getSkipped(),
                'row_errors'                => $contractImport->getRowErrors(),
                'category_counts'           => $contractImport->getCategoryCounts(),
                'committed_values_imported' => $committedImport->getImported(),
                'committed_values_skipped'  => $committedImport->getSkipped(),
                'committed_values_errors'   => $committedImport->getRowErrors(),
                'completado_at'             => now()->format('d/m/Y H:i'),
            ],
            now()->addHours(2)
        );

        // ── Limpiar archivo temporal ──────────────────────────────────────────
        Storage::delete('private/'.$this->filePath);
    }

    public function failed(\Throwable $e): void
    {
        Cache::put(
            "import_contracts_result_{$this->userId}",
            [
                'error'         => 'Error interno al procesar el archivo: '.$e->getMessage(),
                'completado_at' => now()->format('d/m/Y H:i'),
            ],
            now()->addHours(2)
        );
    }
}
