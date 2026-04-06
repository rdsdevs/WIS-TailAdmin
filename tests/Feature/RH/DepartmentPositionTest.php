<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\DocumentType;
use App\Models\RH\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
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
        Position::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'name' => fn () => fake()->unique()->jobTitle(),
        ]);

        $this->get(route('rh.cargos.index'))
            ->assertOk()
            ->assertViewIs('pages.rh.cargos.index');
    });

    it('puede crear un cargo con datos válidos', function (): void {
        $datos = [
            'institution_id' => $this->institution->id,
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

        $datos = [
            'institution_id' => $this->institution->id,
            'name' => 'Cargo No Permitido',
            'is_active' => true,
        ];

        $this->actingAs($consultor)
            ->post(route('rh.cargos.store'), $datos)
            ->assertForbidden();
    });
});

// ─── Gestión de Colaboradores (contratistas) ──────────────────────────────────

describe('Gestión de Colaboradores Contratistas', function (): void {

    beforeEach(function (): void {
        $institution = Institution::factory()->create();
        $this->gerente = User::factory()->create(['institution_id' => $institution->id]);
        $this->gerente->assignRole('rh-manager');
        $this->institution = $institution;
        $this->actingAs($this->gerente);

        $this->documentType = DocumentType::factory()->create([
            'institution_id' => $institution->id,
            'code' => 'CC',
            'name' => 'Cédula de Ciudadanía',
        ]);

        $this->status = CollaboratorStatus::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Activo',
            'icon_class' => 'fa-regular fa-user-check',
        ]);
    });

    it('puede ver el listado de contratistas', function (): void {
        Collaborator::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
            'document_type_id' => $this->documentType->id,
            'status_id' => $this->status->id,
            'type' => 'Contratista',
        ]);

        $response = $this->get(route('rh.colaboradores.index'));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
        $this->assertNotEquals(302, $response->status(), 'No debe redirigir al login');
    });

    it('puede registrar un contratista con datos válidos', function (): void {
        $datos = [
            'institution_id' => $this->institution->id,
            'document_type_id' => $this->documentType->id,
            'document_number' => '123456789',
            'first_name' => 'Ana',
            'first_surname' => 'Martínez',
            'type' => 'Contratista',
            'status_id' => $this->status->id,
            'is_company' => false,
        ];

        $this->post(route('rh.colaboradores.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'document_number' => '123456789',
            'type' => 'Contratista',
        ]);
    });

    it('rechaza el registro de contratista sin número de documento', function (): void {
        $datos = [
            'institution_id' => $this->institution->id,
            'document_type_id' => $this->documentType->id,
            // 'document_number' => omitido intencionalmente
            'first_name' => 'Ana',
            'first_surname' => 'Martínez',
            'type' => 'Contratista',
            'status_id' => $this->status->id,
            'is_company' => false,
        ];

        $this->post(route('rh.colaboradores.store'), $datos)
            ->assertSessionHasErrors('document_number');
    });

    it('impide que un consultor registre contratistas', function (): void {
        $consultor = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $consultor->assignRole('rh-viewer');

        $datos = [
            'institution_id' => $this->institution->id,
            'document_type_id' => $this->documentType->id,
            'document_number' => '987654321',
            'first_name' => 'Luis',
            'first_surname' => 'Pérez',
            'type' => 'Contratista',
            'status_id' => $this->status->id,
            'is_company' => false,
        ];

        $this->actingAs($consultor)
            ->post(route('rh.colaboradores.store'), $datos)
            ->assertForbidden();
    });

    it('hace eliminación lógica del colaborador contratista', function (): void {
        $colaborador = Collaborator::factory()->create([
            'institution_id' => $this->institution->id,
            'document_type_id' => $this->documentType->id,
            'status_id' => $this->status->id,
            'type' => 'Contratista',
        ]);

        $this->delete(route('rh.colaboradores.destroy', $colaborador))
            ->assertRedirect();

        $this->assertSoftDeleted('collaborators', ['id' => $colaborador->id]);
    });
});
