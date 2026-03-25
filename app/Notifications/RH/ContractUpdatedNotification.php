<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractUpdatedNotification extends Notification
{
    public function __construct(
        private readonly string $contractCode,
        private readonly string $contractId,
        private readonly string $collaboratorName,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $message = $this->contractCode !== ''
            ? "El contrato {$this->contractCode} fue actualizado."
            : 'Un contrato fue actualizado.';

        return [
            'title' => 'Contrato actualizado',
            'message' => $message,
            'icon' => 'pencil',
            'color' => 'amber',
            'url' => route('rh.contratos.edit', $this->contractId),
            'type' => 'contract_updated',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->contractCode !== ''
            ? "El contrato {$this->contractCode} fue actualizado."
            : 'Un contrato fue actualizado.';

        return (new MailMessage)
            ->subject('Contrato actualizado')
            ->greeting('Hola, '.$this->collaboratorName.'.')
            ->line($message)
            ->action('Ver contrato', route('rh.contratos.edit', $this->contractId))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
