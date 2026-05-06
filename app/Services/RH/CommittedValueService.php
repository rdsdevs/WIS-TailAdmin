<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use App\Models\RH\ContractExtension;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CommittedValueService
{
    /**
     * Lista paginada de valores comprometidos del contrato (no soft-deleted),
     * ordenados por fecha de creación descendente.
     */
    public function getAllForContract(Contract $contract, int $perPage = 15): LengthAwarePaginator
    {
        return CommittedValue::query()
            ->where('contract_id', $contract->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Crea un valor comprometido para el contrato. La institución se hereda del
     * contrato padre (defense-in-depth contra payloads manipulados).
     */
    public function create(Contract $contract, array $data): CommittedValue
    {
        return DB::transaction(function () use ($contract, $data): CommittedValue {
            return CommittedValue::create([
                'institution_id' => $contract->institution_id,
                'contract_id' => $contract->id,
                'accounting_account' => $data['accounting_account'],
                'cost_center' => $data['cost_center'],
                'amount' => $data['amount'],
            ]);
        });
    }

    /**
     * Actualiza un valor comprometido. No permite mover la línea a otro
     * contrato ni cambiar la institución (preserva integridad histórica).
     */
    public function update(CommittedValue $committedValue, array $data): CommittedValue
    {
        DB::transaction(function () use ($committedValue, $data): void {
            $committedValue->update([
                'accounting_account' => $data['accounting_account'],
                'cost_center' => $data['cost_center'],
                'amount' => $data['amount'],
            ]);
        });

        return $committedValue->refresh();
    }

    /**
     * Elimina (soft-delete) un valor comprometido. Bloquea la eliminación si
     * existe alguna prórroga aplicada sobre la línea para preservar la
     * trazabilidad histórica del contrato.
     *
     * @throws DomainException
     */
    public function delete(CommittedValue $committedValue): void
    {
        $hasExtension = ContractExtension::query()
            ->where('committed_value_id', $committedValue->id)
            ->exists();

        if ($hasExtension) {
            throw new DomainException(
                'No se puede eliminar el valor comprometido porque existe al menos una prórroga aplicada sobre él.'
            );
        }

        $committedValue->delete();
    }
}
