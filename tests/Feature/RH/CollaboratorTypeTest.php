<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/**
 * Helper: crea institución, usuario con rol dado y los auxiliares necesarios.
 * Retorna [$user, $institution, $documentType, $status].
 */
function crearContextoTipo(string $rol): array
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
 * Helper: crea un colaborador del tipo indicado vinculado a la institución dada.
 * Reutiliza DocumentType y CollaboratorStatus existentes de la institución.
 */
function crearColaboradorDeTipo(Institution $institution, string $tipo): Collaborator
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
        'type' => $tipo,
    ]);
}

/**
 * Helper: crea un contrato vigente para el colaborador dado.
 */
function crearContratoVigente(Collaborator $colaborador): Contract
{
    $contractType = ContractType::factory()->create([
        'institution_id' => $colaborador->institution_id,
    ]);

    return Contract::factory()->create([
        'institution_id' => $colaborador->institution_id,
        'collaborator_id' => $colaborador->id,
        'contract_type_id' => $contractType->id,
        'status' => 'Vigente',
    ]);
}

// ─── Visibilidad por tipo ─────────────────────────────────────────────────────

describe('Visibilidad de colaboradores por rol especializado', function (): void {

    it('contractor-manager solo puede ver colaboradores contratistas de su institución', function (): void {
        [$user, $institution] = crearContextoTipo('contractor-manager');

        $contratista = crearColaboradorDeTipo($institution, 'Contratista');
        crearColaboradorDeTipo($institution, 'Empleado');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.show', $contratista))
            ->assertOk();
    });

    it('employee-manager solo puede ver colaboradores empleados de su institución', function (): void {
        [$user, $institution] = crearContextoTipo('employee-manager');

        $empleado = crearColaboradorDeTipo($institution, 'Empleado');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.show', $empleado))
            ->assertOk();
    });

    it('contractor-manager no puede ver colaboradores empleados', function (): void {
        [$user, $institution] = crearContextoTipo('contractor-manager');

        $empleado = crearColaboradorDeTipo($institution, 'Empleado');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.show', $empleado))
            ->assertForbidden();
    });

    it('employee-manager no puede ver colaboradores contratistas', function (): void {
        [$user, $institution] = crearContextoTipo('employee-manager');

        $contratista = crearColaboradorDeTipo($institution, 'Contratista');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.show', $contratista))
            ->assertForbidden();
    });

    it('contractor-manager no puede ver colaboradores de otra institución', function (): void {
        [$user] = crearContextoTipo('contractor-manager');

        // Colaborador contratista pero de institución diferente
        $otraInstitucion = Institution::factory()->create();
        $contratistaAjeno = crearColaboradorDeTipo($otraInstitucion, 'Contratista');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.show', $contratistaAjeno))
            ->assertForbidden();
    });

    it('employee-manager no puede ver colaboradores de otra institución', function (): void {
        [$user] = crearContextoTipo('employee-manager');

        $otraInstitucion = Institution::factory()->create();
        $empleadoAjeno = crearColaboradorDeTipo($otraInstitucion, 'Empleado');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.show', $empleadoAjeno))
            ->assertForbidden();
    });

});

// ─── Edición y eliminación por tipo ──────────────────────────────────────────

describe('Edición y eliminación restringida por tipo de colaborador', function (): void {

    it('contractor-manager no puede editar un colaborador empleado', function (): void {
        [$user, $institution] = crearContextoTipo('contractor-manager');
        $empleado = crearColaboradorDeTipo($institution, 'Empleado');

        $this->actingAs($user)
            ->put(route('rh.colaboradores.update', $empleado), [])
            ->assertForbidden();
    });

    it('employee-manager no puede editar un colaborador contratista', function (): void {
        [$user, $institution] = crearContextoTipo('employee-manager');
        $contratista = crearColaboradorDeTipo($institution, 'Contratista');

        $this->actingAs($user)
            ->put(route('rh.colaboradores.update', $contratista), [])
            ->assertForbidden();
    });

    it('contractor-manager no puede eliminar un colaborador empleado', function (): void {
        [$user, $institution] = crearContextoTipo('contractor-manager');
        $empleado = crearColaboradorDeTipo($institution, 'Empleado');

        $this->actingAs($user)
            ->delete(route('rh.colaboradores.destroy', $empleado))
            ->assertForbidden();

        $this->assertDatabaseHas('collaborators', ['id' => $empleado->id]);
    });

    it('employee-manager no puede eliminar un colaborador contratista', function (): void {
        [$user, $institution] = crearContextoTipo('employee-manager');
        $contratista = crearColaboradorDeTipo($institution, 'Contratista');

        $this->actingAs($user)
            ->delete(route('rh.colaboradores.destroy', $contratista))
            ->assertForbidden();

        $this->assertDatabaseHas('collaborators', ['id' => $contratista->id]);
    });

});

