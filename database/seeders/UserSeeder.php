<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * CONTRASEÑA TEMPORAL DE PRUEBAS: Wis2026*
     *
     * Esta contraseña es exclusiva para entornos de desarrollo y pruebas de UI/UX.
     * Todos los usuarios deben cambiarla antes de pasar a producción.
     * NO usar esta contraseña en ambientes productivos.
     */
    private const PASSWORD_PRUEBAS = 'Wis2026*';

    /**
     * Usuarios migrados del sistema legacy app.wisascun.com.
     *
     * Campos:
     *   document_number    → numdoc del legacy
     *   document_issued_at → expdoc del legacy (fecha de expedición del documento)
     *   name               → nombres + apellidos del legacy
     *   email              → correo del legacy
     *   role               → rol Laravel mapeado desde perfil legacy
     *
     * Mapeo de perfiles legacy → roles Laravel:
     *   Root               → super-admin
     *   Coordinador        → admin
     *   Contabilidad       → accounting-manager
     *   RH Contratistas    → rh-manager
     *   RH Empleados       → rh-manager
     *   Inventarios        → inventory-manager
     */
    private function usuarios(): array
    {
        return [
            [
                'document_number' => '72295936',
                'document_issued_at' => '2003-02-18',
                'name' => 'SUPER ADMINISTRADOR',
                'email' => 'ltoncel@rdssoft.com.co',
                'role' => 'super-admin',
            ],
            [
                'document_number' => '37324165',
                'document_issued_at' => '1989-12-11',
                'name' => 'HILIANET BARBOSA REYES',
                'email' => 'contabiludad@ascun.org.co',
                'role' => 'accounting-manager',
            ],
            [
                'document_number' => '63549971',
                'document_issued_at' => '2002-06-17',
                'name' => 'CAROLINA HENAO MONTOYA',
                'email' => 'administrativo@ascun.org.co',
                'role' => 'admin',
            ],
            [
                'document_number' => '52857025',
                'document_issued_at' => '1999-04-19',
                'name' => 'ANA ISABEL REYES TORRES',
                'email' => 'gestiondocumental@ascun.org.co',
                'role' => 'contractor-manager',
            ],
            [
                'document_number' => '53135875',
                'document_issued_at' => '2004-01-15',
                'name' => 'PAULA ANDREA VELASCO',
                'email' => 'tesoreria@ascun.org.co',
                'role' => 'inventory-manager',
            ],
            [
                'document_number' => '1014233042',
                'document_issued_at' => '2010-06-03',
                'name' => 'JOAN SEBASTIÁN AREVALO',
                'email' => 'sistemas@ascun.org.co',
                'role' => 'admin',
            ],
            [
                'document_number' => '1026283309',
                'document_issued_at' => '2011-04-01',
                'name' => 'YESENIA KATERIN ROJAS MORENO',
                'email' => 'profesional.admin@ascun.org.co',
                'role' => 'employee-manager',
            ],
            [
                'document_number' => '1033815362',
                'document_issued_at' => '2017-04-19',
                'name' => 'INGRID TATIANA CAICEDO',
                'email' => 'apoyosistemas@ascun.org.co',
                'role' => 'admin',
            ],
            [
                'document_number' => '80759183',
                'document_issued_at' => '2001-12-04',
                'name' => 'JORGE BERNAL',
                'email' => 'jbernal@tsedec.com',
                'role' => 'accounting-manager',
            ],
            [
                'document_number' => '1012410970',
                'document_issued_at' => '2012-06-01',
                'name' => 'JOHANNA MONTAÑEZ',
                'email' => 'contabilidad@ascun.org.co',
                'role' => 'accounting-manager',
            ],
        ];
    }

    public function run(): void
    {
        $institution = Institution::where('nit', '860006560')->firstOrFail();

        foreach ($this->usuarios() as $datos) {
            $user = User::firstOrCreate(
                ['document_number' => $datos['document_number']],
                [
                    'institution_id' => $institution->id,
                    'document_type' => 'CC',
                    'document_issued_at' => $datos['document_issued_at'],
                    'name' => $datos['name'],
                    'email' => $datos['email'],
                    'password' => Hash::make(self::PASSWORD_PRUEBAS),
                    'is_active' => true,
                ]
            );

            // Sincronizar rol (idempotente: reemplaza si cambió)
            $user->syncRoles([$datos['role']]);

            $this->command->info(
                sprintf(
                    'Usuario listo: %s [%s] — doc: %s',
                    $user->name,
                    $datos['role'],
                    $user->document_number,
                )
            );
        }

        $this->command->newLine();
        $this->command->warn('Contraseña temporal de todos los usuarios: '.self::PASSWORD_PRUEBAS);
        $this->command->warn('Cambiar antes de subir a producción.');
    }
}
