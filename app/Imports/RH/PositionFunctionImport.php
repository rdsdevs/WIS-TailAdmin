<?php

declare(strict_types=1);

namespace App\Imports\RH;

use App\Models\RH\Position;
use App\Models\RH\PositionFunction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PositionFunctionImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    /** Las funciones no se actualizan individualmente — siempre es 0 */
    public int $updated = 0;

    public int $skipped = 0;

    private array $positionMap = [];

    public function __construct(
        private readonly string $institutionId,
    ) {
        $this->positionMap = Position::query()
            ->where('institution_id', $this->institutionId)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Position $pos): array => [
                strtolower(trim($pos->name)) => (string) $pos->id,
            ])
            ->toArray();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $rowArray = $row->toArray();

            $positionKey = strtolower(trim($rowArray['cargo'] ?? ''));
            $positionId = $this->positionMap[$positionKey] ?? null;

            if ($positionId === null) {
                $this->skipped++;

                continue;
            }

            $description = trim($rowArray['descripcion_funcion'] ?? '');

            if ($description === '') {
                $this->skipped++;

                continue;
            }

            try {
                DB::transaction(function () use ($positionId, $description): void {
                    // firstOrCreate evita duplicados si se reimporta el mismo archivo
                    $fn = PositionFunction::withTrashed()
                        ->where('position_id', $positionId)
                        ->where('description', $description)
                        ->first();

                    if ($fn) {
                        if ($fn->trashed()) {
                            $fn->restore();
                        }
                    } else {
                        PositionFunction::create([
                            'position_id' => $positionId,
                            'description' => $description,
                        ]);
                    }

                    $this->imported++;
                });
            } catch (\Exception) {
                // Registrado vía SkipsFailures
                $this->skipped++;
            }
        }
    }

    public function rules(): array
    {
        return [
            '*.cargo' => ['required', 'string'],
            '*.descripcion_funcion' => ['required', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.cargo.required' => 'La fila :attribute no tiene nombre de cargo.',
            '*.descripcion_funcion.required' => 'La fila :attribute no tiene descripción de función.',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
