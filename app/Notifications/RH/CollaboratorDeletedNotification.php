<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Notification;

class CollaboratorDeletedNotification extends Notification
{
    public function __construct(
        private readonly string $collaboratorName,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Colaborador eliminado',
            'message' => "{$this->collaboratorName} fue eliminado del sistema.",
            'icon' => 'trash',
            'color' => 'red',
            'url' => route('rh.colaboradores.index'),
            'type' => 'collaborator_deleted',
        ];
    }
}
