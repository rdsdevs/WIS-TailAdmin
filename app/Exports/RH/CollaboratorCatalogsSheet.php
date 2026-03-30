<?php

declare(strict_types=1);

namespace App\Exports\RH;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CollaboratorCatalogsSheet implements FromArray, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Catálogos';
    }

    public function array(): array
    {
        return [
            ['TIPOS DE DOCUMENTO', ''],
            ['CC',  'Cédula de Ciudadanía'],
            ['CE',  'Cédula de Extranjería'],
            ['NIT', 'Número de Identificación Tributaria (solo empresas)'],
            ['PAP', 'Pasaporte'],
            ['TI',  'Tarjeta de Identidad'],
            ['NIP', 'Número de Identificación Personal'],
            ['', ''],
            ['GÉNEROS', ''],
            ['M', 'Masculino'],
            ['F', 'Femenino'],
            ['O', 'Otro'],
            ['', ''],
            ['ESTADOS', ''],
            ['Activo',       ''],
            ['Extrabajador', ''],
            ['Inactivo',     ''],
            ['Pensionado',   ''],
            ['Fallecido',    ''],
            ['', ''],
            ['INDICACIONES', ''],
            ['(*)', 'Columnas obligatorias'],
            ['Fechas', 'Formato dd/mm/yyyy  (ej: 15/06/1995)'],
            ['Empresas', 'Solo en hoja Contratistas. Coloque SI en columna es_empresa'],
            ['NIT empresa', 'Cuando es_empresa=SI, coloque el NIT en la columna nit'],
            ['Límite', 'Máximo 500 filas por archivo'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        foreach ([1, 9, 14, 21] as $row) {
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            ]);
        }
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(55);

        return [];
    }
}
