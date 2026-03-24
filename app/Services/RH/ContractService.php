<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Http\Requests\RH\CreateContractRequest;
use App\Http\Requests\RH\UpdateContractRequest;
use App\Models\RH\Contract;
use App\Notifications\RH\ContractCreatedNotification;
use App\Notifications\RH\ContractEarlyTerminatedNotification;
use App\Notifications\RH\ContractTerminatedNotification;
use App\Notifications\RH\ContractUpdatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            $contract->load('collaborator');
            auth()->user()?->notify(new ContractCreatedNotification(
                $contract->contract_code ?? '',
                $contract->id,
                $contract->collaborator?->full_name ?? '',
            ));

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

            auth()->user()?->notify(new ContractUpdatedNotification(
                $contract->contract_code ?? '',
                $contract->id,
            ));
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
     * No modifica end_date; esa fecha es la pactada originalmente.
     */
    public function terminate(Contract $contract): void
    {
        $contract->update([
            'status' => 'Terminado',
        ]);

        auth()->user()?->notify(new ContractTerminatedNotification(
            $contract->contract_code ?? '',
            $contract->id,
        ));
    }

    /**
     * Termina anticipadamente un contrato antes de su fecha de finalización pactada.
     *
     * @param  array{early_termination_date: string, early_termination_reason: string}  $data
     */
    public function earlyTerminate(Contract $contract, array $data): void
    {
        DB::transaction(function () use ($contract, $data): void {
            $contract->update([
                'status' => 'Terminado',
                'early_termination_date' => $data['early_termination_date'],
                'early_termination_reason' => $data['early_termination_reason'],
                'early_terminated_by' => auth()->id(),
                'early_terminated_at' => now(),
            ]);

            // Notificar al usuario que realizó la terminación
            auth()->user()?->notify(new ContractEarlyTerminatedNotification(
                $contract->contract_code ?? '',
                $contract->id,
            ));

            Log::info('Contrato terminado anticipadamente', [
                'contract_id' => $contract->id,
                'contract_code' => $contract->contract_code,
                'early_termination_date' => $data['early_termination_date'],
                'terminated_by' => auth()->id(),
            ]);
        });
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
        foreach ($lines as $line) {
            $contract->committedValues()->create([
                'institution_id' => $contract->institution_id,
                'accounting_account' => $line['accounting_account'],
                'cost_center' => $line['cost_center'],
                'amount' => $line['amount'],
            ]);
        }
    }
}
