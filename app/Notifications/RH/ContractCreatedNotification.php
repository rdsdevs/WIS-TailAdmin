<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractCreatedNotification extends Notification
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
            ? "Contrato {$this->contractCode} para {$this->collaboratorName} fue creado."
            : "Nuevo contrato para {$this->collaboratorName} fue creado.";

        return [
            'title' => 'Contrato creado',
            'message' => $message,
            'icon' => 'document-plus',
            'color' => 'green',
            'url' => route('rh.contratos.show', $this->contractId),
            'type' => 'contract_created',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->contractCode !== ''
            ? "Contrato {$this->contractCode} para {$this->collaboratorName} fue creado."
            : "Nuevo contrato para {$this->collaboratorName} fue creado.";

        return (new MailMessage)
            ->subject('Nuevo contrato registrado')
            ->greeting('Hola, '.$this->collaboratorName.'.')
            ->line($message)
            ->action('Ver contrato', route('rh.contratos.show', $this->contractId))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
