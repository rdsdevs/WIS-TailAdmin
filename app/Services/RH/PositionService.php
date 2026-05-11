<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Http\Requests\RH\CreatePositionRequest;
use App\Http\Requests\RH\UpdatePositionRequest;
use App\Models\RH\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PositionService
{
    /**
     * Lista paginada de cargos por institución.
     */
    public function getAll(string $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        return Position::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Crea un nuevo cargo a partir de un FormRequest.
     */
    public function create(CreatePositionRequest $request): Position
    {
        $validated = $request->validated();

        return $this->createFromArray(
            data: collect($validated)->except(['emails', 'functions'])->all(),
            emails: $validated['emails'] ?? [],
            functions: $validated['functions'] ?? [],
        );
    }

    /**
     * Actualiza un cargo existente a partir de un FormRequest.
     */
    public function update(Position $position, UpdatePositionRequest $request): Position
    {
        $validated = $request->validated();

        return $this->updateFromArray(
            position: $position,
            data: collect($validated)->except(['emails', 'functions'])->all(),
            emails: $validated['emails'] ?? null,
            functions: $validated['functions'] ?? null,
        );
    }

    /**
     * Crea un cargo a partir de arrays planos (uso desde Livewire u otros contextos).
     *
     * @param  array<string,mixed>  $data
     * @param  array<int,string>  $emails
     * @param  array<int,string>  $functions
     */
    public function createFromArray(array $data, array $emails = [], array $functions = []): Position
    {
        return DB::transaction(function () use ($data, $emails, $functions): Position {
            $position = Position::create($data);

            foreach ($emails as $email) {
                $position->emails()->create(['email' => $email]);
            }

            foreach ($functions as $function) {
                $position->functions()->create(['description' => $function]);
            }

            return $position->fresh(['emails', 'functions']);
        });
    }

    /**
     * Actualiza un cargo a partir de arrays planos. Si emails o functions son null,
     * se conservan las colecciones existentes; si son arrays (aunque vacíos), se reemplazan.
     *
     * @param  array<string,mixed>  $data
     * @param  array<int,string>|null  $emails
     * @param  array<int,string>|null  $functions
     */
    public function updateFromArray(
        Position $position,
        array $data,
        ?array $emails = null,
        ?array $functions = null,
    ): Position {
        DB::transaction(function () use ($position, $data, $emails, $functions): void {
            $position->update($data);

            if ($emails !== null) {
                $position->emails()->delete();
                foreach ($emails as $email) {
                    $position->emails()->create(['email' => $email]);
                }
            }

            if ($functions !== null) {
                $position->functions()->delete();
                foreach ($functions as $function) {
                    $position->functions()->create(['description' => $function]);
                }
            }
        });

        return $position->fresh(['emails', 'functions']);
    }

    /**
     * Elimina lógicamente un cargo.
     */
    public function delete(Position $position): void
    {
        $position->delete();
    }
}
