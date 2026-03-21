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
            ->with('department')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Crea un nuevo cargo.
     */
    public function create(CreatePositionRequest $request): Position
    {
        return DB::transaction(function () use ($request): Position {
            return Position::create($request->validated());
        });
    }

    /**
     * Actualiza un cargo existente.
     */
    public function update(Position $position, UpdatePositionRequest $request): Position
    {
        $position->update($request->validated());

        return $position->fresh();
    }

    /**
     * Elimina lógicamente un cargo.
     */
    public function delete(Position $position): void
    {
        $position->delete();
    }
}
