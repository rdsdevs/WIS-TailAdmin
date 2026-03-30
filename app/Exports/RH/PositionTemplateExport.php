<?php

declare(strict_types=1);

namespace App\Exports\RH;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PositionTemplateExport implements WithMultipleSheets
{
    public function __construct(private readonly string $tipo = 'cargos') {}

    public function sheets(): array
    {
        return match ($this->tipo) {
            'funciones' => [new PositionFunctionTemplateSheet],
            'correos'   => [new PositionEmailTemplateSheet],
            default     => [new PositionTemplateSheet],
        };
    }
}
