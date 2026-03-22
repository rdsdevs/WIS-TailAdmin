<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Http\Requests\RH\CreateContractRequest;
use App\Http\Requests\RH\UpdateContractRequest;
use App\Models\RH\CommittedValue;
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
     * Crea un nuevo contrato junto con sus líneas de comprometido opcionales.
     *
     * Los datos del contrato se toman de $request->validated() excluyendo
     * 'committed_values', que se persisten por separado dentro de la misma
     * transacción.
     */
    public function create(CreateContractRequest $request): Contract
    {
        return DB::transaction(function () use ($request): Contract {
            $validated = $request->validated();
            $committedLines = $validated['committed_values'] ?? [];

            $contract = Contract::create(
                collect($validated)->except('committed_values')->all()
            );

            if (! empty($committedLines)) {
                $this->persistCommittedValues($contract, $committedLines);
            }

            return $contract;
        });
    }

    /**
     * Actualiza un contrato existente y reemplaza sus líneas de comprometido.
     */
    public function update(Contract $contract, UpdateContractRequest $request): Contract
    {
        DB::transaction(function () use ($contract, $request): void {
            $validated = $request->validated();
            $committedLines = $validated['committed_values'] ?? null;

            $contract->update(
                collect($validated)->except('committed_values')->all()
            );

            // Solo sincronizar si el array viene explícitamente en el request
            if ($committedLines !== null) {
                $this->syncCommittedValues($contract, $committedLines);
            }
        });

        return $contract->fresh();
    }

    /**
     * Reemplaza todas las líneas de comprometido de un contrato.
     *
     * Elimina (soft-delete) las líneas existentes y crea las nuevas en una
     * sola operación. Pasar un array vacío borra todas las líneas vigentes.
     */
    public function syncCommittedValues(Contract $contract, array $lines): void
    {
        DB::transaction(function () use ($contract, $lines): void {
            // Soft-delete de todas las líneas actuales
            $contract->committedValues()->delete();

            if (! empty($lines)) {
                $this->persistCommittedValues($contract, $lines);
            }
        });
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

    // ── Métodos privados ──────────────────────────────────────────────────────

    /**
     * Persiste un conjunto de líneas de comprometido para un contrato.
     * Debe llamarse siempre dentro de una transacción activa.
     *
     * @param  array<int, array{accounting_account: string, cost_center: string, amount: numeric-string|float}>  $lines
     */
    private function persistCommittedValues(Contract $contract, array $lines): void
    {
        $records = array_map(
            fn (array $line): array => [
                'institution_id' => $contract->institution_id,
                'contract_id' => $contract->id,
                'accounting_account' => $line['accounting_account'],
                'cost_center' => $line['cost_center'],
                'amount' => $line['amount'],
            ],
            $lines
        );

        CommittedValue::insert(
            array_map(
                fn (array $record): array => array_merge($record, [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ]),
                $records
            )
        );
    }
}
