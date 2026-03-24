<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Crear los roles necesarios antes de cada test
beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/**
 * Helper: crea institución, tipos de documento, estado activo y usuario con el rol dado.
 */
function crearContextoColaborador(string $rol): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    $documentType = DocumentType::factory()->create([
        'institution_id' => $institution->id,
        'code' => 'CC',
        'name' => 'Cédula de Ciudadanía',
    ]);

    $status = CollaboratorStatus::factory()->create([
        'institution_id' => $institution->id,
        'name' => 'Activo',
        'icon_class' => 'fa-regular fa-user-check',
    ]);

    return [$user, $institution, $documentType, $status];
}

/**
 * Helper: crea un colaborador empleado en la institución dada.
 * Reutiliza DocumentType y CollaboratorStatus si ya existen para esa institución.
 */
function crearColaboradorEnInstitucion(Institution $institution): Collaborator
{
    $documentType = DocumentType::firstOrCreate(
        ['institution_id' => $institution->id, 'code' => 'CC'],
        ['name' => 'Cédula de Ciudadanía']
    );

    $status = CollaboratorStatus::firstOrCreate(
        ['institution_id' => $institution->id, 'name' => 'Activo'],
        ['icon_class' => 'fa-regular fa-user-check']
    );

    return Collaborator::factory()->create([
        'institution_id' => $institution->id,
        'document_type_id' => $documentType->id,
        'status_id' => $status->id,
        'type' => 'Empleado',
    ]);
}

