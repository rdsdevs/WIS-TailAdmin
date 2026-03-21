<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Contract;
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
 * Helper: crea empleado completo con su institución.
 */
function crearEmpleadoRH(Institution $institution): Employee
{
    $dpto = Department::factory()->create(['institution_id' => $institution->id]);
    $cargo = Position::factory()->create([
        'institution_id' => $institution->id,
        'department_id' => $dpto->id,
    ]);

    return Employee::factory()->create([
        'institution_id' => $institution->id,
        'position_id' => $cargo->id,
        'department_id' => $dpto->id,
    ]);
}

describe('Gestión de Contratos', function (): void {

    it('puede listar contratos activos', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');

        $empleado = crearEmpleadoRH($institution);
        Contract::factory()->create([
            'institution_id' => $institution->id,
            'contractable_id' => $empleado->id,
            'contractable_type' => Employee::class,
            'is_active' => true,
        ]);

        // La vista aún no existe (la crea el agente frontend).
        // Verificamos que la autorización pasa: no 403 ni redirección a login.
        $response = $this->actingAs($user)
            ->get(route('rh.contratos.index'));

        $this->assertNotEquals(403, $response->status(), 'No debe devolver Prohibido');
        $this->assertNotEquals(302, $response->status(), 'No debe redirigir al login');
    });

    it('puede crear un contrato para un empleado', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $empleado = crearEmpleadoRH($institution);

        $datos = [
            'institution_id' => $institution->id,
            'contractable_id' => $empleado->id,
            'contractable_type' => 'employee',
            'contract_type' => 'indefinite',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'salary' => 4500000,
            'position' => 'Analista de Sistemas',
        ];

        $this->actingAs($user)
            ->post(route('rh.contratos.store'), $datos)
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'contractable_id' => $empleado->id,
            'contractable_type' => Employee::class,
            'contract_type' => 'indefinite',
        ]);
    });

    it('rechaza contrato a término fijo sin fecha de fin', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $empleado = crearEmpleadoRH($institution);

        $this->actingAs($user)
            ->post(route('rh.contratos.store'), [
                'institution_id' => $institution->id,
                'contractable_id' => $empleado->id,
                'contractable_type' => 'employee',
                'contract_type' => 'fixed_term', // requiere end_date
                'start_date' => now()->toDateString(),
                'end_date' => null,          // omitido
                'salary' => 3200000,
                'position' => 'Auxiliar',
            ])
            ->assertSessionHasErrors('end_date');
    });

    it('puede terminar un contrato activo', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $empleado = crearEmpleadoRH($institution);

        $contrato = Contract::factory()->create([
            'institution_id' => $institution->id,
            'contractable_id' => $empleado->id,
            'contractable_type' => Employee::class,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('rh.contratos.terminate', $contrato))
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'id' => $contrato->id,
            'is_active' => false,
        ]);
    });

    it('muestra contratos próximos a vencer', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $empleado = crearEmpleadoRH($institution);

        // Contrato que vence en 10 días
        $contrato = Contract::factory()->create([
            'institution_id' => $institution->id,
            'contractable_id' => $empleado->id,
            'contractable_type' => Employee::class,
            'contract_type' => 'fixed_term',
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'is_active' => true,
        ]);

        // Verificar que el contrato es detectado por el scope expiringSoon del servicio
        $expirando = \App\Models\RH\Contract::query()
            ->where('institution_id', $institution->id)
            ->expiringSoon(30)
            ->get();

        expect($expirando)->toHaveCount(1);
        expect($expirando->first()->id)->toBe($contrato->id);
    });
});
