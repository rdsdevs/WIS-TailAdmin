<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Http\Requests\RH\CreateContractRequest;
use App\Http\Requests\RH\UpdateContractRequest;
use App\Models\RH\Contract;
use App\Models\RH\Contractor;
use App\Models\RH\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ContractService
{
    /**
     * Contratos activos de la institución, paginados.
     */
    public function getActive(string $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        return Contract::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->with('contractable')
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Contratos próximos a vencer en los siguientes X días.
     */
    public function getExpiringSoon(string $institutionId, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Contract::query()
            ->where('institution_id', $institutionId)
            ->expiringSoon($days)
            ->with('contractable')
            ->orderBy('end_date')
            ->get();
    }

    /**
     * Crea un nuevo contrato.
     * Resuelve el tipo polimórfico (employee → Employee::class, contractor → Contractor::class).
     */
    public function create(CreateContractRequest $request): Contract
    {
        return DB::transaction(function () use ($request): Contract {
            $validated = $request->validated();

            // Resolver el FQCN del modelo a partir del string del formulario
            $validated['contractable_type'] = $this->resolveContractableType($validated['contractable_type']);

            return Contract::create($validated);
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
     * Termina (desactiva) un contrato activo.
     */
    public function terminate(Contract $contract): void
    {
        $contract->update([
            'is_active' => false,
            'end_date' => $contract->end_date ?? now()->toDateString(),
        ]);
    }

    /**
     * Convierte el string del formulario al FQCN del modelo Eloquent.
     */
    private function resolveContractableType(string $type): string
    {
        return match ($type) {
            'employee' => Employee::class,
            'contractor' => Contractor::class,
            default => throw new \InvalidArgumentException("Tipo de vinculado no válido: {$type}"),
        };
    }
}
