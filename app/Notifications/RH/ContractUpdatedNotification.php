<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Notification;

class ContractUpdatedNotification extends Notification
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
        $message = $this->contractCode !== ''
            ? "El contrato {$this->contractCode} fue actualizado."
            : 'Un contrato fue actualizado.';

        return [
            'title'   => 'Contrato actualizado',
            'message' => $message,
            'icon'    => 'pencil',
            'color'   => 'amber',
            'url'     => route('rh.contratos.edit', $this->contractId),
            'type'    => 'contract_updated',
        ];
    }
}
