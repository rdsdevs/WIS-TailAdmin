<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use App\Models\RH\ContractExtension;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\User;
use App\Services\RH\ContractProrogaService;
use App\Services\RH\ContractService;
use Carbon\Carbon;
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

/**
 * Helper: crea colaborador de tipo Contratista en la institución dada.
 */
function crearContratista(Institution $institution): Collaborator
{
    $documentType = DocumentType::firstOrCreate(
        ['institution_id' => $institution->id, 'code' => 'CC'],
        ['name' => 'Cédula de Ciudadanía'],
    );

    $status = CollaboratorStatus::firstOrCreate(
        ['institution_id' => $institution->id, 'name' => 'Activo'],
        ['icon_class' => 'fa-regular fa-user-check'],
    );

    return Collaborator::factory()->create([
        'institution_id' => $institution->id,
        'document_type_id' => $documentType->id,
        'status_id' => $status->id,
        'type' => 'Contratista',
    ]);
}

/**
 * Helper: contexto base para tests de prórroga.
 * Retorna [$user, $institution, $contrato] con un rh-manager y un contrato vigente
 * de tipo "honorarios" (fees > 0) iniciado en 2025 con end_date conocida.
 *
 * @param  string  $startDate  Fecha de inicio del contrato (Y-m-d)
 * @param  float   $fees       Honorarios del contrato
 * @param  float   $salary     Salario del contrato (cuando fees = 0)
 */
function contextoContratoProroga(string $startDate = '2025-01-15', float $fees = 5000000, float $salary = 0): array
{
    [$user, $institution] = crearUsuarioRH('rh-manager');

    $colaborador = crearColaboradorRH($institution);
    $tipoContrato = crearTipoContrato($institution);

    $contrato = Contract::factory()->create([
        'institution_id'   => $institution->id,
        'collaborator_id'  => $colaborador->id,
        'contract_type_id' => $tipoContrato->id,
        'start_date'       => $startDate,
        'end_date'         => Carbon::parse($startDate)->addMonths(6)->toDateString(),
        'fees'             => $fees,
        'salary'           => $salary,
        'status'           => 'Vigente',
    ]);

    return [$user, $institution, $contrato];
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

    it('crea un contrato con líneas de comprometido', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        $datos = [
            'institution_id' => $institution->id,
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'start_date' => now()->toDateString(),
            'fees' => 3000000,
            'status' => 'Vigente',
            'committed_values' => [
                ['accounting_account' => '2-1-1-01', 'cost_center' => 'CC-100', 'amount' => 1500000],
                ['accounting_account' => '2-1-1-02', 'cost_center' => 'CC-200', 'amount' => 1500000],
            ],
        ];

        $this->actingAs($user)
            ->post(route('rh.contratos.store'), $datos)
            ->assertRedirect();

        $contrato = Contract::query()
            ->where('collaborator_id', $colaborador->id)
            ->firstOrFail();

        expect($contrato->committedValues()->count())->toBe(2);
        $this->assertDatabaseHas('committed_values', [
            'contract_id' => $contrato->id,
            'accounting_account' => '2-1-1-01',
            'cost_center' => 'CC-100',
            'amount' => 1500000,
        ]);
    });

    it('rechaza líneas de comprometido con cuenta contable vacía', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        $this->actingAs($user)
            ->post(route('rh.contratos.store'), [
                'institution_id' => $institution->id,
                'collaborator_id' => $colaborador->id,
                'contract_type_id' => $tipoContrato->id,
                'start_date' => now()->toDateString(),
                'status' => 'Vigente',
                'committed_values' => [
                    ['accounting_account' => '', 'cost_center' => 'CC-100', 'amount' => 500000],
                ],
            ])
            ->assertSessionHasErrors('committed_values.0.accounting_account');
    });

    it('sincroniza (reemplaza) las líneas de comprometido de un contrato existente', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        $contrato = Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'status' => 'Vigente',
        ]);

        // Crear líneas iniciales
        $contrato->committedValues()->create([
            'institution_id' => $institution->id,
            'accounting_account' => '2-1-1-01',
            'cost_center' => 'CC-100',
            'amount' => 2000000,
        ]);

        // Sincronizar con nuevas líneas
        $service = app(\App\Services\RH\ContractService::class);
        $service->syncCommittedValues($contrato, [
            ['accounting_account' => '3-1-1-01', 'cost_center' => 'CC-300', 'amount' => 5000000],
        ]);

        expect($contrato->committedValues()->count())->toBe(1);
        $this->assertDatabaseHas('committed_values', [
            'contract_id' => $contrato->id,
            'accounting_account' => '3-1-1-01',
            'amount' => 5000000,
        ]);
        $this->assertDatabaseMissing('committed_values', [
            'contract_id' => $contrato->id,
            'accounting_account' => '2-1-1-01',
            'deleted_at' => null,
        ]);
    });

    it('syncCommittedValues con array vacío elimina todas las líneas', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');
        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        $contrato = Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'status' => 'Vigente',
        ]);

        $contrato->committedValues()->create([
            'institution_id' => $institution->id,
            'accounting_account' => '2-1-1-01',
            'cost_center' => 'CC-100',
            'amount' => 1000000,
        ]);

        $service = app(\App\Services\RH\ContractService::class);
        $service->syncCommittedValues($contrato, []);

        expect($contrato->committedValues()->count())->toBe(0);
    });
});

