<?php

declare(strict_types=1);

namespace App\Notifications\RH;

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
        return ['database'];
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
}
