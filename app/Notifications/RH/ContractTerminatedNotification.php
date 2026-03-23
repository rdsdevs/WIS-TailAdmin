<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Notification;

class ContractTerminatedNotification extends Notification
{
    public function __construct(
        private readonly string $contractCode,
        private readonly string $contractId,
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
            'title'   => 'Contrato terminado',
            'message' => "El contrato {$this->contractCode} fue marcado como terminado.",
            'icon'    => 'check-circle',
            'color'   => 'blue',
            'url'     => route('rh.contratos.index'),
            'type'    => 'contract_terminated',
        ];
    }
}
