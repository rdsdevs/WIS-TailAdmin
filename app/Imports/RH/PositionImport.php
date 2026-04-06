<?php

declare(strict_types=1);

namespace App\Imports\RH;

use App\Models\RH\Position;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PositionImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function __construct(
        private readonly string $institutionId,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $rowArray = $row->toArray();

            $activoRaw = strtoupper(trim((string) ($rowArray['activo'] ?? '')));
            $isActive = blank($activoRaw) || in_array($activoRaw, ['SI', 'SÍ', 'S', '1', 'TRUE'], true);

            try {
                DB::transaction(function () use ($rowArray, $isActive): void {
                    $name = trim($rowArray['nombre_cargo']);

                    // Buscar incluyendo soft-deleted para poder restaurar sin romper relaciones
                    $position = Position::withTrashed()
                        ->where('institution_id', $this->institutionId)
                        ->where('name', $name)
                        ->first();

                    if ($position) {
                        if ($position->trashed()) {
                            $position->restore();
                        }
                        $position->update(['is_active' => $isActive]);
                        $this->updated++;
                    } else {
                        Position::create([
                            'institution_id' => $this->institutionId,
                            'name'           => $name,
                            'is_active'      => $isActive,
                        ]);
                        $this->imported++;
                    }
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
            '*.nombre_cargo' => ['required', 'string', 'max:255'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.nombre_cargo.required' => 'La fila :attribute no tiene nombre de cargo.',
            '*.nombre_cargo.max' => 'El nombre del cargo en la fila :attribute no puede superar 255 caracteres.',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
