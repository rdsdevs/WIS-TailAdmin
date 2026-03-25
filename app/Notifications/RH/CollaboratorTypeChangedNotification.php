<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaboratorTypeChangedNotification extends Notification
{
    public function __construct(
        private readonly string $collaboratorName,
        private readonly string $collaboratorId,
        private readonly string $newType,
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
            'title' => 'Tipo de colaborador cambiado',
            'message' => "{$this->collaboratorName} ahora es {$this->newType}.",
            'icon' => 'arrows-right-left',
            'color' => 'blue',
            'url' => route('rh.colaboradores.show', $this->collaboratorId),
            'type' => 'collaborator_type_changed',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tipo de colaborador actualizado')
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line("{$this->collaboratorName} ahora es {$this->newType}.")
            ->action('Ver colaborador', route('rh.colaboradores.show', $this->collaboratorId))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
