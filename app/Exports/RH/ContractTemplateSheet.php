<?php

declare(strict_types=1);

namespace App\Exports\RH;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractTemplateSheet implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Contratos';
    }

    public function array(): array
    {
        return [
            $this->headings(),
            $this->exampleRow(),
        ];
    }

    private function headings(): array
    {
        return [
            'documento_colaborador (*)',
            'tipo_contrato (*)',
            'fecha_inicio (*) dd/mm/yyyy',
            'fecha_fin dd/mm/yyyy',
            'objeto (*)',
            'obligaciones',
            'honorarios',
            'salario',
            'correo_cargo',
            'estado (*)',
            'num_contrato (solo >= 2019)',
            'codigo_contrato (solo >= 2019)',
        ];
    }

    private function exampleRow(): array
    {
        return [
            '1234567890',
            'Prestador de servicios',
            '15/01/2024',
            '31/12/2024',
            'Prestar servicios de asesoría en...',
            '1) Cumplir con el objeto...',
            '5000000',
            '0',
            'administrativo@ascun.org.co',
            'Terminado',
            '001',
            '001-2024',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 28,
            'C' => 26,
            'D' => 22,
            'E' => 45,
            'F' => 40,
            'G' => 15,
            'H' => 15,
            'I' => 32,
            'J' => 16,
            'K' => 26,
            'L' => 26,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Encabezado: fondo azul oscuro, texto blanco, negrita, centrado
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
        ]);

        // Fila de ejemplo: cursiva, fondo gris muy claro
        $sheet->getStyle('A2:L2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF888888']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F5']],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event): void {
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }
}
