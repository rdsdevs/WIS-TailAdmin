<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractProrogaAppliedNotification extends Notification
{
    public function __construct(
        private readonly string $contractCode,
        private readonly string $contractId,
        private readonly string $extensionType,
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
        return [
            'title' => "Prórroga aplicada al contrato {$this->contractCode}",
            'message' => "Se aplicó una prórroga de tipo {$this->extensionType} al contrato {$this->contractCode}.",
            'type' => 'contract_proroga_applied',
            'color' => 'blue',
            'contract_id' => $this->contractId,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Prórroga aplicada al contrato')
            ->greeting('Hola, '.$this->collaboratorName.'.')
            ->line("Se aplicó una prórroga de tipo {$this->extensionType} al contrato {$this->contractCode}.")
            ->action('Ver contrato', route('rh.contratos.show', $this->contractId))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
