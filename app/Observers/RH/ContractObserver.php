<?php

namespace App\Observers\RH;

use App\Models\RH\Contract;

class ContractObserver
{
    /**
     * Handle the Contract "created" event.
     */
    public function created(Contract $contract): void
    {
        //
    }

    /**
     * Handle the Contract "updated" event.
     */
    public function updated(Contract $contract): void
    {
        if ($contract->wasChanged(['position_id', 'salary'])) {
            // Solo registrar si hay un cargo asignado
            if ($contract->position_id !== null) {
                $contract->positionChangeHistory()->create([
                    'previous_position_id' => $contract->getOriginal('position_id'),
                    'new_position_id' => $contract->position_id,
                    'new_salary' => $contract->salary,
                    'observations' => 'Cambio automático detectado por actualización de contrato.',
                ]);
            }
        }
    }

    /**
     * Handle the Contract "deleted" event.
     */
    public function deleted(Contract $contract): void
    {
        //
    }

    /**
     * Handle the Contract "restored" event.
     */
    public function restored(Contract $contract): void
    {
        //
    }

    /**
     * Handle the Contract "force deleted" event.
     */
    public function forceDeleted(Contract $contract): void
    {
        //
    }
}
