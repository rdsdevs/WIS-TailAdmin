<?php

declare(strict_types=1);

namespace Database\Seeders\RH;

use App\Models\Institution;
use App\Models\RH\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener la primera institución disponible (o crearla si no existe)
        $institution = Institution::first();

        if (! $institution) {
            $this->command->warn('No se encontró ninguna institución. Ejecute primero el seeder de instituciones.');

            return;
        }

        $departamentos = [
            [
                'name' => 'Dirección General',
                'description' => 'Área de dirección estratégica y representación legal de la asociación.',
            ],
            [
                'name' => 'Recursos Humanos',
                'description' => 'Gestión del talento humano, nómina, contratos y bienestar laboral.',
            ],
            [
                'name' => 'Contabilidad y Finanzas',
                'description' => 'Gestión contable, presupuestal y reportes financieros.',
            ],
            [
                'name' => 'Sistemas de Información',
                'description' => 'Administración de infraestructura tecnológica y sistemas de información.',
            ],
            [
                'name' => 'Comunicaciones',
                'description' => 'Comunicación institucional, prensa y relaciones públicas.',
            ],
        ];

        foreach ($departamentos as $datos) {
            Department::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'name' => $datos['name'],
                ],
                array_merge($datos, [
                    'institution_id' => $institution->id,
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('Dependencias creadas: '.count($departamentos));
    }
}
