<?php

declare(strict_types=1);

namespace App\Exports\RH;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CollaboratorTemplateExport implements WithMultipleSheets
{
    public function __construct(private readonly string $type = 'todos') {}

    public function sheets(): array
    {
        $sheets = [];

        if (in_array($this->type, ['todos', 'empleados'])) {
            $sheets[] = new CollaboratorTemplateSheet('Empleados', false);
        }
        if (in_array($this->type, ['todos', 'contratistas'])) {
            $sheets[] = new CollaboratorTemplateSheet('Contratistas', true);
        }
        $sheets[] = new CollaboratorCatalogsSheet;

        return $sheets;
    }
}
