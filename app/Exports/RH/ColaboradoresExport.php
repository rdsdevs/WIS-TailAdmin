<?php

declare(strict_types=1);

namespace App\Exports\RH;

use App\Models\RH\Collaborator;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ColaboradoresExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly string $institutionId,
        private readonly string $tipo = 'todos'
    ) {}

    public function query(): Builder
    {
        $query = Collaborator::query()
            ->where('institution_id', $this->institutionId)
            ->with(['documentType', 'status', 'activeContract.position']);

        if ($this->tipo === 'empleados') {
            $query->empleados();
        } elseif ($this->tipo === 'contratistas') {
            $query->contratistas();
        }

        return $query->orderBy('first_surname')->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Tipo Documento',
            'Número Documento',
            'Nombre Completo',
            'Correo',
            'Teléfono',
            'Estado',
            'Cargo Actual',
        ];
    }

    public function map($collaborator): array
    {
        return [
            $collaborator->type,
            $collaborator->documentType?->code ?? '',
            $collaborator->document_number,
            $collaborator->full_name,
            $collaborator->email ?? '',
            $collaborator->phone ?? '',
            $collaborator->status?->name ?? '',
            $collaborator->activeContract?->position?->name ?? '',
        ];
    }
}
