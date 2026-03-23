<?php

declare(strict_types=1);

namespace App\Exports\RH;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CommittedValueTemplateSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Valores_comprometidos';
    }

    public function array(): array
    {
        return [
            $this->headings(),
            $this->exampleRow(),
            $this->noteRow(),
        ];
    }

    private function headings(): array
    {
        return [
            'codigo_contrato (*)',
            'cuenta_contable (*)',
            'centro_de_costo (*)',
            'valor (*)',
        ];
    }

    private function exampleRow(): array
    {
        return [
            '001-2025',
            '711004',
            '4-70',
            '5000000',
        ];
    }

    private function noteRow(): array
    {
        return [
            'NOTA: Solo completar para contratos con fecha_inicio desde el año 2025 en adelante.',
            '',
            '',
            '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 24,
            'B' => 22,
            'C' => 22,
            'D' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Encabezado: fondo azul oscuro, texto blanco, negrita, centrado
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
        ]);

        // Fila de ejemplo: cursiva, fondo gris muy claro
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF888888']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F5']],
        ]);

        // Fila de nota: texto naranja oscuro, negrita
        $sheet->getStyle('A3:D3')->applyFromArray([
            'font' => ['italic' => true, 'bold' => true, 'color' => ['argb' => 'FF8B4513']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF3E0']],
        ]);

        // Merge de celdas para la nota
        $sheet->mergeCells('A3:D3');

        return [];
    }
}
