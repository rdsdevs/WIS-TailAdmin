<?php

declare(strict_types=1);

namespace App\Exports\RH;

use App\Models\RH\Contractor;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ContratistasExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly string $institutionId) {}

    public function query(): Builder
    {
        return Contractor::query()
            ->where('institution_id', $this->institutionId)
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'Tipo Documento',
            'Documento',
            'Nombre / Razón Social',
            'Correo',
            'Teléfono',
            'Estado',
        ];
    }

    /**
     * @param  Contractor  $contractor
     */
    public function map($contractor): array
    {
        return [
            $contractor->document_type,
            $contractor->document_number,
            $contractor->full_name,
            $contractor->email ?? '',
            $contractor->phone ?? '',
            $contractor->is_active ? 'Activo' : 'Inactivo',
        ];
    }

    public function title(): string
    {
        return 'Contratistas';
    }
}
