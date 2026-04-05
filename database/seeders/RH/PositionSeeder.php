<?php

declare(strict_types=1);

namespace Database\Seeders\RH;

use App\Models\Institution;
use App\Models\RH\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::first();

        if (! $institution) {
            $this->command->warn('No se encontró ninguna institución.');

            return;
        }

        $cargos = [
            // Dirección General
            ['name' => 'Director General',         'description' => 'Máxima autoridad ejecutiva de la asociación.'],
            ['name' => 'Asistente de Dirección',   'description' => 'Apoyo administrativo a la dirección general.'],
            // RH
            ['name' => 'Coordinador de RH',        'description' => 'Coordinación del área de recursos humanos.'],
            ['name' => 'Analista de Nómina',        'description' => 'Liquidación y procesamiento de nómina.'],
            ['name' => 'Auxiliar de RH',            'description' => 'Apoyo en actividades administrativas de RH.'],
            // Contabilidad
            ['name' => 'Contador Público',          'description' => 'Gestión contable y tributaria.'],
            ['name' => 'Auxiliar Contable',         'description' => 'Apoyo en registros contables y causaciones.'],
            // Sistemas
            ['name' => 'Profesional de Sistemas',  'description' => 'Administración de sistemas y soporte tecnológico.'],
            ['name' => 'Técnico de Soporte',        'description' => 'Soporte técnico a usuarios.'],
            // Comunicaciones
            ['name' => 'Comunicador Social',        'description' => 'Gestión de comunicación institucional y medios.'],
        ];

        foreach ($cargos as $datos) {
            Position::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'name' => $datos['name'],
                ],
                [
                    'institution_id' => $institution->id,
                    'name' => $datos['name'],
                    'description' => $datos['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Cargos creados: '.count($cargos));
    }
}
