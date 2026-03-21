<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Http\Requests\RH\CreateDepartmentRequest;
use App\Http\Requests\RH\UpdateDepartmentRequest;
use App\Models\RH\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class DepartmentService
{
    /**
     * Lista paginada de dependencias por institución.
     */
    public function getAll(string $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        return Department::query()
            ->where('institution_id', $institutionId)
            ->with('positions')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Crea una nueva dependencia.
     */
    public function create(CreateDepartmentRequest $request): Department
    {
        return DB::transaction(function () use ($request): Department {
            return Department::create($request->validated());
        });
    }

    /**
     * Actualiza una dependencia existente.
     */
    public function update(Department $department, UpdateDepartmentRequest $request): Department
    {
        $department->update($request->validated());

        return $department->fresh();
    }

    /**
     * Elimina lógicamente una dependencia.
     */
    public function delete(Department $department): void
    {
        $department->delete();
    }
}
