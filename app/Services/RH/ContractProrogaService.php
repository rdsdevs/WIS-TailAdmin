<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use App\Models\RH\ContractExtension;
use App\Notifications\RH\ContractProrogaAppliedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ContractProrogaService
{
    /**
     * Retorna true si el contrato requiere que el usuario seleccione
     * a qué CommittedValue se aplica el valor de la prórroga.
     *
     * Condición: contrato de 2025 en adelante Y tiene más de un CommittedValue activo.
     */
    public function needsCostCenterSelection(Contract $contract): bool
    {
        if ($contract->start_date->year < 2025) {
            return false;
        }

        return $contract->committedValues()->count() > 1;
    }

    /**
     * Retorna los CommittedValues del contrato para mostrar en el selector.
     * Solo aplica cuando needsCostCenterSelection() === true.
     */
    public function getCommittedValueOptions(Contract $contract): Collection
    {
        return $contract->committedValues()->get();
    }

    /**
     * Aplica la prórroga al contrato dentro de una transacción atómica.
     *
     * @param  array{
     *   extension_type: string,
     *   extension_months: int|null,
     *   extension_days: int|null,
     *   extension_value: float|null,
     *   committed_value_id: string|null,
     *   approval_date: string,
     *   reason: string|null,
     *   institution_id: string,
     * }  $data
     */
    public function apply(Contract $contract, array $data): ContractExtension
    {
        return DB::transaction(function () use ($contract, $data): ContractExtension {
            $extensionType = $data['extension_type'];
            $newEndDate = null;

            // 1. Procesar prórroga de tiempo
            if ($extensionType === 'tiempo' || $extensionType === 'tiempo_y_valor') {
                $baseDate = $contract->end_date ?? now()->toDateString();
                $date = \Carbon\Carbon::parse($baseDate);

                if (! empty($data['extension_months'])) {
                    $date->addMonths((int) $data['extension_months']);
                }

                if (! empty($data['extension_days'])) {
                    $date->addDays((int) $data['extension_days']);
                }

                $newEndDate = $date->toDateString();

                $contract->update(['end_date' => $newEndDate]);
            }

            // 2. Procesar prórroga de valor
            if ($extensionType === 'valor' || $extensionType === 'tiempo_y_valor') {
                $extensionValue = (float) ($data['extension_value'] ?? 0);

                if ((float) $contract->fees > 0) {
                    $contract->increment('fees', $extensionValue);
                } elseif ((float) $contract->salary > 0) {
                    $contract->increment('salary', $extensionValue);
                }

                // Actualizar CommittedValue si el contrato es de 2025 en adelante
                if ($contract->start_date->year >= 2025) {
                    if (! empty($data['committed_value_id'])) {
                        // Verificar ownership: el CommittedValue debe pertenecer a este contrato
                        $committedValue = $contract->committedValues()
                            ->where('id', $data['committed_value_id'])
                            ->firstOrFail();
                        $committedValue->increment('amount', $extensionValue);
                    } else {
                        $committedValue = $contract->committedValues()->first();
                        $committedValue?->increment('amount', $extensionValue);
                    }
                }
            }

            // 3. Crear el registro de la prórroga
            $extension = ContractExtension::create([
                'contract_id' => $contract->id,
                'extension_date' => now()->toDateString(),
                'reason' => $data['reason'] ?? null,
                'extension_type' => $extensionType,
                'extension_months' => $data['extension_months'] ?? null,
                'extension_days' => $data['extension_days'] ?? null,
                'extension_value' => $data['extension_value'] ?? null,
                'new_end_date' => $newEndDate,
                'approval_date' => $data['approval_date'],
                'institution_id' => $data['institution_id'],
                'committed_value_id' => $data['committed_value_id'] ?? null,
            ]);

            // 4. Notificar
            auth()->user()?->notify(new ContractProrogaAppliedNotification(
                $contract->contract_code ?? '',
                $contract->id,
                $extensionType,
            ));

            Log::info('Prórroga aplicada al contrato', [
                'contract_id' => $contract->id,
                'contract_code' => $contract->contract_code,
                'extension_type' => $extensionType,
                'extension_id' => $extension->id,
            ]);

            return $extension;
        });
    }
}