// ─── Cambio de tipo ───────────────────────────────────────────────────────────

describe('Cambio de tipo de colaborador', function (): void {

    it('rh-manager puede cambiar tipo de cualquier colaborador sin contratos vigentes', function (): void {
        [$user, $institution] = crearContextoTipo('rh-manager');

        $empleado = crearColaboradorDeTipo($institution, 'Empleado');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $empleado))
            ->assertRedirect(route('rh.colaboradores.show', $empleado));

        $this->assertDatabaseHas('collaborators', [
            'id' => $empleado->id,
            'type' => 'Contratista',
        ]);
    });

    it('contractor-manager puede cambiar tipo de contratista sin contratos vigentes', function (): void {
        [$user, $institution] = crearContextoTipo('contractor-manager');

        $contratista = crearColaboradorDeTipo($institution, 'Contratista');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $contratista))
            ->assertRedirect(route('rh.colaboradores.show', $contratista));

        $this->assertDatabaseHas('collaborators', [
            'id' => $contratista->id,
            'type' => 'Empleado',
        ]);
    });

    it('se puede cambiar tipo de colaborador sin contratos vigentes', function (): void {
        [$user, $institution] = crearContextoTipo('rh-manager');

        $colaborador = crearColaboradorDeTipo($institution, 'Empleado');

        // Crear contrato en estado distinto a Vigente para verificar que no bloquea
        $contractType = ContractType::factory()->create(['institution_id' => $institution->id]);
        Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $contractType->id,
            'status' => 'Liquidado',
        ]);

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $colaborador))
            ->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'id' => $colaborador->id,
            'type' => 'Contratista',
        ]);
    });

    it('no se puede cambiar tipo si tiene contratos vigentes', function (): void {
        [$user, $institution] = crearContextoTipo('rh-manager');

        $colaborador = crearColaboradorDeTipo($institution, 'Empleado');
        crearContratoVigente($colaborador);

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $colaborador))
            ->assertForbidden();

        // El tipo debe permanecer sin cambios
        $this->assertDatabaseHas('collaborators', [
            'id' => $colaborador->id,
            'type' => 'Empleado',
        ]);
    });

    it('contractor-manager no puede cambiar tipo cuando el contratista tiene contratos vigentes', function (): void {
        [$user, $institution] = crearContextoTipo('contractor-manager');

        $contratista = crearColaboradorDeTipo($institution, 'Contratista');
        crearContratoVigente($contratista);

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $contratista))
            ->assertForbidden();

        $this->assertDatabaseHas('collaborators', [
            'id' => $contratista->id,
            'type' => 'Contratista',
        ]);
    });

    it('contractor-manager no puede cambiar tipo de un empleado (tipo equivocado)', function (): void {
        [$user, $institution] = crearContextoTipo('contractor-manager');

        $empleado = crearColaboradorDeTipo($institution, 'Empleado');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $empleado))
            ->assertForbidden();
    });

    it('employee-manager no puede cambiar tipo de un contratista (tipo equivocado)', function (): void {
        [$user, $institution] = crearContextoTipo('employee-manager');

        $contratista = crearColaboradorDeTipo($institution, 'Contratista');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $contratista))
            ->assertForbidden();
    });

    it('rh-viewer no puede cambiar el tipo de ningún colaborador', function (): void {
        [$user, $institution] = crearContextoTipo('rh-viewer');

        $colaborador = crearColaboradorDeTipo($institution, 'Empleado');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $colaborador))
            ->assertForbidden();
    });

    it('usuario no autenticado no puede cambiar el tipo de un colaborador', function (): void {
        $institution = Institution::factory()->create();
        $colaborador = crearColaboradorDeTipo($institution, 'Empleado');

        $this->post(route('rh.colaboradores.change-type', $colaborador))
            ->assertRedirect(route('login'));
    });

    it('contractor-manager no puede cambiar tipo de colaborador de otra institución', function (): void {
        [$user] = crearContextoTipo('contractor-manager');

        $otraInstitucion = Institution::factory()->create();
        $contratistaAjeno = crearColaboradorDeTipo($otraInstitucion, 'Contratista');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.change-type', $contratistaAjeno))
            ->assertForbidden();
    });

});
