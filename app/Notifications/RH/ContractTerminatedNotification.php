<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
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
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Contrato terminado',
            'message' => "El contrato {$this->contractCode} fue marcado como terminado.",
            'icon' => 'check-circle',
            'color' => 'blue',
            'url' => route('rh.contratos.index'),
            'type' => 'contract_terminated',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contrato terminado')
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line("El contrato {$this->contractCode} fue marcado como terminado.")
            ->action('Ver contratos', route('rh.contratos.index'))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