describe('Prórroga de contratos', function (): void {

    it('rh-manager puede aplicar prórroga de tiempo a contrato vigente', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga('2025-01-15');

        $fechaFinOriginal  = Carbon::parse($contrato->end_date);
        $fechaFinEsperada  = $fechaFinOriginal->copy()->addMonths(3)->toDateString();

        $servicio = app(ContractProrogaService::class);

        $this->actingAs($user);

        $servicio->apply($contrato, [
            'extension_type'     => 'tiempo',
            'extension_months'   => 3,
            'extension_days'     => 0,
            'extension_value'    => null,
            'committed_value_id' => null,
            'approval_date'      => today()->toDateString(),
            'reason'             => 'Extensión del plazo de entrega',
            'institution_id'     => $institution->id,
        ]);

        expect($contrato->fresh()->end_date->toDateString())->toBe($fechaFinEsperada);

        $this->assertDatabaseHas('contract_extensions', [
            'contract_id'    => $contrato->id,
            'extension_type' => 'tiempo',
        ]);
    });

    it('prórroga de valor incrementa honorarios del contrato', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga('2025-03-01', fees: 5000000);

        $this->actingAs($user);

        $servicio = app(ContractProrogaService::class);
        $servicio->apply($contrato, [
            'extension_type'     => 'valor',
            'extension_months'   => null,
            'extension_days'     => null,
            'extension_value'    => 2000000,
            'committed_value_id' => null,
            'approval_date'      => today()->toDateString(),
            'reason'             => null,
            'institution_id'     => $institution->id,
        ]);

        // fees debe ser 5000000 + 2000000 = 7000000
        expect((float) $contrato->fresh()->fees)->toBe(7000000.0);
    });

    it('prórroga de valor incrementa salario cuando el contrato no tiene honorarios', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga('2025-03-01', fees: 0, salary: 3000000);

        $this->actingAs($user);

        $servicio = app(ContractProrogaService::class);
        $servicio->apply($contrato, [
            'extension_type'     => 'valor',
            'extension_months'   => null,
            'extension_days'     => null,
            'extension_value'    => 1000000,
            'committed_value_id' => null,
            'approval_date'      => today()->toDateString(),
            'reason'             => null,
            'institution_id'     => $institution->id,
        ]);

        // salary debe ser 3000000 + 1000000 = 4000000
        expect((float) $contrato->fresh()->salary)->toBe(4000000.0);
    });

    it('prórroga de tiempo_y_valor actualiza fecha y monto simultáneamente', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga('2025-02-01', fees: 5000000);

        $fechaFinOriginal = Carbon::parse($contrato->end_date);
        $fechaFinEsperada = $fechaFinOriginal->copy()->addMonths(2)->toDateString();

        $this->actingAs($user);

        $servicio = app(ContractProrogaService::class);
        $servicio->apply($contrato, [
            'extension_type'     => 'tiempo_y_valor',
            'extension_months'   => 2,
            'extension_days'     => null,
            'extension_value'    => 1000000,
            'committed_value_id' => null,
            'approval_date'      => today()->toDateString(),
            'reason'             => 'Ampliación por nuevas actividades',
            'institution_id'     => $institution->id,
        ]);

        $contratoActualizado = $contrato->fresh();

        expect($contratoActualizado->end_date->toDateString())->toBe($fechaFinEsperada);
        expect((float) $contratoActualizado->fees)->toBe(6000000.0);
    });

    it('prórroga en contrato de 2025 con una línea comprometida actualiza su monto automáticamente', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga('2025-01-01', fees: 5000000);

        $cv = CommittedValue::factory()->create([
            'contract_id'      => $contrato->id,
            'institution_id'   => $institution->id,
            'accounting_account' => '2-1-1-01',
            'cost_center'      => 'CC-100',
            'amount'           => 5000000,
        ]);

        $this->actingAs($user);

        $servicio = app(ContractProrogaService::class);
        $servicio->apply($contrato, [
            'extension_type'     => 'valor',
            'extension_months'   => null,
            'extension_days'     => null,
            'extension_value'    => 1000000,
            'committed_value_id' => null,
            'approval_date'      => today()->toDateString(),
            'reason'             => null,
            'institution_id'     => $institution->id,
        ]);

        // El único CommittedValue debe haber sido incrementado a 6000000
        expect((float) $cv->fresh()->amount)->toBe(6000000.0);
    });

    it('prórroga en contrato de 2025 con múltiples líneas requiere selección explícita de centro de costos', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga('2025-01-01', fees: 5000000);

        $cv1 = CommittedValue::factory()->create([
            'contract_id'        => $contrato->id,
            'institution_id'     => $institution->id,
            'accounting_account' => '2-1-1-01',
            'cost_center'        => 'CC-100',
            'amount'             => 2500000,
        ]);

        $cv2 = CommittedValue::factory()->create([
            'contract_id'        => $contrato->id,
            'institution_id'     => $institution->id,
            'accounting_account' => '2-1-1-02',
            'cost_center'        => 'CC-200',
            'amount'             => 2500000,
        ]);

        $servicio = app(ContractProrogaService::class);

        // Con dos líneas, el servicio debe indicar que se requiere selección
        expect($servicio->needsCostCenterSelection($contrato))->toBeTrue();

        $this->actingAs($user);

        // Aplicar prórroga apuntando solo a cv2
        $servicio->apply($contrato, [
            'extension_type'     => 'valor',
            'extension_months'   => null,
            'extension_days'     => null,
            'extension_value'    => 1000000,
            'committed_value_id' => $cv2->id,
            'approval_date'      => today()->toDateString(),
            'reason'             => null,
            'institution_id'     => $institution->id,
        ]);

        // Solo cv2 debe incrementarse; cv1 no cambia
        expect((float) $cv1->fresh()->amount)->toBe(2500000.0);
        expect((float) $cv2->fresh()->amount)->toBe(3500000.0);
    });

    it('prórroga en contrato anterior a 2025 no actualiza committed values aunque tenga líneas', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga('2024-01-01', fees: 5000000);

        $cv = CommittedValue::factory()->create([
            'contract_id'        => $contrato->id,
            'institution_id'     => $institution->id,
            'accounting_account' => '2-1-1-01',
            'cost_center'        => 'CC-100',
            'amount'             => 3000000,
        ]);

        $this->actingAs($user);

        $servicio = app(ContractProrogaService::class);
        $servicio->apply($contrato, [
            'extension_type'     => 'valor',
            'extension_months'   => null,
            'extension_days'     => null,
            'extension_value'    => 1000000,
            'committed_value_id' => null,
            'approval_date'      => today()->toDateString(),
            'reason'             => null,
            'institution_id'     => $institution->id,
        ]);

        // El monto del CommittedValue no debe cambiar (contrato pre-2025)
        expect((float) $cv->fresh()->amount)->toBe(3000000.0);
    });

    it('contractor-manager puede prorrogar contratos de contratistas', function (): void {
        [$user, $institution] = crearUsuarioRH('contractor-manager');

        $contratista = crearContratista($institution);
        $tipoContrato = crearTipoContrato($institution);

        $contrato = Contract::factory()->create([
            'institution_id'   => $institution->id,
            'collaborator_id'  => $contratista->id,
            'contract_type_id' => $tipoContrato->id,
            'status'           => 'Vigente',
        ]);

        $contrato->load('collaborator');

        expect($user->can('applyProroga', $contrato))->toBeTrue();
    });

    it('contractor-manager no puede prorrogar contratos de empleados', function (): void {
        [$user, $institution] = crearUsuarioRH('contractor-manager');

        $empleado = crearColaboradorRH($institution); // tipo Empleado
        $tipoContrato = crearTipoContrato($institution);

        $contrato = Contract::factory()->create([
            'institution_id'   => $institution->id,
            'collaborator_id'  => $empleado->id,
            'contract_type_id' => $tipoContrato->id,
            'status'           => 'Vigente',
        ]);

        $contrato->load('collaborator');

        expect($user->can('applyProroga', $contrato))->toBeFalse();
    });

    it('rh-viewer no puede aplicar prórroga', function (): void {
        [$viewer, $institution] = crearUsuarioRH('rh-viewer');

        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        $contrato = Contract::factory()->create([
            'institution_id'   => $institution->id,
            'collaborator_id'  => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'status'           => 'Vigente',
        ]);

        expect($viewer->can('applyProroga', $contrato))->toBeFalse();
    });

    it('un contrato puede acumular múltiples prórrogas históricas', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga('2025-01-01');

        $this->actingAs($user);

        $servicio = app(ContractProrogaService::class);

        $datosBase = [
            'extension_type'     => 'tiempo',
            'extension_months'   => 1,
            'extension_days'     => null,
            'extension_value'    => null,
            'committed_value_id' => null,
            'approval_date'      => today()->toDateString(),
            'reason'             => 'Prórroga histórica',
            'institution_id'     => $institution->id,
        ];

        $servicio->apply($contrato, $datosBase);
        $servicio->apply($contrato->fresh(), $datosBase);
        $servicio->apply($contrato->fresh(), $datosBase);

        expect($contrato->extensions()->count())->toBe(3);
    });

    it('la prórroga de tiempo_y_valor sin committed_value_id con múltiples líneas aplica al primer CommittedValue', function (): void {
        // Comportamiento documentado: cuando needsCostCenterSelection() es true
        // y no se pasa committed_value_id, el servicio aplica al primer CV de la colección.
        [$user, $institution, $contrato] = contextoContratoProroga('2025-01-01', fees: 6000000);

        $cv1 = CommittedValue::factory()->create([
            'contract_id'        => $contrato->id,
            'institution_id'     => $institution->id,
            'accounting_account' => '2-1-1-01',
            'cost_center'        => 'CC-100',
            'amount'             => 3000000,
        ]);

        $cv2 = CommittedValue::factory()->create([
            'contract_id'        => $contrato->id,
            'institution_id'     => $institution->id,
            'accounting_account' => '2-1-1-02',
            'cost_center'        => 'CC-200',
            'amount'             => 3000000,
        ]);

        $servicio = app(ContractProrogaService::class);

        // Confirmar que el servicio detecta múltiples líneas
        expect($servicio->needsCostCenterSelection($contrato))->toBeTrue();

        $this->actingAs($user);

        // Aplicar sin seleccionar CV explícito: el servicio usará ->first()
        $servicio->apply($contrato, [
            'extension_type'     => 'tiempo_y_valor',
            'extension_months'   => 1,
            'extension_days'     => null,
            'extension_value'    => 1000000,
            'committed_value_id' => null, // sin selección explícita
            'approval_date'      => today()->toDateString(),
            'reason'             => null,
            'institution_id'     => $institution->id,
        ]);

        // cv1 fue incrementado (es el primero de la colección)
        expect((float) $cv1->fresh()->amount)->toBe(4000000.0);
        // cv2 no cambia
        expect((float) $cv2->fresh()->amount)->toBe(3000000.0);
    });
});

