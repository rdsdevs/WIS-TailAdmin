<?php

declare(strict_types=1);

namespace App\Imports\RH;

use App\Models\RH\Position;
use App\Models\RH\PositionResponsibility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PositionResponsibilityImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    /** Las responsabilidades no se actualizan individualmente — siempre es 0 */
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

            $positionKey = strtolower(trim($rowArray['nombre_cargo'] ?? ''));
            $positionId = $this->positionMap[$positionKey] ?? null;

            if ($positionId === null) {
                $this->skipped++;

                continue;
            }

            $description = trim($rowArray['responsabilidad'] ?? '');

            if ($description === '') {
                $this->skipped++;

                continue;
            }

            try {
                DB::transaction(function () use ($positionId, $description): void {
                    $item = PositionResponsibility::withTrashed()
                        ->where('position_id', $positionId)
                        ->where('description', $description)
                        ->first();

                    if ($item) {
                        if ($item->trashed()) {
                            $item->restore();
                        }
                    } else {
                        PositionResponsibility::create([
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
            '*.nombre_cargo'    => ['required', 'string'],
            '*.responsabilidad' => ['required', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.nombre_cargo.required'    => 'La fila :attribute no tiene nombre de cargo.',
            '*.responsabilidad.required' => 'La fila :attribute no tiene descripción de responsabilidad.',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
