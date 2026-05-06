<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Models\RH\Contract;
use App\Models\RH\PositionChangeHistory;
use DomainException;
use Illuminate\Support\Facades\DB;

final class PositionChangeService
{
    /**
     * Aplica un cambio de cargo al contrato dentro de una transacción atómica.
     *
     * El ajuste de salario/honorarios es opcional. Si no se proporciona,
     * el contrato conserva los montos actuales y el histórico registra el
     * salario actual como old_salary y new_salary.
     *
     * @param  array{
     *   new_position_id: string,
     *   change_date: string,
     *   adjust_compensation: bool,
     *   new_amount: float|null,
     *   observations: string|null,
     * }  $data
     *
     * @throws DomainException si el colaborador no es de tipo Empleado.
     */
    public function apply(Contract $contract, array $data): PositionChangeHistory
    {
        $collaborator = $contract->relationLoaded('collaborator')
            ? $contract->collaborator
            : $contract->collaborator()->first();

        if ($collaborator === null || $collaborator->type !== 'Empleado') {
            throw new DomainException(
                'El cambio de cargo solo aplica a colaboradores de tipo Empleado.'
            );
        }

        return DB::transaction(function () use ($contract, $data): PositionChangeHistory {
            $previousPositionId = $contract->position_id;
            $oldSalary = (float) ($contract->salary ?? 0);
            $oldFees = (float) ($contract->fees ?? 0);
            $isFeesContract = $oldFees > 0;
            $oldAmount = $isFeesContract ? $oldFees : $oldSalary;

            $newAmount = $oldAmount;
            if (($data['adjust_compensation'] ?? false) && $data['new_amount'] !== null) {
                $newAmount = (float) $data['new_amount'];
            }

            $history = PositionChangeHistory::create([
                'contract_id' => $contract->id,
                'previous_position_id' => $previousPositionId,
                'new_position_id' => $data['new_position_id'],
                'old_salary' => $oldAmount,
                'new_salary' => $newAmount,
                'change_date' => $data['change_date'],
                'observations' => $data['observations'] ?? null,
            ]);

            $update = ['position_id' => $data['new_position_id']];
            if (($data['adjust_compensation'] ?? false) && $data['new_amount'] !== null) {
                if ($isFeesContract) {
                    $update['fees'] = $newAmount;
                } else {
                    $update['salary'] = $newAmount;
                }
            }
            $contract->update($update);

            return $history;
        });
    }
}
