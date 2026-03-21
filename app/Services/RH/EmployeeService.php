<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Events\RH\EmployeeCreated;
use App\Exports\RH\EmpleadosExport;
use App\Http\Requests\RH\CreateEmployeeRequest;
use App\Http\Requests\RH\UpdateEmployeeRequest;
use App\Models\RH\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EmployeeService
{
    /**
     * Lista paginada de empleados con filtros opcionales.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAll(string $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::query()
            ->where('institution_id', $institutionId)
            ->with(['position', 'department']);

        if (! empty($filters['buscar'])) {
            $buscar = $filters['buscar'];
            $query->where(function ($q) use ($buscar): void {
                $q->where('first_name', 'like', "%{$buscar}%")
                    ->orWhere('last_name', 'like', "%{$buscar}%")
                    ->orWhere('document_number', 'like', "%{$buscar}%");
            });
        }

        if (isset($filters['activo'])) {
            $query->where('is_active', (bool) $filters['activo']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['position_id'])) {
            $query->where('position_id', $filters['position_id']);
        }

        return $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage);
    }

    /**
     * Crea un nuevo empleado y dispara el evento.
     */
    public function create(CreateEmployeeRequest $request): Employee
    {
        return DB::transaction(function () use ($request): Employee {
            $employee = Employee::create($request->validated());
            event(new EmployeeCreated($employee));

            return $employee;
        });
    }

    /**
     * Actualiza los datos de un empleado.
     */
    public function update(Employee $employee, UpdateEmployeeRequest $request): Employee
    {
        DB::transaction(function () use ($employee, $request): void {
            $employee->update($request->validated());
        });

        return $employee->fresh(['position', 'department']);
    }

    /**
     * Elimina lógicamente un empleado.
     */
    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    /**
     * Retorna el objeto de exportación Excel para la institución.
     */
    public function export(string $institutionId): EmpleadosExport
    {
        return new EmpleadosExport($institutionId);
    }
}
