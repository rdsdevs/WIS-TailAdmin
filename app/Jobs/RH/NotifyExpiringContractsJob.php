<?php

declare(strict_types=1);

namespace App\Jobs\RH;

use App\Models\RH\Contract;
use App\Models\RH\ContractExpiringNotificationLog;
use App\Models\User;
use App\Notifications\RH\ContractExpiringSoonNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotifyExpiringContractsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MANAGER_ROLES = ['super-admin', 'admin', 'rh-manager', 'contractor-manager'];

    /**
     * Notifica una vez (por threshold) a los gestores de cada institución
     * de los contratos vigentes próximos a vencer.
     */
    public function handle(): void
    {
        $thresholds = config('rh.expiring_thresholds', [7, 15, 30]);

        if (! is_array($thresholds) || $thresholds === []) {
            Log::warning('[NotifyExpiringContractsJob] config rh.expiring_thresholds vacío o inválido.');

            return;
        }

        // Procesar de menor a mayor para que un mismo contrato cercano dispare
        // primero el threshold más urgente.
        sort($thresholds);

        $totalNotified = 0;

        foreach ($thresholds as $days) {
            $totalNotified += $this->processThreshold((int) $days);
        }

        Log::info("[NotifyExpiringContractsJob] {$totalNotified} notificación(es) enviada(s).");
    }

    /**
     * Procesa los contratos por vencer en exactamente {threshold} días o menos
     * que aún no fueron notificados para ese threshold.
     */
    private function processThreshold(int $threshold): int
    {
        $alreadyNotifiedIds = ContractExpiringNotificationLog::query()
            ->where('threshold_days', $threshold)
            ->pluck('contract_id')
            ->all();

        $contracts = Contract::query()
            ->expiringSoon($threshold)
            ->whereNotIn('id', $alreadyNotifiedIds)
            ->with(['collaborator'])
            ->get();

        if ($contracts->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($contracts as $contract) {
            $this->notifyContract($contract, $threshold);
            $count++;
        }

        return $count;
    }

    private function notifyContract(Contract $contract, int $threshold): void
    {
        $managers = User::query()
            ->where('institution_id', $contract->institution_id)
            ->where('is_active', true)
            ->role(self::MANAGER_ROLES)
            ->get();

        if ($managers->isEmpty()) {
            return;
        }

        $endDate = $contract->end_date?->format('d/m/Y') ?? '';
        $daysRemaining = (int) max(0, now()->startOfDay()->diffInDays($contract->end_date, false));

        $notification = new ContractExpiringSoonNotification(
            contractId: $contract->id,
            contractCode: $contract->contract_code ?? '',
            collaboratorName: $contract->collaborator?->full_name ?? '',
            endDate: $endDate,
            daysRemaining: $daysRemaining,
        );

        foreach ($managers as $manager) {
            $manager->notify($notification);
        }

        ContractExpiringNotificationLog::create([
            'id' => (string) Str::uuid(),
            'contract_id' => $contract->id,
            'threshold_days' => $threshold,
            'notified_at' => now(),
        ]);
    }
}
