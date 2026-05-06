<?php

declare(strict_types=1);

namespace Database\Seeders\RH;

use App\Models\RH\Contract;
use App\Models\RH\PositionChangeHistory;
use Illuminate\Database\Seeder;

/**
 * Genera un registro inicial en `position_change_history` para cada contrato
 * que tenga un cargo asignado y aún no posea historial.
 *
 * Este seeder es idempotente: solo inserta histórico para contratos sin
 * registros previos, por lo que puede ejecutarse múltiples veces sin riesgo.
 */
class PositionChangeBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $contratos = Contract::query()
            ->whereNotNull('position_id')
            ->whereDoesntHave('positionChangeHistory')
            ->cursor();

        $insertados = 0;

        foreach ($contratos as $contrato) {
            $monto = (float) ($contrato->fees ?? 0) > 0
                ? (float) $contrato->fees
                : (float) ($contrato->salary ?? 0);

            $changeDate = $contrato->start_date?->toDateString() ?? now()->toDateString();

            PositionChangeHistory::create([
                'contract_id' => $contrato->id,
                'previous_position_id' => null,
                'new_position_id' => $contrato->position_id,
                'old_salary' => $monto,
                'new_salary' => $monto,
                'change_date' => $changeDate,
                'observations' => 'Cargo inicial (backfill).',
            ]);

            $insertados++;
        }

        $this->command?->info("Backfill de cambios de cargo: {$insertados} registros creados.");
    }
}
