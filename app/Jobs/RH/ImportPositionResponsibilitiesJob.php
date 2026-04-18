<?php

declare(strict_types=1);

namespace App\Jobs\RH;

use App\Imports\RH\PositionResponsibilityImport;
use App\Models\User;
use App\Notifications\RH\PositionImportCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportPositionResponsibilitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        private readonly string $filePath,
        private readonly string $institutionId,
        private readonly string $userId,
    ) {}

    public function handle(): void
    {
        $import = new PositionResponsibilityImport(institutionId: $this->institutionId);

        Excel::import($import, Storage::disk('local')->path($this->filePath));

        $failures = $import->failures();
        $imported = $import->imported;
        $updated = $import->updated;
        $skipped = $import->skipped;
        $total = $imported + $updated + $skipped;

        $result = [
            'imported'      => $imported,
            'updated'       => $updated,
            'skipped'       => $skipped,
            'failures'      => collect($failures)->map(fn ($f) => [
                'fila'    => $f->row(),
                'campo'   => implode(', ', (array) $f->attribute()),
                'errores' => $f->errors(),
            ])->toArray(),
            'tipo'          => 'responsabilidades',
            'total'         => $total,
            'completado_at' => now()->format('d/m/Y H:i'),
        ];

        cache()->put("import_positions_result_{$this->userId}", $result, now()->addHours(2));

        User::find($this->userId)?->notify(new PositionImportCompletedNotification($result));

        Storage::delete($this->filePath);
    }

    public function failed(\Throwable $e): void
    {
        $errorData = [
            'error'         => 'La importación falló: '.$e->getMessage(),
            'tipo'          => 'responsabilidades',
            'completado_at' => now()->format('d/m/Y H:i'),
        ];

        cache()->put("import_positions_result_{$this->userId}", $errorData, now()->addHours(2));

        User::find($this->userId)?->notify(new PositionImportCompletedNotification($errorData));

        Storage::delete($this->filePath);
    }
}
