<?php

declare(strict_types=1);

namespace Database\Seeders\RH;

use App\Models\Institution;
use App\Models\RH\Contract;
use App\Models\RH\Department;
use App\Models\RH\Employee;
use App\Models\RH\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::first();

        if (! $institution) {
            $this->command->warn('No se encontró ninguna institución.');

            return;
        }

        $posiciones = Position::where('institution_id', $institution->id)->get();
        $departamentos = Department::where('institution_id', $institution->id)->get();

        if ($posiciones->isEmpty() || $departamentos->isEmpty()) {
            $this->command->warn('Ejecute primero DepartmentSeeder y PositionSeeder.');

            return;
        }

        $empleados = [
            ['first_name' => 'Carlos Alberto',   'last_name' => 'Martínez Gómez',   'document' => '79456123', 'salary' => 8500000],
            ['first_name' => 'María Fernanda',    'last_name' => 'Rodríguez Torres',  'document' => '52341890', 'salary' => 5200000],
            ['first_name' => 'Juan David',        'last_name' => 'López Herrera',     'document' => '1020345678', 'salary' => 3800000],
            ['first_name' => 'Andrea Paola',      'last_name' => 'González Vargas',   'document' => '53241760', 'salary' => 4100000],
            ['first_name' => 'Luis Ernesto',      'last_name' => 'Pérez Ríos',        'document' => '80123456', 'salary' => 6300000],
            ['first_name' => 'Diana Carolina',    'last_name' => 'Sánchez Mejía',     'document' => '46712389', 'salary' => 3200000],
            ['first_name' => 'Hernán Felipe',     'last_name' => 'Castro Molina',     'document' => '71890234', 'salary' => 7500000],
            ['first_name' => 'Claudia Marcela',   'last_name' => 'Reyes Ospina',      'document' => '41234567', 'salary' => 3800000],
            ['first_name' => 'Andrés Mauricio',   'last_name' => 'Morales Jiménez',   'document' => '1013456789', 'salary' => 2800000],
            ['first_name' => 'Patricia Elena',    'last_name' => 'Vargas Acosta',     'document' => '55678912', 'salary' => 4500000],
            ['first_name' => 'Ricardo Armando',   'last_name' => 'Suárez Pinto',      'document' => '79345678', 'salary' => 5800000],
            ['first_name' => 'Marcela Eugenia',   'last_name' => 'Álvarez Serrano',   'document' => '43210987', 'salary' => 3600000],
            ['first_name' => 'Jaime Alejandro',   'last_name' => 'Pinzón Rojas',      'document' => '80765432', 'salary' => 9200000],
            ['first_name' => 'Nathalia Cristina', 'last_name' => 'Ramírez Cruz',      'document' => '1019234567', 'salary' => 2600000],
            ['first_name' => 'Guillermo Antonio', 'last_name' => 'Torres Barrera',    'document' => '17456789', 'salary' => 4800000],
            ['first_name' => 'Sandra Milena',     'last_name' => 'Méndez Cano',       'document' => '52891234', 'salary' => 3400000],
            ['first_name' => 'Fabio Alejandro',   'last_name' => 'Gómez Valencia',    'document' => '71234890', 'salary' => 6100000],
            ['first_name' => 'Laura Viviana',     'last_name' => 'Arias Montoya',     'document' => '47890123', 'salary' => 3900000],
            ['first_name' => 'Édgar Enrique',     'last_name' => 'Bejarano Sierra',   'document' => '19234567', 'salary' => 5400000],
            ['first_name' => 'Constanza Isabel',  'last_name' => 'Trujillo Agudelo',  'document' => '42345678', 'salary' => 4200000],
        ];

        foreach ($empleados as $datos) {
            $posicion = $posiciones->random();
            $departamento = $departamentos->firstWhere('id', $posicion->department_id) ?? $departamentos->first();

            /** @var Employee $empleado */
            $empleado = Employee::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'document_number' => $datos['document'],
                ],
                [
                    'institution_id' => $institution->id,
                    'position_id' => $posicion->id,
                    'department_id' => $departamento->id,
                    'document_type' => 'CC',
                    'document_number' => $datos['document'],
                    'first_name' => $datos['first_name'],
                    'last_name' => $datos['last_name'],
                    'email' => Str::lower(
                        Str::ascii($datos['first_name'][0]).
                        '.'.
                        Str::ascii(explode(' ', $datos['last_name'])[0]).
                        '@ascun.edu.co'
                    ),
                    'salary' => $datos['salary'],
                    'is_active' => true,
                ]
            );

            // Crear contrato activo para el empleado
            if ($empleado->wasRecentlyCreated) {
                Contract::create([
                    'institution_id' => $institution->id,
                    'contractable_id' => $empleado->id,
                    'contractable_type' => Employee::class,
                    'contract_type' => 'indefinite',
                    'start_date' => now()->subYears(rand(1, 5))->startOfMonth(),
                    'end_date' => null,
                    'salary' => $datos['salary'],
                    'position' => $posicion->name,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Empleados de prueba creados: '.count($empleados));
    }
}
