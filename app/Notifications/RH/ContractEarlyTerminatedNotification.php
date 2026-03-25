<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractEarlyTerminatedNotification extends Notification
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
            'title' => "Contrato {$this->contractCode} terminado anticipadamente",
            'message' => "El contrato {$this->contractCode} fue terminado antes de su fecha de finalización programada.",
            'type' => 'contract_early_terminated',
            'color' => 'red',
            'contract_id' => $this->contractId,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contrato terminado anticipadamente')
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line("El contrato {$this->contractCode} fue terminado antes de su fecha de finalización programada.")
            ->action('Ver contrato', route('rh.contratos.show', $this->contractId))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
