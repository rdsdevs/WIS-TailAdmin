<?php

declare(strict_types=1);

namespace Database\Seeders\RH;

use App\Models\Institution;
use App\Models\RH\Department;
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

        // Obtener dependencias por nombre
        $depto = fn (string $nombre) => Department::where('institution_id', $institution->id)
            ->where('name', $nombre)
            ->first();

        $cargos = [
            // Dirección General
            ['department' => 'Dirección General',        'name' => 'Director General',             'description' => 'Máxima autoridad ejecutiva de la asociación.'],
            ['department' => 'Dirección General',        'name' => 'Asistente de Dirección',       'description' => 'Apoyo administrativo a la dirección general.'],
            // RH
            ['department' => 'Recursos Humanos',         'name' => 'Coordinador de RH',            'description' => 'Coordinación del área de recursos humanos.'],
            ['department' => 'Recursos Humanos',         'name' => 'Analista de Nómina',           'description' => 'Liquidación y procesamiento de nómina.'],
            ['department' => 'Recursos Humanos',         'name' => 'Auxiliar de RH',               'description' => 'Apoyo en actividades administrativas de RH.'],
            // Contabilidad
            ['department' => 'Contabilidad y Finanzas',  'name' => 'Contador Público',             'description' => 'Gestión contable y tributaria.'],
            ['department' => 'Contabilidad y Finanzas',  'name' => 'Auxiliar Contable',            'description' => 'Apoyo en registros contables y causaciones.'],
            // Sistemas
            ['department' => 'Sistemas de Información',  'name' => 'Profesional de Sistemas',     'description' => 'Administración de sistemas y soporte tecnológico.'],
            ['department' => 'Sistemas de Información',  'name' => 'Técnico de Soporte',           'description' => 'Soporte técnico a usuarios.'],
            // Comunicaciones
            ['department' => 'Comunicaciones',           'name' => 'Comunicador Social',           'description' => 'Gestión de comunicación institucional y medios.'],
        ];

        foreach ($cargos as $datos) {
            $departamento = $depto($datos['department']);

            if (! $departamento) {
                $this->command->warn("No se encontró la dependencia: {$datos['department']}");

                continue;
            }

            Position::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'name' => $datos['name'],
                ],
                [
                    'institution_id' => $institution->id,
                    'department_id' => $departamento->id,
                    'name' => $datos['name'],
                    'description' => $datos['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Cargos creados: '.count($cargos));
    }
}
