<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractDeletedNotification extends Notification
{
    public function __construct(
        private readonly string $contractCode,
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
            'title' => 'Contrato eliminado',
            'message' => "El contrato {$this->contractCode} fue eliminado del sistema.",
            'icon' => 'trash',
            'color' => 'red',
            'url' => route('rh.contratos.index'),
            'type' => 'contract_deleted',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contrato eliminado')
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line("El contrato {$this->contractCode} fue eliminado del sistema.")
            ->action('Ver contratos', route('rh.contratos.index'))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
