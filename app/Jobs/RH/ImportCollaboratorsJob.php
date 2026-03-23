<?php

declare(strict_types=1);

namespace App\Jobs\RH;

use App\Imports\RH\CollaboratorImport;
use App\Models\User;
use App\Notifications\RH\CollaboratorImportCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportCollaboratorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(
        private readonly string $filePath,
        private readonly string $institutionId,
        private readonly string $type,
        private readonly bool $overwrite,
        private readonly string $userId,
    ) {}

    public function handle(): void
    {
        $import = new CollaboratorImport(
            institutionId: $this->institutionId,
            type: $this->type,
            overwrite: $this->overwrite,
        );

        Excel::import($import, storage_path('app/private/'.$this->filePath));

        $failures = $import->failures();
        $imported = $import->getImported();
        $updated  = $import->getUpdated();
        $skipped  = $import->getSkipped();

        // Guardar resultado en caché para que el usuario lo consulte
        $cacheKey = "import_result_{$this->userId}";
        cache()->put($cacheKey, [
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'failures' => collect($failures)->map(fn ($f) => [
                'fila'    => $f->row(),
                'campo'   => implode(', ', (array) $f->attribute()),
                'errores' => $f->errors(),
            ])->toArray(),
            'tipo'          => $this->type,
            'completado_at' => now()->format('d/m/Y H:i'),
        ], now()->addHours(2));

        // Notificar al usuario
        User::find($this->userId)?->notify(new CollaboratorImportCompletedNotification([
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'failures' => collect($failures)->map(fn ($f) => [
                'fila'    => $f->row(),
                'campo'   => implode(', ', (array) $f->attribute()),
                'errores' => $f->errors(),
            ])->toArray(),
            'tipo'          => $this->type,
            'completado_at' => now()->format('d/m/Y H:i'),
        ]));

        // Limpiar archivo temporal
        Storage::delete('private/'.$this->filePath);
    }

    public function failed(\Throwable $e): void
    {
        $errorData = [
            'error'         => 'La importación falló: '.$e->getMessage(),
            'completado_at' => now()->format('d/m/Y H:i'),
        ];

        cache()->put("import_result_{$this->userId}", $errorData, now()->addHours(2));

        User::find($this->userId)?->notify(new CollaboratorImportCompletedNotification($errorData));
    }
}
