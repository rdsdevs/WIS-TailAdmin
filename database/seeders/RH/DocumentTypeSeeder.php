<?php

declare(strict_types=1);

namespace Database\Seeders\RH;

use App\Models\Institution;
use App\Models\RH\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::first();

        if (! $institution) {
            $this->command->warn('No se encontró ninguna institución. Ejecute primero el seeder de instituciones.');

            return;
        }

        $tipos = [
            ['code' => 'CC',  'name' => 'Cédula de Ciudadanía'],
            ['code' => 'CE',  'name' => 'Cédula de Extranjería'],
            ['code' => 'NIT', 'name' => 'Número de Identificación Tributaria'],
            ['code' => 'PAP', 'name' => 'Pasaporte'],
            ['code' => 'TI',  'name' => 'Tarjeta de Identidad'],
            ['code' => 'NIP', 'name' => 'Número de Identificación Personal'],
        ];

        foreach ($tipos as $tipo) {
            DocumentType::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'code' => $tipo['code'],
                ],
                [
                    'institution_id' => $institution->id,
                    'code' => $tipo['code'],
                    'name' => $tipo['name'],
                ]
            );
        }

        $this->command->info('Tipos de documento creados: '.count($tipos));
    }
}
