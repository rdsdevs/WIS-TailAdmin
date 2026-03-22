<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Exports\RH\ColaboradoresExport;
use App\Http\Requests\RH\CreateCollaboratorRequest;
use App\Http\Requests\RH\UpdateCollaboratorRequest;
use App\Models\RH\Collaborator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CollaboratorService
{
    /**
     * Lista paginada de colaboradores con filtros opcionales.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAll(string $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Collaborator::query()
            ->where('institution_id', $institutionId)
            ->with(['documentType', 'status', 'activeContract.position']);

        if (! empty($filters['tipo'])) {
            match ($filters['tipo']) {
                'empleados' => $query->employees(),
                'contratistas' => $query->contractors(),
                default => null,
            };
        }

        if (! empty($filters['buscar'])) {
            $buscar = $filters['buscar'];
            $query->where(function ($q) use ($buscar): void {
                $q->where('first_name', 'like', "%{$buscar}%")
                    ->orWhere('first_surname', 'like', "%{$buscar}%")
                    ->orWhere('second_surname', 'like', "%{$buscar}%")
                    ->orWhere('document_number', 'like', "%{$buscar}%")
                    ->orWhere('company_name', 'like', "%{$buscar}%");
            });
        }

        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        return $query->orderBy('first_surname')->orderBy('first_name')->paginate($perPage);
    }

    /**
     * Crea un nuevo colaborador.
     */
    public function create(CreateCollaboratorRequest $request): Collaborator
    {
        return DB::transaction(function () use ($request): Collaborator {
            return Collaborator::create($request->validated());
        });
    }

    /**
     * Actualiza los datos de un colaborador.
     */
    public function update(Collaborator $collaborator, UpdateCollaboratorRequest $request): Collaborator
    {
        DB::transaction(function () use ($collaborator, $request): void {
            $collaborator->update($request->validated());
        });

        return $collaborator->fresh(['documentType', 'status', 'activeContract.position']);
    }

    /**
     * Elimina lógicamente un colaborador.
     */
    public function delete(Collaborator $collaborator): void
    {
        $collaborator->delete();
    }

    /**
     * Retorna el objeto de exportación Excel para la institución.
     */
    public function export(string $institutionId, string $tipo = 'todos'): ColaboradoresExport
    {
        return new ColaboradoresExport($institutionId, $tipo);
    }
}
