<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractImportCompletedNotification extends Notification
{
    public function __construct(
        private readonly array $result,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $isError = isset($this->result['error']);
        $hasFailures = ! $isError && (count($this->result['row_errors'] ?? []) + count($this->result['failures'] ?? [])) > 0;
        $imported = $this->result['imported'] ?? 0;
        $updated = $this->result['updated'] ?? 0;
        $skipped = $this->result['skipped'] ?? 0;

        return [
            'title' => $isError ? 'Error en importación de contratos' : 'Importación de contratos completada',
            'message' => $isError
                ? ($this->result['error'] ?? 'Ocurrió un error durante la importación.')
                : "Se importaron {$imported} contratos. Actualizados: {$updated}. Omitidos: {$skipped}.",
            'icon' => 'arrow-up-tray',
            'color' => $isError || $hasFailures ? 'red' : 'green',
            'url' => route('rh.contratos.index'),
            'type' => 'contract_import_completed',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isError = isset($this->result['error']);
        $imported = $this->result['imported'] ?? 0;
        $updated = $this->result['updated'] ?? 0;
        $skipped = $this->result['skipped'] ?? 0;

        $subject = $isError
            ? 'Error en importación de contratos'
            : 'Importación de contratos completada';

        $message = $isError
            ? ($this->result['error'] ?? 'Ocurrió un error durante la importación.')
            : "Se importaron {$imported} contratos. Actualizados: {$updated}. Omitidos: {$skipped}.";

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line($message)
            ->action('Ver contratos', route('rh.contratos.index'))
            ->line('Este mensaje fue generado automáticamente por WIS ASCUN.');
    }
}
