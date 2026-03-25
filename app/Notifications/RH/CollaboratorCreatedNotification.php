<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaboratorCreatedNotification extends Notification
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
            'title' => 'Colaborador creado',
            'message' => "{$this->collaboratorName} fue registrado en el sistema.",
            'icon' => 'user-plus',
            'color' => 'green',
            'url' => route('rh.colaboradores.show', $this->collaboratorId),
            'type' => 'collaborator_created',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo colaborador registrado')
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line("{$this->collaboratorName} fue registrado en el sistema.")
            ->action('Ver colaborador', route('rh.colaboradores.show', $this->collaboratorId))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
