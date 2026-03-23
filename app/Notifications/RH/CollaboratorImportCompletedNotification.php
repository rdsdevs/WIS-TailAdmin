<?php

declare(strict_types=1);

namespace App\Notifications\RH;

use Illuminate\Notifications\Notification;

class CollaboratorImportCompletedNotification extends Notification
{
    public function __construct(
        private readonly array $result,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $isError    = isset($this->result['error']);
        $hasFailures = ! $isError && (count($this->result['row_errors'] ?? []) + count($this->result['failures'] ?? [])) > 0;
        $imported   = $this->result['imported'] ?? 0;
        $skipped    = $this->result['skipped'] ?? 0;

        return [
            'title'   => $isError ? 'Error en importación de colaboradores' : 'Importación de colaboradores completada',
            'message' => $isError
                ? ($this->result['error'] ?? 'Ocurrió un error durante la importación.')
                : "Se importaron {$imported} colaboradores. Omitidos: {$skipped}.",
            'icon'    => 'arrow-up-tray',
            'color'   => $isError || $hasFailures ? 'red' : 'green',
            'url'     => route('rh.colaboradores.index'),
            'type'    => 'collaborator_import_completed',
        ];
    }
}
