<?php

declare(strict_types=1);

namespace App\Exports\RH;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ContractTemplateExport implements WithMultipleSheets
{
    public function __construct(private readonly string $institutionId) {}

    public function sheets(): array
    {
        return [
            new ContractTemplateSheet(),
            new CommittedValueTemplateSheet(),
            new ContractCatalogsSheet($this->institutionId),
        ];
    }
}
