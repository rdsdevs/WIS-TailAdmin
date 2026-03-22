<?php

declare(strict_types=1);

namespace Database\Seeders\RH;

use App\Models\Institution;
use App\Models\RH\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::first();

        if (! $institution) {
            $this->command->warn('No se encontró ninguna institución. Ejecute primero el seeder de instituciones.');

            return;
        }

        $tipos = [
            ['code' => 'IDFD',  'name' => 'Indefinido',              'description' => null],
            ['code' => 'FIAA',  'name' => 'Fijo Inferior a un año',  'description' => null],
            ['code' => 'OPS',   'name' => 'OPS',                     'description' => 'Orden de Prestación de Servicios'],
            ['code' => 'CPS',   'name' => 'Prestador de Servicios',  'description' => null],
            ['code' => 'PTCT',  'name' => 'Practicante',             'description' => null],
            ['code' => 'APRE',  'name' => 'Aprendizaje',             'description' => null],
            ['code' => 'MO',    'name' => 'Mano de obra',            'description' => null],
            ['code' => 'ARRE',  'name' => 'Arrendamiento',           'description' => null],
            ['code' => 'COMPR', 'name' => 'Compraventa',             'description' => null],
            ['code' => 'CASE',  'name' => 'Contrato de Asesoría',    'description' => null],
        ];

        foreach ($tipos as $tipo) {
            ContractType::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'code' => $tipo['code'],
                ],
                [
                    'institution_id' => $institution->id,
                    'code' => $tipo['code'],
                    'name' => $tipo['name'],
                    'description' => $tipo['description'],
                ]
            );
        }

        $this->command->info('Tipos de contrato creados: '.count($tipos));
    }
}
