<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Http\Requests\RH\CreateContractRequest;
use App\Http\Requests\RH\UpdateContractRequest;
use App\Models\RH\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ContractService
{
    /**
     * Contratos vigentes de la institución, paginados.
     */
    public function getActive(string $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        return Contract::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Vigente')
            ->with(['collaborator', 'contractType', 'position'])
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Contratos próximos a vencer en los siguientes X días.
     */
    public function getExpiringSoon(string $institutionId, int $days = 30): Collection
    {
        return Contract::query()
            ->where('institution_id', $institutionId)
            ->expiringSoon($days)
            ->with(['collaborator', 'contractType', 'position'])
            ->orderBy('end_date')
            ->get();
    }

    /**
     * Crea un nuevo contrato.
     */
    public function create(CreateContractRequest $request): Contract
    {
        return DB::transaction(function () use ($request): Contract {
            return Contract::create($request->validated());
        });
    }

    /**
     * Actualiza un contrato existente.
     */
    public function update(Contract $contract, UpdateContractRequest $request): Contract
    {
        DB::transaction(function () use ($contract, $request): void {
            $contract->update($request->validated());
        });

        return $contract->fresh();
    }

    /**
     * Termina un contrato vigente cambiando su estado a 'Terminado'.
     */
    public function terminate(Contract $contract): void
    {
        $contract->update([
            'status' => 'Terminado',
            'end_date' => $contract->end_date ?? now()->toDateString(),
        ]);
    }
}
