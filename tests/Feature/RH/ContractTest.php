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

// Crear los roles necesarios antes de cada test
beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/**
 * Helper: crea institución + usuario con rol.
 */
function crearUsuarioRH(string $rol): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    return [$user, $institution];
}

/**
 * Helper: crea colaborador con toda su dependencia en la institución dada.
 */
function crearColaboradorRH(Institution $institution): Collaborator
{
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

    return Collaborator::factory()->create([
        'institution_id' => $institution->id,
        'document_type_id' => $documentType->id,
        'status_id' => $status->id,
        'type' => 'Empleado',
    ]);
}

/**
 * Helper: crea un tipo de contrato para la institución.
 */
function crearTipoContrato(Institution $institution): ContractType
{
    return ContractType::factory()->create([
        'institution_id' => $institution->id,
        'code' => 'IDFD',
        'name' => 'Indefinido',
    ]);
}

describe('Gestión de Contratos', function (): void {

    it('puede listar contratos activos', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');

        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'status' => 'Vigente',
        ]);

        $response = $this->actingAs($user)
            ->get(route('rh.contratos.index'));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
        $this->assertNotEquals(302, $response->status(), 'No debe redirigir al login');
    });

    it('puede crear un contrato para un colaborador', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        $datos = [
            'institution_id' => $institution->id,
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'salary' => 4500000,
            'fees' => 0,
            'status' => 'Vigente',
        ];

        $this->actingAs($user)
            ->post(route('rh.contratos.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'status' => 'Vigente',
        ]);
    });

    it('rechaza contrato a término fijo sin fecha de fin', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $colaborador = crearColaboradorRH($institution);

        $tipoFijo = ContractType::factory()->create([
            'institution_id' => $institution->id,
            'code' => 'FIAA',
            'name' => 'Fijo Inferior a un año',
        ]);

        $this->actingAs($user)
            ->post(route('rh.contratos.store'), [
                'institution_id' => $institution->id,
                'collaborator_id' => $colaborador->id,
                'contract_type_id' => $tipoFijo->id,
                'start_date' => now()->toDateString(),
                'end_date' => null,   // omitido para tipo fijo
                'salary' => 3200000,
                'status' => 'Vigente',
            ])
            ->assertSessionHasErrors('end_date');
    });

    it('puede terminar un contrato vigente', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        $contrato = Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'status' => 'Vigente',
        ]);

        $this->actingAs($user)
            ->patch(route('rh.contratos.terminate', $contrato))
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'id' => $contrato->id,
            'status' => 'Terminado',
        ]);
    });

    it('muestra contratos próximos a vencer', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        // Contrato que vence en 10 días
        $contrato = Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'Vigente',
        ]);

        $expirando = Contract::query()
            ->where('institution_id', $institution->id)
            ->expiringSoon(30)
            ->get();

        expect($expirando)->toHaveCount(1);
        expect($expirando->first()->id)->toBe($contrato->id);
    });
});
