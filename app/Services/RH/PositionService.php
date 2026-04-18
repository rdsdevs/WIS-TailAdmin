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
     * Crea un nuevo cargo.
     */
    public function create(CreatePositionRequest $request): Position
    {
        return DB::transaction(function () use ($request): Position {
            $validated = $request->validated();
            $emails = $validated['emails'] ?? [];
            $functions = $validated['functions'] ?? [];
            $responsibilities = $validated['responsibilities'] ?? [];
            $authorities = $validated['authorities'] ?? [];

            $data = collect($validated)->except(['emails', 'functions', 'responsibilities', 'authorities'])->all();
            $position = Position::create($data);

            if (! empty($emails)) {
                foreach ($emails as $email) {
                    $position->emails()->create(['email' => $email]);
                }
            }

            if (! empty($functions)) {
                foreach ($functions as $function) {
                    $position->functions()->create(['description' => $function]);
                }
            }

            if (! empty($responsibilities)) {
                foreach ($responsibilities as $item) {
                    $position->responsibilities()->create(['description' => $item]);
                }
            }

            if (! empty($authorities)) {
                foreach ($authorities as $item) {
                    $position->authorities()->create(['description' => $item]);
                }
            }

            return $position;
        });
    }

    /**
     * Actualiza un cargo existente.
     */
    public function update(Position $position, UpdatePositionRequest $request): Position
    {
        DB::transaction(function () use ($position, $request): void {
            $validated = $request->validated();
            $emails = $validated['emails'] ?? null;
            $functions = $validated['functions'] ?? null;
            $responsibilities = array_key_exists('responsibilities', $validated) ? $validated['responsibilities'] : null;
            $authorities = array_key_exists('authorities', $validated) ? $validated['authorities'] : null;

            $data = collect($validated)->except(['emails', 'functions', 'responsibilities', 'authorities'])->all();
            $position->update($data);

            if ($emails !== null) {
                $position->emails()->get()->each->delete();
                foreach ($emails as $email) {
                    $position->emails()->create(['email' => $email]);
                }
            }

            if ($functions !== null) {
                $position->functions()->get()->each->delete();
                foreach ($functions as $function) {
                    $position->functions()->create(['description' => $function]);
                }
            }

            if ($responsibilities !== null) {
                $position->responsibilities()->get()->each->delete();
                foreach ($responsibilities as $item) {
                    $position->responsibilities()->create(['description' => $item]);
                }
            }

            if ($authorities !== null) {
                $position->authorities()->get()->each->delete();
                foreach ($authorities as $item) {
                    $position->authorities()->create(['description' => $item]);
                }
            }
        });

        return $position->fresh(['emails', 'functions', 'responsibilities', 'authorities']);
    }

    /**
     * Elimina lógicamente un cargo.
     */
    public function delete(Position $position): void
    {
        $position->delete();
    }
}
