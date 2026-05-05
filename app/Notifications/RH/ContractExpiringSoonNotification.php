<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringSoonNotification extends Notification
{
    public function __construct(
        private readonly string $contractId,
        private readonly string $contractCode,
        private readonly string $collaboratorName,
        private readonly string $endDate,
        private readonly int $daysRemaining,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('rh.notify_expiring_via_mail', false)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "Contrato {$this->contractCode} próximo a vencer",
            'message' => "El contrato de {$this->collaboratorName} vence en {$this->daysRemaining} día(s) ({$this->endDate}).",
            'type' => 'contract_expiring_soon',
            'color' => $this->daysRemaining <= 7 ? 'red' : ($this->daysRemaining <= 15 ? 'amber' : 'blue'),
            'contract_id' => $this->contractId,
            'days_remaining' => $this->daysRemaining,
            'end_date' => $this->endDate,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Contrato {$this->contractCode} próximo a vencer")
            ->greeting('Hola.')
            ->line("El contrato de {$this->collaboratorName} (código {$this->contractCode}) vence el {$this->endDate}, en {$this->daysRemaining} día(s).")
            ->action('Ver contrato', route('rh.contratos.show', $this->contractId))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
