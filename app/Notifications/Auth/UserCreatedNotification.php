<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    public function __construct(
        private readonly string $userName,
        private readonly string $userEmail,
        private readonly string $temporaryPassword,
        private readonly string $roleName,
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
            'title' => 'Cuenta creada',
            'message' => "Tu cuenta en WIS ASCUN fue creada. Usuario: {$this->userEmail}",
            'icon' => 'user-plus',
            'color' => 'green',
            'url' => route('dashboard'),
            'type' => 'user_created',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu cuenta en WIS ASCUN fue creada')
            ->greeting('Hola, '.$this->userName.'.')
            ->line('Tu cuenta de acceso al sistema WIS ASCUN ha sido creada.')
            ->line("Usuario: {$this->userEmail}")
            ->line("Contraseña temporal: {$this->temporaryPassword}")
            ->line("Rol asignado: {$this->roleName}")
            ->line('Por favor cambia tu contraseña al ingresar por primera vez.')
            ->action('Iniciar sesión', route('login'))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
