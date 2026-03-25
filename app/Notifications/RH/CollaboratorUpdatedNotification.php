<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaboratorUpdatedNotification extends Notification
{
    public function __construct(
        private readonly string $collaboratorName,
        private readonly string $collaboratorId,
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
            'title' => 'Colaborador actualizado',
            'message' => "Los datos de {$this->collaboratorName} fueron actualizados.",
            'icon' => 'pencil',
            'color' => 'amber',
            'url' => route('rh.colaboradores.show', $this->collaboratorId),
            'type' => 'collaborator_updated',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Colaborador actualizado')
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line("Los datos de {$this->collaboratorName} fueron actualizados.")
            ->action('Ver colaborador', route('rh.colaboradores.show', $this->collaboratorId))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
