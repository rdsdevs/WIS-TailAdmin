<?php

declare(strict_types=1);

namespace App\Exports\RH;

use App\Models\RH\Collaborator;
use App\Models\RH\ContractType;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractCatalogsSheet implements FromArray, WithStyles, WithTitle
{
    public function __construct(private readonly string $institutionId) {}

    public function title(): string
    {
        return 'Catálogos';
    }

    public function array(): array
    {
        $rows = [];

        // ── Sección 1: Estados válidos ────────────────────────────────────────
        $rows[] = ['ESTADOS VÁLIDOS', ''];
        foreach (['Vigente', 'Terminado', 'Liquidado', 'Vencido', 'Borrador'] as $estado) {
            $rows[] = [$estado, ''];
        }
        $rows[] = ['', ''];

        // ── Sección 2: Tipos de contrato ──────────────────────────────────────
        $rows[] = ['TIPOS DE CONTRATO', ''];
        $tiposContrato = ContractType::where('institution_id', $this->institutionId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        foreach ($tiposContrato as $tipo) {
            $rows[] = [$tipo, ''];
        }

        if (empty($tiposContrato)) {
            $rows[] = ['(No hay tipos de contrato registrados)', ''];
        }
        $rows[] = ['', ''];

        // ── Sección 3: Colaboradores ──────────────────────────────────────────
        $rows[] = ['COLABORADORES', 'Nombre completo'];
        $colaboradores = Collaborator::where('institution_id', $this->institutionId)
            ->whereNull('deleted_at')
            ->orderBy(DB::raw("CONCAT(first_name, ' ', first_surname)"))
            ->select('document_number', 'first_name', 'second_name', 'first_surname', 'second_surname', 'company_name', 'is_company')
            ->limit(1000)
            ->get();

        foreach ($colaboradores as $colaborador) {
            if ($colaborador->is_company) {
                $nombre = $colaborador->company_name ?? '';
            } else {
                $nombre = trim(implode(' ', array_filter([
                    $colaborador->first_name,
                    $colaborador->second_name,
                    $colaborador->first_surname,
                    $colaborador->second_surname,
                ])));
            }
            $rows[] = [$colaborador->document_number, $nombre];
        }

        if ($colaboradores->isEmpty()) {
            $rows[] = ['(No hay colaboradores registrados)', ''];
        }
        $rows[] = ['', ''];

        // ── Sección 4: Instrucciones ──────────────────────────────────────────
        $rows[] = ['INSTRUCCIONES', ''];
        $rows[] = ['(*)', 'Columnas obligatorias'];
        $rows[] = ['Fechas', 'Formato dd/mm/yyyy  (ej: 15/01/2024)'];
        $rows[] = ['Contratos antes de 2019', 'Dejar num_contrato y codigo_contrato vacíos'];
        $rows[] = ['Contratos 2019-2024', 'num_contrato: 3 dígitos (ej: 001)  |  codigo_contrato: NNN-AAAA (ej: 001-2022)'];
        $rows[] = ['Contratos 2025+', 'Mismo formato anterior + completar hoja Valores_comprometidos'];
        $rows[] = ['Límite contratos', 'Máximo 500 contratos por archivo'];
        $rows[] = ['Límite valores', 'Máximo 2000 valores comprometidos por archivo'];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Identificar filas de encabezado de sección
        $sectionRows = [];
        $data = $this->array();

        foreach ($data as $i => $row) {
            $cellValue = $row[0] ?? '';
            if (in_array($cellValue, ['ESTADOS VÁLIDOS', 'TIPOS DE CONTRATO', 'COLABORADORES', 'INSTRUCCIONES'], true)) {
                $sectionRows[] = $i + 1; // 1-indexed
            }
        }

        foreach ($sectionRows as $rowNum) {
            $sheet->getStyle("A{$rowNum}:B{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            ]);
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(70);

        return [];
    }
}
