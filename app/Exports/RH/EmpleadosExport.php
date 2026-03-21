<?php

declare(strict_types=1);

namespace App\Exports\RH;

use App\Models\RH\Contract;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmpleadosExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly string $institutionId) {}

    public function query(): Builder
    {
        return Employee::query()
            ->where('institution_id', $this->institutionId)
            ->with([
                'position',
                'department',
                'contracts' => fn ($q) => $q->where('is_active', true)->latest('start_date'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'Tipo Documento',
            'Cédula',
            'Nombre Completo',
            'Cargo',
            'Dependencia',
            'Salario',
            'Fecha de Ingreso',
            'Tipo Contrato',
            'Estado',
        ];
    }

    /**
     * @param  Employee  $employee
     */
    public function map($employee): array
    {
        /** @var Contract|null $contrato */
        $contrato = $employee->contracts->first();

        return [
            $employee->document_type,
            $employee->document_number,
            $employee->full_name,
            $employee->position?->name ?? '',
            $employee->department?->name ?? '',
            '$ '.number_format((float) $employee->salary, 2, ',', '.'),
            $contrato?->start_date?->format('d/m/Y') ?? '',
            $contrato ? $this->labelTipoContrato($contrato->contract_type) : '',
            $employee->is_active ? 'Activo' : 'Inactivo',
        ];
    }

    public function title(): string
    {
        return 'Empleados';
    }

    private function labelTipoContrato(string $type): string
    {
        return match ($type) {
            'indefinite' => 'Término indefinido',
            'fixed_term' => 'Término fijo',
            'contractor' => 'Contratista',
            'intern' => 'Práctica / Pasantía',
            default => $type,
        };
    }
}
