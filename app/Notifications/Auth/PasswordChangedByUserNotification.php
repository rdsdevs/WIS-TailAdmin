<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedByUserNotification extends Notification
{
    public function __construct(
        private readonly string $userName,
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
            'title' => 'Contraseña actualizada',
            'message' => 'Tu contraseña fue cambiada exitosamente.',
            'icon' => 'lock-closed',
            'color' => 'green',
            'url' => route('profile'),
            'type' => 'password_changed_by_user',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu contraseña en WIS ASCUN fue actualizada')
            ->greeting('Hola, '.$this->userName.'.')
            ->line('Tu contraseña de acceso al sistema WIS ASCUN fue cambiada exitosamente.')
            ->line('Si no realizaste este cambio, contacta al administrador inmediatamente.')
            ->action('Ver mi perfil', route('profile'));
    }
}
