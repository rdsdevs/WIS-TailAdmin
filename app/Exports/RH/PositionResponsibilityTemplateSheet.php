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

class PositionResponsibilityTemplateSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Responsabilidades';
    }

    public function array(): array
    {
        return [
            ['nombre_cargo', 'responsabilidad'],
            ['Coordinador de Programas', 'Garantizar el cumplimiento de los objetivos académicos institucionales.'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 70,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Encabezado: fondo azul oscuro, texto blanco, negrita
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
        ]);

        // Fila de ejemplo: cursiva, color gris tenue
        $sheet->getStyle('A2:B2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF888888']],
        ]);

        // Congelar fila de encabezado
        $sheet->freezePane('A2');

        return [];
    }
}
