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
     *
     * El histórico de cargos se gestiona explícitamente desde el flujo
     * de "Cambio de cargo" (PositionChangeService), no desde el observer.
     */
    public function updated(Contract $contract): void
    {
        //
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
