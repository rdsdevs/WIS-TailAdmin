<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Exports\RH\ColaboradoresExport;
use App\Http\Requests\RH\CreateCollaboratorRequest;
use App\Http\Requests\RH\UpdateCollaboratorRequest;
use App\Models\RH\Collaborator;
use App\Notifications\RH\CollaboratorCreatedNotification;
use App\Notifications\RH\CollaboratorDeletedNotification;
use App\Notifications\RH\CollaboratorTypeChangedNotification;
use App\Notifications\RH\CollaboratorUpdatedNotification;
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
            $colaborador = Collaborator::create($request->validated());
            auth()->user()?->notify(new CollaboratorCreatedNotification($colaborador->full_name, $colaborador->id));

            return $colaborador;
        });
    }

    /**
     * Actualiza los datos de un colaborador.
     */
    public function update(Collaborator $collaborator, UpdateCollaboratorRequest $request): Collaborator
    {
        DB::transaction(function () use ($collaborator, $request): void {
            $collaborator->update($request->validated());
            auth()->user()?->notify(new CollaboratorUpdatedNotification($collaborator->full_name, $collaborator->id));
        });

        return $collaborator->fresh(['documentType', 'status', 'activeContract.position']);
    }

    /**
     * Determina si un colaborador puede cambiar de tipo.
     * Solo es posible si no tiene contratos vigentes.
     */
    public function canChangeType(Collaborator $collaborator): bool
    {
        if ($collaborator->is_company) {
            return false;
        }

        return ! $collaborator->contracts()
            ->where('status', 'Vigente')
            ->exists();
    }

    /**
     * Cambia el tipo de un colaborador entre Empleado y Contratista.
     *
     * @throws \RuntimeException si el colaborador tiene contratos vigentes.
     */
    public function changeType(Collaborator $collaborator): Collaborator
    {
        if (! $this->canChangeType($collaborator)) {
            throw new \RuntimeException('No se puede cambiar el tipo de colaborador mientras tenga contratos activos.');
        }

        $newType = $collaborator->type === 'Empleado' ? 'Contratista' : 'Empleado';

        return DB::transaction(function () use ($collaborator, $newType): Collaborator {
            $collaborator->update(['type' => $newType]);
            auth()->user()?->notify(new CollaboratorTypeChangedNotification($collaborator->full_name, $collaborator->id, $newType));

            return $collaborator->fresh();
        });
    }

    /**
     * Elimina lógicamente un colaborador.
     */
    public function delete(Collaborator $collaborator): void
    {
        $nombre = $collaborator->full_name;
        $collaborator->delete();
        auth()->user()?->notify(new CollaboratorDeletedNotification($nombre));
    }

    /**
     * Retorna el objeto de exportación Excel para la institución.
     */
    public function export(string $institutionId, string $tipo = 'todos'): ColaboradoresExport
    {
        return new ColaboradoresExport($institutionId, $tipo);
    }
}
