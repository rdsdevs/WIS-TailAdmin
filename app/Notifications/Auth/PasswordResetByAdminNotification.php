<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetByAdminNotification extends Notification
{
    public function __construct(
        private readonly string $userName,
        private readonly string $temporaryPassword,
        private readonly string $adminName,
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
            'title' => 'Contraseña restablecida',
            'message' => 'Un administrador restableció tu contraseña.',
            'icon' => 'key',
            'color' => 'amber',
            'url' => route('login'),
            'type' => 'password_reset_by_admin',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu contraseña en WIS ASCUN fue restablecida')
            ->greeting('Hola, '.$this->userName.'.')
            ->line("El administrador {$this->adminName} restableció tu contraseña de acceso al sistema.")
            ->line("Nueva contraseña temporal: {$this->temporaryPassword}")
            ->line('Por favor cambia tu contraseña al ingresar.')
            ->action('Iniciar sesión', route('login'))
            ->line('Si no solicitaste este cambio, contacta al administrador inmediatamente.');
    }
}