describe('Terminación anticipada de contratos', function (): void {

    it('rh-manager puede terminar anticipadamente un contrato vigente', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga();

        $this->actingAs($user);

        $servicio = app(ContractService::class);
        $servicio->earlyTerminate($contrato, [
            'early_termination_date'   => today()->toDateString(),
            'early_termination_reason' => 'Mutuo acuerdo entre las partes',
        ]);

        $contratoActualizado = $contrato->fresh();

        expect($contratoActualizado->status)->toBe('Terminado');
        expect($contratoActualizado->early_termination_date->toDateString())->toBe(today()->toDateString());
        expect($contratoActualizado->early_termination_reason)->toBe('Mutuo acuerdo entre las partes');
    });

    it('la terminación anticipada NO modifica la end_date original del contrato', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');

        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        // CRÍTICO: end_date es la fecha pactada en contrato — no debe cambiar jamás
        $contrato = Contract::factory()->create([
            'institution_id'   => $institution->id,
            'collaborator_id'  => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'start_date'       => '2025-01-01',
            'end_date'         => '2025-12-31',
            'status'           => 'Vigente',
        ]);

        $this->actingAs($user);

        $servicio = app(ContractService::class);
        $servicio->earlyTerminate($contrato, [
            'early_termination_date'   => today()->toDateString(),
            'early_termination_reason' => 'Renuncia del contratista',
        ]);

        expect($contrato->fresh()->end_date->format('Y-m-d'))->toBe('2025-12-31');
    });

    it('la terminación anticipada registra el usuario que realizó la acción', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga();

        $this->actingAs($user);

        $servicio = app(ContractService::class);
        $servicio->earlyTerminate($contrato, [
            'early_termination_date'   => today()->toDateString(),
            'early_termination_reason' => 'Incumplimiento de obligaciones',
        ]);

        $contratoActualizado = $contrato->fresh();

        expect($contratoActualizado->early_terminated_by)->toBe($user->id);
        expect($contratoActualizado->early_terminated_at)->not->toBeNull();
    });

    it('isEarlyTerminated retorna true después de una terminación anticipada', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga();

        $this->actingAs($user);

        $servicio = app(ContractService::class);
        $servicio->earlyTerminate($contrato, [
            'early_termination_date'   => today()->toDateString(),
            'early_termination_reason' => 'Decisión institucional',
        ]);

        expect($contrato->fresh()->isEarlyTerminated())->toBeTrue();
    });

    it('isEarlyTerminated retorna false en contratos no terminados anticipadamente', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga();

        // Contrato vigente sin ninguna terminación anticipada
        expect($contrato->isEarlyTerminated())->toBeFalse();
    });

    it('rh-manager tiene permiso de terminación anticipada en contratos de su institución', function (): void {
        [$user, $institution, $contrato] = contextoContratoProroga();

        expect($user->can('earlyTerminate', $contrato))->toBeTrue();
    });

    it('rh-viewer NO tiene permiso de terminación anticipada', function (): void {
        [$viewer, $institution] = crearUsuarioRH('rh-viewer');

        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        $contrato = Contract::factory()->create([
            'institution_id'   => $institution->id,
            'collaborator_id'  => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'status'           => 'Vigente',
        ]);

        expect($viewer->can('earlyTerminate', $contrato))->toBeFalse();
    });

    it('contractor-manager NO puede terminar anticipadamente', function (): void {
        [$user, $institution] = crearUsuarioRH('contractor-manager');

        $contratista = crearContratista($institution);
        $tipoContrato = crearTipoContrato($institution);

        $contrato = Contract::factory()->create([
            'institution_id'   => $institution->id,
            'collaborator_id'  => $contratista->id,
            'contract_type_id' => $tipoContrato->id,
            'status'           => 'Vigente',
        ]);

        expect($user->can('earlyTerminate', $contrato))->toBeFalse();
    });

    it('no se puede acceder a contrato de otra institución en terminación anticipada', function (): void {
        [$userA, $institucionA] = crearUsuarioRH('rh-manager');
        [$userB, $institucionB] = crearUsuarioRH('rh-manager');

        $colaboradorB = crearColaboradorRH($institucionB);
        $tipoContratoB = crearTipoContrato($institucionB);

        $contratoB = Contract::factory()->create([
            'institution_id'   => $institucionB->id,
            'collaborator_id'  => $colaboradorB->id,
            'contract_type_id' => $tipoContratoB->id,
            'status'           => 'Vigente',
        ]);

        // userA es de institucionA, no puede terminar contratos de institucionB
        expect($userA->can('earlyTerminate', $contratoB))->toBeFalse();
    });

    it('scope scopeEarlyTerminated filtra solo contratos terminados anticipadamente', function (): void {
        [$user, $institution] = crearUsuarioRH('rh-manager');

        $colaborador = crearColaboradorRH($institution);
        $tipoContrato = crearTipoContrato($institution);

        // Contrato con terminación anticipada
        Contract::factory()->create([
            'institution_id'         => $institution->id,
            'collaborator_id'        => $colaborador->id,
            'contract_type_id'       => $tipoContrato->id,
            'status'                 => 'Terminado',
            'early_termination_date' => today()->toDateString(),
        ]);

        // Dos contratos sin terminación anticipada
        Contract::factory()->count(2)->create([
            'institution_id'   => $institution->id,
            'collaborator_id'  => $colaborador->id,
            'contract_type_id' => $tipoContrato->id,
            'status'           => 'Vigente',
        ]);

        $terminadosAnticipadamente = Contract::query()
            ->where('institution_id', $institution->id)
            ->earlyTerminated()
            ->get();

        expect($terminadosAnticipadamente)->toHaveCount(1);
    });
});
