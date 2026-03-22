<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\RH\CollaboratorStatusSeeder;
use Database\Seeders\RH\ContractTypeSeeder;
use Database\Seeders\RH\DepartmentSeeder;
use Database\Seeders\RH\DocumentTypeSeeder;
use Database\Seeders\RH\PositionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Pobla la base de datos con datos iniciales del sistema WIS ASCUN.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            // Módulo RH — orden importa por FKs
            DocumentTypeSeeder::class,
            CollaboratorStatusSeeder::class,
            ContractTypeSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
        ]);
    }
}
