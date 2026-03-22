<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Contabilidad\AccountingAccountSeeder;
use Database\Seeders\Contabilidad\CostCenterSeeder;
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
            InstitutionSeeder::class,   // debe correr antes que UserSeeder
            UserSeeder::class,          // requiere institución y roles ya creados
            // Módulo RH — orden importa por FKs
            DocumentTypeSeeder::class,
            CollaboratorStatusSeeder::class,
            ContractTypeSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            // Módulo Contabilidad — catálogos
            AccountingAccountSeeder::class,
            CostCenterSeeder::class,
        ]);
    }
}
