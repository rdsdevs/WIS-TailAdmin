<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Contractor;
use App\Models\RH\Department;
use App\Models\RH\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ─── Gestión de Departamentos ─────────────────────────────────────────────────

describe('Gestión de Departamentos', function (): void {

    beforeEach(function (): void {
        $institution = Institution::factory()->create();
        $this->gerente = User::factory()->create(['institution_id' => $institution->id]);
        $this->gerente->assignRole('rh-manager');
        $this->institution = $institution;
        $this->actingAs($this->gerente);
    });

    it('puede ver el listado de departamentos', function (): void {
        Department::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'name' => fn () => fake()->unique()->word().' '.fake()->word(),
        ]);

        $this->get(route('rh.departamentos.index'))
            ->assertOk()
            ->assertViewIs('pages.rh.departamentos.index');
    });

    it('puede crear un departamento con datos válidos', function (): void {
        $datos = [
            'institution_id' => $this->institution->id,
            'name' => 'Departamento de Prueba',
            'description' => 'Descripción de prueba',
            'is_active' => true,
        ];

        $this->post(route('rh.departamentos.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'name' => 'Departamento de Prueba',
        ]);
    });

    it('impide que un consultor cree departamentos', function (): void {
        $consultor = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $consultor->assignRole('rh-viewer');

        $datos = [
            'institution_id' => $this->institution->id,
            'name' => 'Departamento No Permitido',
            'is_active' => true,
        ];

        $this->actingAs($consultor)
            ->post(route('rh.departamentos.store'), $datos)
            ->assertForbidden();
    });

    it('puede eliminar un departamento', function (): void {
        $departamento = Department::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Departamento A Eliminar',
        ]);

        $this->delete(route('rh.departamentos.destroy', $departamento))
            ->assertRedirect();

        $this->assertSoftDeleted('departments', ['id' => $departamento->id]);
    });
});

// ─── Gestión de Cargos ────────────────────────────────────────────────────────

describe('Gestión de Cargos', function (): void {

    beforeEach(function (): void {
        $institution = Institution::factory()->create();
        $this->gerente = User::factory()->create(['institution_id' => $institution->id]);
        $this->gerente->assignRole('rh-manager');
        $this->institution = $institution;
        $this->actingAs($this->gerente);
    });

    it('puede ver el listado de cargos', function (): void {
        $departamento = Department::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Departamento de Cargos',
        ]);
        Position::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'department_id' => $departamento->id,
            'name' => fn () => fake()->unique()->jobTitle(),
        ]);

        $this->get(route('rh.cargos.index'))
            ->assertOk()
            ->assertViewIs('pages.rh.cargos.index');
    });

    it('puede crear un cargo con datos válidos', function (): void {
        $departamento = Department::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Departamento para Cargo',
        ]);

        $datos = [
            'institution_id' => $this->institution->id,
            'department_id' => $departamento->id,
            'name' => 'Analista de Sistemas',
            'is_active' => true,
        ];

        $this->post(route('rh.cargos.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('positions', [
            'name' => 'Analista de Sistemas',
        ]);
    });

    it('impide que un consultor cree cargos', function (): void {
        $consultor = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $consultor->assignRole('rh-viewer');

        $departamento = Department::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Departamento Consultor',
        ]);

        $datos = [
            'institution_id' => $this->institution->id,
            'department_id' => $departamento->id,
            'name' => 'Cargo No Permitido',
            'is_active' => true,
        ];

        $this->actingAs($consultor)
            ->post(route('rh.cargos.store'), $datos)
            ->assertForbidden();
    });
});

// ─── Gestión de Contratistas ──────────────────────────────────────────────────

describe('Gestión de Contratistas', function (): void {

    beforeEach(function (): void {
        $institution = Institution::factory()->create();
        $this->gerente = User::factory()->create(['institution_id' => $institution->id]);
        $this->gerente->assignRole('rh-manager');
        $this->institution = $institution;
        $this->actingAs($this->gerente);
    });

    it('puede ver el listado de contratistas', function (): void {
        Contractor::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->get(route('rh.contratistas.index'));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
        $this->assertNotEquals(302, $response->status(), 'No debe redirigir al login');
    });

    it('puede registrar un contratista con datos válidos', function (): void {
        $datos = [
            'institution_id' => $this->institution->id,
            'document_type' => 'CC',
            'document_number' => '123456789',
            'first_name' => 'Ana',
            'last_name' => 'Martínez',
            'is_active' => true,
        ];

        $this->post(route('rh.contratistas.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('contractors', [
            'document_number' => '123456789',
        ]);
    });

    it('rechaza el registro de contratista sin número de cédula', function (): void {
        $datos = [
            'institution_id' => $this->institution->id,
            'document_type' => 'CC',
            // 'document_number' => omitido intencionalmente
            'first_name' => 'Ana',
            'last_name' => 'Martínez',
            'is_active' => true,
        ];

        $this->post(route('rh.contratistas.store'), $datos)
            ->assertSessionHasErrors('document_number');
    });

    it('impide que un consultor registre contratistas', function (): void {
        $consultor = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $consultor->assignRole('rh-viewer');

        $datos = [
            'institution_id' => $this->institution->id,
            'document_type' => 'CC',
            'document_number' => '987654321',
            'first_name' => 'Luis',
            'last_name' => 'Pérez',
            'is_active' => true,
        ];

        $this->actingAs($consultor)
            ->post(route('rh.contratistas.store'), $datos)
            ->assertForbidden();
    });

    it('hace eliminación lógica del contratista', function (): void {
        $contratista = Contractor::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->delete(route('rh.contratistas.destroy', $contratista))
            ->assertRedirect();

        $this->assertSoftDeleted('contractors', ['id' => $contratista->id]);
    });
});
