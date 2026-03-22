<?php

declare(strict_types=1);

namespace Database\Seeders\RH;

use App\Models\Institution;
use App\Models\RH\CollaboratorStatus;
use Illuminate\Database\Seeder;

class CollaboratorStatusSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::first();

        if (! $institution) {
            $this->command->warn('No se encontró ninguna institución. Ejecute primero el seeder de instituciones.');

            return;
        }

        $estados = [
            ['name' => 'Activo',       'icon_class' => 'fa-regular fa-user-check'],
            ['name' => 'Extrabajador', 'icon_class' => 'fa-regular fa-user-xmark'],
            ['name' => 'Inactivo',     'icon_class' => 'fa-regular fa-user-xmark'],
            ['name' => 'Pensionado',   'icon_class' => 'fa-regular fa-island-tropical'],
            ['name' => 'Fallecido',    'icon_class' => 'fa-regular fa-tombstone'],
        ];

        foreach ($estados as $estado) {
            CollaboratorStatus::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'name' => $estado['name'],
                ],
                [
                    'institution_id' => $institution->id,
                    'name' => $estado['name'],
                    'icon_class' => $estado['icon_class'],
                ]
            );
        }

        $this->command->info('Estados de colaborador creados: '.count($estados));
    }
}
