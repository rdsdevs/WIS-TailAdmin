<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Crea la institución principal ASCUN.
     *
     * Usa firstOrCreate para ser idempotente: si ya existe, no la duplica.
     */
    public function run(): void
    {
        $institution = Institution::firstOrCreate(
            ['nit' => '860006560'],
            [
                'name'      => 'Asociación Colombiana de Universidades',
                'city'      => 'Bogotá',
                'is_active' => true,
            ]
        );

        $this->command->info("Institución lista: {$institution->name} (ID: {$institution->id})");
    }
}
