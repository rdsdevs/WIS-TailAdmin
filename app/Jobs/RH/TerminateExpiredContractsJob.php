<?php

declare(strict_types=1);

namespace App\Jobs\RH;

use App\Models\RH\Contract;
use App\Models\User;
use App\Notifications\RH\ContractTerminatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TerminateExpiredContractsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Termina en lote todos los contratos cuya fecha de vencimiento
     * ya pasó y cuyo estado sigue siendo 'Vigente'.
     *
     * Se ejecuta diariamente a medianoche desde el scheduler.
     */
    public function handle(): void
    {
        // Obtener los IDs y datos necesarios antes de la transacción
        $expiredContracts = Contract::query()
            ->where('status', 'Vigente')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->with(['institution'])
            ->get(['id', 'institution_id', 'contract_code']);

        if ($expiredContracts->isEmpty()) {
            Log::info('[TerminateExpiredContractsJob] No hay contratos vencidos para terminar.');

            return;
        }

        $ids = $expiredContracts->pluck('id')->all();

        DB::transaction(function () use ($ids): void {
            Contract::whereIn('id', $ids)->update(['status' => 'Terminado']);
        });

        $total = count($ids);

        Log::info(
            "[TerminateExpiredContractsJob] {$total} contrato(s) marcado(s) como 'Terminado' automáticamente.",
            ['ids' => $ids]
        );

        // Notificar a los gestores RH y administradores de cada institución afectada
        foreach ($expiredContracts as $contract) {
            $managers = User::query()
                ->where('institution_id', $contract->institution_id)
                ->where('is_active', true)
                ->role(['super-admin', 'admin', 'rh-manager'])
                ->get();

            foreach ($managers as $manager) {
                $manager->notify(new ContractTerminatedNotification(
                    $contract->contract_code ?? '',
                    $contract->id,
                ));
            }
        }
    }
}
