<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Crear los roles necesarios antes de cada test
beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/**
 * Helper: crea institución y usuario con el rol dado.
 */
function crearContextoAdmin(string $rol): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    return [$user, $institution];
}

/**
 * Helper: datos válidos para crear un usuario.
 */
function datosUsuarioValidos(string $institutionId): array
{
    return [
        'name'                  => 'Carlos Pérez',
        'email'                 => 'carlos.perez@example.com',
        'document_type'         => 'CC',
        'document_number'       => '10203040',
        'document_issued_at'    => '2010-06-15',
        'password'              => 'Clave1234*',
        'password_confirmation' => 'Clave1234*',
        'roles'                 => ['rh-viewer'],
    ];
}

describe('Gestión de Usuarios', function (): void {

    it('puede listar usuarios (admin)', function (): void {
        [$admin] = crearContextoAdmin('admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.usuarios.index'));

        $response->assertOk();
    });

    it('puede crear un usuario (admin)', function (): void {
        [$admin, $institution] = crearContextoAdmin('admin');

        $datos = datosUsuarioValidos($institution->id);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'document_number' => '10203040',
            'name'            => 'Carlos Pérez',
        ]);
    });

    it('rechaza la creación con datos inválidos', function (): void {
        [$admin] = crearContextoAdmin('admin');

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'name'               => '',
                'document_type'      => 'XX',
                'document_number'    => '',
                'document_issued_at' => 'no-es-fecha',
                'password'           => '123',
                'roles'              => [],
            ])
            ->assertSessionHasErrors(['name', 'document_type', 'document_number', 'document_issued_at', 'password', 'roles']);
    });

    it('impide que rh-manager acceda a usuarios', function (): void {
        [$rhManager] = crearContextoAdmin('rh-manager');

        $this->actingAs($rhManager)
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
    });

    it('puede editar un usuario de su institución', function (): void {
        [$admin, $institution] = crearContextoAdmin('admin');

        $objetivo = User::factory()->create(['institution_id' => $institution->id]);
        $objetivo->assignRole('rh-viewer');

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $objetivo), [
                'name'               => 'Nombre Actualizado',
                'document_type'      => $objetivo->document_type,
                'document_number'    => $objetivo->document_number,
                'document_issued_at' => $objetivo->document_issued_at->format('Y-m-d'),
                'email'              => $objetivo->email,
                'password'           => '',
                'roles'              => ['rh-viewer'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id'   => $objetivo->id,
            'name' => 'Nombre Actualizado',
        ]);
    });

    it('no puede editar usuario de otra institución', function (): void {
        [$admin] = crearContextoAdmin('admin');

        $otraInstitucion = Institution::factory()->create();
        $objetivoOtra = User::factory()->create(['institution_id' => $otraInstitucion->id]);
        $objetivoOtra->assignRole('rh-viewer');

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $objetivoOtra), [
                'name'               => 'Hackeo',
                'document_type'      => $objetivoOtra->document_type,
                'document_number'    => $objetivoOtra->document_number,
                'document_issued_at' => $objetivoOtra->document_issued_at->format('Y-m-d'),
                'roles'              => ['rh-viewer'],
            ])
            ->assertForbidden();
    });

    it('puede eliminar un usuario', function (): void {
        [$admin, $institution] = crearContextoAdmin('admin');

        $objetivo = User::factory()->create(['institution_id' => $institution->id]);
        $objetivo->assignRole('rh-viewer');

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $objetivo))
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertSoftDeleted('users', ['id' => $objetivo->id]);
    });

    it('no puede eliminarse a sí mismo', function (): void {
        [$admin] = crearContextoAdmin('admin');

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $admin))
            ->assertForbidden();
    });

});
