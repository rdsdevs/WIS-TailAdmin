<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Exports\RH\ContratistasExport;
use App\Http\Requests\RH\CreateContractorRequest;
use App\Http\Requests\RH\UpdateContractorRequest;
use App\Models\RH\Contractor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ContractorService
{
    /**
     * Lista paginada de contratistas con filtros opcionales.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAll(string $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Contractor::query()
            ->where('institution_id', $institutionId);

        if (! empty($filters['buscar'])) {
            $buscar = $filters['buscar'];
            $query->where(function ($q) use ($buscar): void {
                $q->where('first_name', 'like', "%{$buscar}%")
                    ->orWhere('last_name', 'like', "%{$buscar}%")
                    ->orWhere('document_number', 'like', "%{$buscar}%")
                    ->orWhere('company_name', 'like', "%{$buscar}%");
            });
        }

        if (isset($filters['activo'])) {
            $query->where('is_active', (bool) $filters['activo']);
        }

        return $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage);
    }

    /**
     * Crea un nuevo contratista.
     */
    public function create(CreateContractorRequest $request): Contractor
    {
        return DB::transaction(function () use ($request): Contractor {
            return Contractor::create($request->validated());
        });
    }

    /**
     * Actualiza los datos de un contratista.
     */
    public function update(Contractor $contractor, UpdateContractorRequest $request): Contractor
    {
        $contractor->update($request->validated());

        return $contractor->fresh();
    }

    /**
     * Elimina lógicamente un contratista.
     */
    public function delete(Contractor $contractor): void
    {
        $contractor->delete();
    }

    /**
     * Retorna el objeto de exportación Excel.
     */
    public function export(string $institutionId): ContratistasExport
    {
        return new ContratistasExport($institutionId);
    }
}
