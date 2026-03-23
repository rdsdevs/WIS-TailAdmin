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

class CollaboratorTemplateSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    private array $headings;
    private array $exampleRow;

    public function __construct(
        private readonly string $sheetTitle,
        private readonly bool $includeCompany,
    ) {
        $this->headings = $this->buildHeadings();
        $this->exampleRow = $this->buildExample();
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function array(): array
    {
        return [$this->headings, $this->exampleRow];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 18, 'B' => 20, 'C' => 18, 'D' => 18, 'E' => 18,
            'F' => 18, 'G' => 18, 'H' => 18, 'I' => 10, 'J' => 28,
            'K' => 16, 'L' => 30, 'M' => 16,
        ];

        if ($this->includeCompany) {
            $widths['N'] = 12;
            $widths['O'] = 30;
            $widths['P'] = 20;
            $widths['Q'] = 30;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $this->includeCompany ? 'Q' : 'M';

        // Encabezado: fondo azul oscuro, texto blanco, negrita
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
        ]);

        // Fila de ejemplo: cursiva, color gris tenue
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF888888']],
        ]);

        // Congelar fila de encabezado
        $sheet->freezePane('A2');

        return [];
    }

    private function buildHeadings(): array
    {
        $base = [
            'tipo_documento (*)',
            'numero_documento (*)',
            'fecha_expedicion',
            'primer_nombre (*)',
            'segundo_nombre',
            'primer_apellido (*)',
            'segundo_apellido',
            'fecha_nacimiento',
            'genero',
            'correo',
            'telefono',
            'direccion',
            'estado (*)',
        ];

        if ($this->includeCompany) {
            array_push($base, 'es_empresa', 'razon_social', 'nit', 'representante_legal');
        }

        return $base;
    }

    private function buildExample(): array
    {
        $base = [
            'CC', '1234567890', '15/03/1990', 'ANA', 'ISABEL',
            'REYES', 'TORRES', '10/01/1990', 'F',
            'ana@correo.co', '3001234567', 'Calle 10 #5-20', 'Activo',
        ];

        if ($this->includeCompany) {
            array_push($base, 'NO', '', '', '');
        }

        return $base;
    }
}
