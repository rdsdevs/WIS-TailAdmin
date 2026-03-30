<?php

declare(strict_types=1);

namespace App\Notifications\RH;

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
        return ['database'];
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
}
