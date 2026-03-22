<?php

declare(strict_types=1);

namespace Database\Seeders\Contabilidad;

use App\Models\Contabilidad\CostCenter;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        // Centros de costo extraídos del sistema legacy (ascunwsi_db_wisascun + sandbox)
        $centers = [
            // Administración (1-x)
            ['code' => '1-1',   'name' => 'Administración general',          'category' => 'ADMINISTRACIÓN'],
            ['code' => '1-10',  'name' => 'Administración - Jurídica',       'category' => 'ADMINISTRACIÓN'],

            // Contratos y convenios (2-x)
            ['code' => '2-10',  'name' => 'Contratos - Servicios generales', 'category' => 'CONTRATOS Y CONVENIOS'],
            ['code' => '2-21',  'name' => 'Contratos - Consultoría',         'category' => 'CONTRATOS Y CONVENIOS'],
            ['code' => '2-33',  'name' => 'Contratos - Investigación',       'category' => 'CONTRATOS Y CONVENIOS'],
            ['code' => '2-50',  'name' => 'Contratos - Tecnología',          'category' => 'CONTRATOS Y CONVENIOS'],

            // Equipo técnico (4-x)
            ['code' => '4-2',   'name' => 'Equipo técnico - Comunicaciones', 'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-4',   'name' => 'Equipo técnico - Sistemas',       'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-10',  'name' => 'Equipo técnico - Investigación',  'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-14',  'name' => 'Equipo técnico - Publicaciones',  'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-16',  'name' => 'Equipo técnico - Biblioteca',     'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-22',  'name' => 'Equipo técnico - Calidad',        'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-26',  'name' => 'Equipo técnico - Gestión',        'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-29',  'name' => 'Equipo técnico - Financiero',     'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-52',  'name' => 'Equipo técnico - Logística',      'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-57',  'name' => 'Equipo técnico - Académico',      'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-59',  'name' => 'Equipo técnico - Proyectos',      'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-63',  'name' => 'Equipo técnico - Planeación',     'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-65',  'name' => 'Equipo técnico - Bienestar',      'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-68',  'name' => 'Equipo técnico - Extensión',      'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-70',  'name' => 'Equipo técnico - Convenios',      'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-83',  'name' => 'Equipo técnico - Internacionalización', 'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-86',  'name' => 'Equipo técnico - Acreditación',   'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-91',  'name' => 'Equipo técnico - Innovación',     'category' => 'EQUIPO TÉCNICO'],
            ['code' => '4-94',  'name' => 'Equipo técnico - Desarrollo',     'category' => 'EQUIPO TÉCNICO'],

            // Administración interna (40-x)
            ['code' => '40-1',  'name' => 'Administración interna - General', 'category' => 'ADMINISTRACIÓN'],
            ['code' => '40-2',  'name' => 'Administración interna - Apoyo',   'category' => 'ADMINISTRACIÓN'],

            // Eventos y programas (50-x)
            ['code' => '50-1',  'name' => 'Eventos - Congresos y seminarios', 'category' => 'EVENTOS Y PROGRAMAS'],
            ['code' => '50-2',  'name' => 'Eventos - Talleres y capacitación', 'category' => 'EVENTOS Y PROGRAMAS'],
            ['code' => '50-3',  'name' => 'Eventos - Programas académicos',   'category' => 'EVENTOS Y PROGRAMAS'],

            // Proyectos (60-x)
            ['code' => '60-1',  'name' => 'Proyectos - Investigación',        'category' => 'PROYECTOS'],
            ['code' => '60-2',  'name' => 'Proyectos - Extensión',            'category' => 'PROYECTOS'],
            ['code' => '60-5',  'name' => 'Proyectos - Internacionales',      'category' => 'PROYECTOS'],

            // Inversión (70-x, 80-x, 90-x)
            ['code' => '70-1',  'name' => 'Inversión - Infraestructura',      'category' => 'INVERSIÓN'],
            ['code' => '80-1',  'name' => 'Inversión - Equipos',              'category' => 'INVERSIÓN'],
            ['code' => '90-1',  'name' => 'Inversión - Tecnología',           'category' => 'INVERSIÓN'],
            ['code' => '90-2',  'name' => 'Inversión - Software',             'category' => 'INVERSIÓN'],

            // Redes institucionales (13-x, 14-x, 15-x, 16-x)
            ['code' => '13-1',  'name' => 'Red - Universidades regionales',   'category' => 'REDES'],
            ['code' => '14-1',  'name' => 'Red - Universidades nacionales',   'category' => 'REDES'],
            ['code' => '15-1',  'name' => 'Red - Cooperación internacional',  'category' => 'REDES'],
            ['code' => '15-17', 'name' => 'Red - Convenios bilaterales',      'category' => 'REDES'],
            ['code' => '15-39', 'name' => 'Red - Alianzas estratégicas',      'category' => 'REDES'],
            ['code' => '15-40', 'name' => 'Red - Proyectos conjuntos',        'category' => 'REDES'],
            ['code' => '15-42', 'name' => 'Red - Intercambios académicos',    'category' => 'REDES'],
            ['code' => '15-43', 'name' => 'Red - Movilidad estudiantil',      'category' => 'REDES'],
            ['code' => '16-1',  'name' => 'Red - Asociaciones nacionales',    'category' => 'REDES'],
            ['code' => '16-2',  'name' => 'Red - Gremios educativos',         'category' => 'REDES'],
            ['code' => '16-4',  'name' => 'Red - Organismos gubernamentales', 'category' => 'REDES'],
            ['code' => '16-8',  'name' => 'Red - Entidades internacionales',  'category' => 'REDES'],

            // Fondos especiales (651-x, 652-x, 656-x, 667-x)
            ['code' => '651-1', 'name' => 'Fondo - Investigación',            'category' => 'FONDOS'],
            ['code' => '652-1', 'name' => 'Fondo - Extensión',                'category' => 'FONDOS'],
            ['code' => '656-1', 'name' => 'Fondo - Bienestar universitario',  'category' => 'FONDOS'],
            ['code' => '667-1', 'name' => 'Fondo - Internacionalización',     'category' => 'FONDOS'],
        ];

        foreach ($centers as $center) {
            CostCenter::firstOrCreate(
                ['code' => $center['code'], 'institution_id' => null],
                array_merge($center, ['institution_id' => null, 'is_active' => true])
            );
        }
    }
}
