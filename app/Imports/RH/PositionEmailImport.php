<?php

declare(strict_types=1);

namespace App\Imports\RH;

use App\Models\RH\Position;
use App\Models\RH\PositionEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PositionEmailImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

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

            $email = strtolower(trim($rowArray['correo_electronico'] ?? ''));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->skipped++;

                continue;
            }

            try {
                DB::transaction(function () use ($positionId, $email): void {
                    $existing = PositionEmail::withTrashed()
                        ->where('position_id', $positionId)
                        ->where('email', $email)
                        ->first();

                    if ($existing !== null) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }

                        $this->updated++;
                    } else {
                        PositionEmail::create([
                            'position_id' => $positionId,
                            'email' => $email,
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
            '*.cargo' => ['required', 'string'],
            '*.correo_electronico' => ['required', 'email'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.cargo.required' => 'La fila :attribute no tiene nombre de cargo.',
            '*.correo_electronico.required' => 'La fila :attribute no tiene correo electrónico.',
            '*.correo_electronico.email' => 'El correo electrónico en la fila :attribute no tiene un formato válido.',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