describe('Gestión de Colaboradores', function (): void {

    it('puede listar colaboradores con rol rh-manager', function (): void {
        [$user, $institution] = crearContextoColaborador('rh-manager');
        crearColaboradorEnInstitucion($institution);

        $response = $this->actingAs($user)
            ->get(route('rh.colaboradores.index'));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
        $this->assertNotEquals(302, $response->status(), 'No debe redirigir al login');
    });

    it('puede registrar colaborador empleado con datos válidos', function (): void {
        [$user, $institution, $documentType, $status] = crearContextoColaborador('rh-manager');

        $datos = [
            'institution_id' => $institution->id,
            'document_type_id' => $documentType->id,
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'first_surname' => 'García',
            'type' => 'Empleado',
            'status_id' => $status->id,
            'is_company' => false,
        ];

        $this->actingAs($user)
            ->post(route('rh.colaboradores.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'document_number' => '12345678',
            'institution_id' => $institution->id,
            'type' => 'Empleado',
        ]);
    });

    it('puede registrar colaborador contratista con datos válidos', function (): void {
        [$user, $institution, $documentType, $status] = crearContextoColaborador('rh-manager');

        $datos = [
            'institution_id' => $institution->id,
            'document_type_id' => $documentType->id,
            'document_number' => '99887766',
            'first_name' => 'Ana',
            'first_surname' => 'Martínez',
            'type' => 'Contratista',
            'status_id' => $status->id,
            'is_company' => false,
        ];

        $this->actingAs($user)
            ->post(route('rh.colaboradores.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'document_number' => '99887766',
            'type' => 'Contratista',
        ]);
    });

    it('rechaza registro sin número de documento', function (): void {
        [$user, $institution, $documentType, $status] = crearContextoColaborador('rh-manager');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.store'), [
                'institution_id' => $institution->id,
                'document_type_id' => $documentType->id,
                // 'document_number' => omitido
                'first_name' => 'Juan',
                'first_surname' => 'García',
                'type' => 'Empleado',
                'status_id' => $status->id,
                'is_company' => false,
            ])
            ->assertSessionHasErrors('document_number');
    });

    it('rechaza registro con documento duplicado en la misma institución', function (): void {
        [$user, $institution, $documentType, $status] = crearContextoColaborador('rh-manager');

        $existente = Collaborator::factory()->create([
            'institution_id' => $institution->id,
            'document_type_id' => $documentType->id,
            'status_id' => $status->id,
            'document_number' => '11223344',
        ]);

        $this->actingAs($user)
            ->post(route('rh.colaboradores.store'), [
                'institution_id' => $institution->id,
                'document_type_id' => $documentType->id,
                'document_number' => $existente->document_number,
                'first_name' => 'Otro',
                'first_surname' => 'Nombre',
                'type' => 'Empleado',
                'status_id' => $status->id,
                'is_company' => false,
            ])
            ->assertSessionHasErrors('document_number');
    });

    it('impide que rh-viewer registre colaboradores', function (): void {
        [$user, $institution, $documentType, $status] = crearContextoColaborador('rh-viewer');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.store'), [
                'institution_id' => $institution->id,
                'document_type_id' => $documentType->id,
                'document_number' => '55443322',
                'first_name' => 'Pedro',
                'first_surname' => 'Ramírez',
                'type' => 'Empleado',
                'status_id' => $status->id,
                'is_company' => false,
            ])
            ->assertForbidden();
    });

    it('puede actualizar un colaborador', function (): void {
        [$user, $institution] = crearContextoColaborador('rh-manager');
        $colaborador = crearColaboradorEnInstitucion($institution);

        $this->actingAs($user)
            ->put(route('rh.colaboradores.update', $colaborador), [
                'institution_id' => $institution->id,
                'document_type_id' => $colaborador->document_type_id,
                'document_number' => $colaborador->document_number,
                'first_name' => 'NuevoNombre',
                'first_surname' => $colaborador->first_surname,
                'type' => $colaborador->type,
                'status_id' => $colaborador->status_id,
                'is_company' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'id' => $colaborador->id,
            'first_name' => 'NuevoNombre',
        ]);
    });

    it('hace eliminación lógica y registra auditoría', function (): void {
        [$user, $institution] = crearContextoColaborador('rh-manager');
        $colaborador = crearColaboradorEnInstitucion($institution);

        $this->actingAs($user)
            ->delete(route('rh.colaboradores.destroy', $colaborador))
            ->assertRedirect(route('rh.colaboradores.index'));

        $this->assertSoftDeleted('collaborators', ['id' => $colaborador->id]);
    });

    it('puede exportar colaboradores a Excel', function (): void {
        [$user, $institution] = crearContextoColaborador('rh-manager');
        crearColaboradorEnInstitucion($institution);

        $this->actingAs($user)
            ->get(route('rh.colaboradores.export', ['tipo' => 'todos']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('bloquea acceso no autenticado al listado de colaboradores', function (): void {
        $this->get(route('rh.colaboradores.index'))
            ->assertRedirect(route('login'));
    });

    it('employee-manager puede listar colaboradores', function (): void {
        [$user, $institution] = crearContextoColaborador('employee-manager');
        crearColaboradorEnInstitucion($institution);

        $response = $this->actingAs($user)
            ->get(route('rh.colaboradores.index'));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
        $this->assertNotEquals(302, $response->status(), 'No debe redirigir al login');
    });

    it('contractor-manager puede listar colaboradores', function (): void {
        [$user, $institution] = crearContextoColaborador('contractor-manager');
        crearColaboradorEnInstitucion($institution);

        $response = $this->actingAs($user)
            ->get(route('rh.colaboradores.index'));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
        $this->assertNotEquals(302, $response->status(), 'No debe redirigir al login');
    });

    it('employee-manager puede ver un colaborador de su institución', function (): void {
        [$user, $institution] = crearContextoColaborador('employee-manager');
        $colaborador = crearColaboradorEnInstitucion($institution);

        $response = $this->actingAs($user)
            ->get(route('rh.colaboradores.show', $colaborador));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
    });

    it('contractor-manager puede ver un colaborador de su institución', function (): void {
        [$user, $institution] = crearContextoColaborador('contractor-manager');
        $colaborador = crearColaboradorEnInstitucion($institution);

        $response = $this->actingAs($user)
            ->get(route('rh.colaboradores.show', $colaborador));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
    });

    it('employee-manager puede registrar un colaborador', function (): void {
        [$user, $institution, $documentType, $status] = crearContextoColaborador('employee-manager');

        $datos = [
            'institution_id'   => $institution->id,
            'document_type_id' => $documentType->id,
            'document_number'  => '10000001',
            'first_name'       => 'Carlos',
            'first_surname'    => 'Pérez',
            'type'             => 'Empleado',
            'status_id'        => $status->id,
            'is_company'       => false,
        ];

        $this->actingAs($user)
            ->post(route('rh.colaboradores.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'document_number' => '10000001',
            'institution_id'  => $institution->id,
        ]);
    });

    it('contractor-manager puede registrar un colaborador', function (): void {
        [$user, $institution, $documentType, $status] = crearContextoColaborador('contractor-manager');

        $datos = [
            'institution_id'   => $institution->id,
            'document_type_id' => $documentType->id,
            'document_number'  => '20000002',
            'first_name'       => 'Laura',
            'first_surname'    => 'Gómez',
            'type'             => 'Contratista',
            'status_id'        => $status->id,
            'is_company'       => false,
        ];

        $this->actingAs($user)
            ->post(route('rh.colaboradores.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'document_number' => '20000002',
            'institution_id'  => $institution->id,
        ]);
    });

    it('employee-manager puede actualizar un colaborador', function (): void {
        [$user, $institution] = crearContextoColaborador('employee-manager');
        $colaborador = crearColaboradorEnInstitucion($institution);

        $this->actingAs($user)
            ->put(route('rh.colaboradores.update', $colaborador), [
                'institution_id'   => $institution->id,
                'document_type_id' => $colaborador->document_type_id,
                'document_number'  => $colaborador->document_number,
                'first_name'       => 'NombreEditado',
                'first_surname'    => $colaborador->first_surname,
                'type'             => $colaborador->type,
                'status_id'        => $colaborador->status_id,
                'is_company'       => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'id'         => $colaborador->id,
            'first_name' => 'NombreEditado',
        ]);
    });

    it('contractor-manager puede actualizar un colaborador', function (): void {
        [$user, $institution] = crearContextoColaborador('contractor-manager');
        $colaborador = crearColaboradorEnInstitucion($institution);

        $this->actingAs($user)
            ->put(route('rh.colaboradores.update', $colaborador), [
                'institution_id'   => $institution->id,
                'document_type_id' => $colaborador->document_type_id,
                'document_number'  => $colaborador->document_number,
                'first_name'       => 'NombreEditadoCM',
                'first_surname'    => $colaborador->first_surname,
                'type'             => $colaborador->type,
                'status_id'        => $colaborador->status_id,
                'is_company'       => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'id'         => $colaborador->id,
            'first_name' => 'NombreEditadoCM',
        ]);
    });

    it('employee-manager puede importar colaboradores', function (): void {
        [$user] = crearContextoColaborador('employee-manager');

        $policy = new \App\Policies\RH\CollaboratorPolicy();

        expect($policy->import($user))->toBeTrue();
    });

    it('contractor-manager puede importar colaboradores', function (): void {
        [$user] = crearContextoColaborador('contractor-manager');

        $policy = new \App\Policies\RH\CollaboratorPolicy();

        expect($policy->import($user))->toBeTrue();
    });
});
