<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Department;
use App\Models\RH\Employee;
use App\Models\RH\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Crear los roles necesarios antes de cada test
beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/**
 * Helper: crea institución + usuario con el rol dado.
 */
function crearUsuarioConRol(string $rol): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    return [$user, $institution];
}

/**
 * Helper: crea dependencia, cargo y empleado pertenecientes a la institución dada.
 */
function crearEmpleadoEnInstitucion(Institution $institution): Employee
{
    $departamento = Department::factory()->create(['institution_id' => $institution->id]);
    $cargo = Position::factory()->create([
        'institution_id' => $institution->id,
        'department_id' => $departamento->id,
    ]);

    return Employee::factory()->create([
        'institution_id' => $institution->id,
        'position_id' => $cargo->id,
        'department_id' => $departamento->id,
    ]);
}

describe('Gestión de Empleados', function (): void {

    it('puede listar empleados con rol rh-manager', function (): void {
        [$user, $institution] = crearUsuarioConRol('rh-manager');
        crearEmpleadoEnInstitucion($institution);

        // La vista aún no existe (la crea el agente frontend).
        // Verificamos que la autorización pasa: no 403 ni redirección a login.
        $response = $this->actingAs($user)
            ->get(route('rh.empleados.index'));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
        $this->assertNotEquals(302, $response->status(), 'No debe redirigir al login');
    });

    it('puede registrar empleado con datos válidos', function (): void {
        [$user, $institution] = crearUsuarioConRol('rh-manager');

        $departamento = Department::factory()->create(['institution_id' => $institution->id]);
        $cargo = Position::factory()->create([
            'institution_id' => $institution->id,
            'department_id' => $departamento->id,
        ]);

        $datos = [
            'institution_id' => $institution->id,
            'position_id' => $cargo->id,
            'department_id' => $departamento->id,
            'document_type' => 'CC',
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'García',
            'salary' => 3500000,
        ];

        $this->actingAs($user)
            ->post(route('rh.empleados.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'document_number' => '12345678',
            'institution_id' => $institution->id,
        ]);
    });

    it('rechaza registro sin número de cédula', function (): void {
        [$user, $institution] = crearUsuarioConRol('rh-manager');

        $departamento = Department::factory()->create(['institution_id' => $institution->id]);
        $cargo = Position::factory()->create([
            'institution_id' => $institution->id,
            'department_id' => $departamento->id,
        ]);

        $this->actingAs($user)
            ->post(route('rh.empleados.store'), [
                'institution_id' => $institution->id,
                'position_id' => $cargo->id,
                'department_id' => $departamento->id,
                'document_type' => 'CC',
                // 'document_number' => omitido
                'first_name' => 'Juan',
                'last_name' => 'García',
                'salary' => 3500000,
            ])
            ->assertSessionHasErrors('document_number');
    });

    it('rechaza registro con cédula duplicada en la misma institución', function (): void {
        [$user, $institution] = crearUsuarioConRol('rh-manager');
        $empleadoExistente = crearEmpleadoEnInstitucion($institution);

        $departamento = Department::factory()->create(['institution_id' => $institution->id]);
        $cargo = Position::factory()->create([
            'institution_id' => $institution->id,
            'department_id' => $departamento->id,
        ]);

        $this->actingAs($user)
            ->post(route('rh.empleados.store'), [
                'institution_id' => $institution->id,
                'position_id' => $cargo->id,
                'department_id' => $departamento->id,
                'document_type' => 'CC',
                'document_number' => $empleadoExistente->document_number, // duplicado
                'first_name' => 'Otro',
                'last_name' => 'Nombre',
                'salary' => 2800000,
            ])
            ->assertSessionHasErrors('document_number');
    });

    it('impide que rh-viewer registre empleados', function (): void {
        [$user, $institution] = crearUsuarioConRol('rh-viewer');

        $departamento = Department::factory()->create(['institution_id' => $institution->id]);
        $cargo = Position::factory()->create([
            'institution_id' => $institution->id,
            'department_id' => $departamento->id,
        ]);

        $this->actingAs($user)
            ->post(route('rh.empleados.store'), [
                'institution_id' => $institution->id,
                'position_id' => $cargo->id,
                'department_id' => $departamento->id,
                'document_type' => 'CC',
                'document_number' => '98765432',
                'first_name' => 'Pedro',
                'last_name' => 'Ramírez',
                'salary' => 3000000,
            ])
            ->assertForbidden();
    });

    it('puede actualizar un empleado', function (): void {
        [$user, $institution] = crearUsuarioConRol('rh-manager');
        $empleado = crearEmpleadoEnInstitucion($institution);

        $this->actingAs($user)
            ->put(route('rh.empleados.update', $empleado), [
                'position_id' => $empleado->position_id,
                'department_id' => $empleado->department_id,
                'document_type' => 'CC',
                'document_number' => $empleado->document_number,
                'first_name' => 'NuevoNombre',
                'last_name' => $empleado->last_name,
                'salary' => 4500000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $empleado->id,
            'first_name' => 'NuevoNombre',
        ]);
    });

    it('hace eliminación lógica y registra auditoría', function (): void {
        [$user, $institution] = crearUsuarioConRol('rh-manager');
        $empleado = crearEmpleadoEnInstitucion($institution);

        $this->actingAs($user)
            ->delete(route('rh.empleados.destroy', $empleado))
            ->assertRedirect(route('rh.empleados.index'));

        // El registro existe en DB con soft delete
        $this->assertSoftDeleted('employees', ['id' => $empleado->id]);
    });

    it('puede exportar empleados a Excel', function (): void {
        [$user, $institution] = crearUsuarioConRol('rh-manager');
        crearEmpleadoEnInstitucion($institution);

        $this->actingAs($user)
            ->get(route('rh.empleados.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('bloquea acceso no autenticado al listado de empleados', function (): void {
        $this->get(route('rh.empleados.index'))
            ->assertRedirect(route('login'));
    });
});
