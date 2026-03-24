<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Notification;

class ContractProrogaAppliedNotification extends Notification
{
    public function __construct(
        private readonly string $contractCode,
        private readonly string $contractId,
        private readonly string $extensionType,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "Prórroga aplicada al contrato {$this->contractCode}",
            'message' => "Se aplicó una prórroga de tipo {$this->extensionType} al contrato {$this->contractCode}.",
            'type' => 'contract_proroga_applied',
            'color' => 'blue',
            'contract_id' => $this->contractId,
        ];
    }
}
