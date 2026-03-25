<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\User;
use App\Policies\RH\ContractPolicy;
use App\Services\RH\ContractProrogaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function contextoBase(): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole('rh-manager');

    $documentType = DocumentType::factory()->create([
        'institution_id' => $institution->id,
        'code'           => 'CC',
        'name'           => 'Cédula de Ciudadanía',
    ]);

    $status = CollaboratorStatus::factory()->create([
        'institution_id' => $institution->id,
        'name'           => 'Activo',
        'icon_class'     => 'fa-regular fa-user-check',
    ]);

    $colaborador = Collaborator::factory()->create([
        'institution_id'   => $institution->id,
        'document_type_id' => $documentType->id,
        'status_id'        => $status->id,
        'type'             => 'Empleado',
    ]);

    $tipoContrato = ContractType::factory()->create([
        'institution_id' => $institution->id,
        'code'           => 'TF',
        'name'           => 'Término Fijo',
    ]);

    return compact('institution', 'user', 'colaborador', 'tipoContrato');
}

function crearContratoHistorico(Institution $institution, Collaborator $colaborador, ContractType $tipoContrato): Contract
{
    $year = now()->year - 1;

    return Contract::factory()->create([
        'institution_id'   => $institution->id,
        'collaborator_id'  => $colaborador->id,
        'contract_type_id' => $tipoContrato->id,
        'start_date'       => Carbon::create($year, 2, 1),
        'end_date'         => Carbon::create($year, 10, 5),
        'status'           => 'Terminado',
        'salary'           => '2000000',
        'fees'             => '0',
    ]);
}

function crearContratoVigenteAnioActual(Institution $institution, Collaborator $colaborador, ContractType $tipoContrato): Contract
{
    return Contract::factory()->create([
        'institution_id'   => $institution->id,
        'collaborator_id'  => $colaborador->id,
        'contract_type_id' => $tipoContrato->id,
        'start_date'       => now()->startOfYear(),
        'end_date'         => now()->endOfYear(),
        'status'           => 'Vigente',
        'salary'           => '3000000',
        'fees'             => '0',
    ]);
}

// ── Tests: helpers del modelo Contract ───────────────────────────────────────

test('isFromPreviousYear es true para contrato de año anterior', function (): void {
    ['institution' => $i, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $contrato = crearContratoHistorico($i, $c, $t);

    expect($contrato->isFromPreviousYear())->toBeTrue();
    expect($contrato->isCurrentYear())->toBeFalse();
});

test('isCurrentYear es true para contrato del año actual', function (): void {
    ['institution' => $i, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $contrato = crearContratoVigenteAnioActual($i, $c, $t);

    expect($contrato->isCurrentYear())->toBeTrue();
    expect($contrato->isFromPreviousYear())->toBeFalse();
});

test('canBeProrrogated es true para contrato Vigente del año actual', function (): void {
    ['institution' => $i, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $contrato = crearContratoVigenteAnioActual($i, $c, $t);

    expect($contrato->canBeProrrogated())->toBeTrue();
});

test('canBeProrrogated es true para contrato Terminado del año actual', function (): void {
    ['institution' => $i, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();

    $contrato = Contract::factory()->create([
        'institution_id'   => $i->id,
        'collaborator_id'  => $c->id,
        'contract_type_id' => $t->id,
        'start_date'       => now()->startOfYear(),
        'end_date'         => now()->subDays(5),
        'status'           => 'Terminado',
    ]);

    expect($contrato->canBeProrrogated())->toBeTrue();
});

test('canBeProrrogated es false para contrato Terminado de año anterior', function (): void {
    ['institution' => $i, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $contrato = crearContratoHistorico($i, $c, $t);

    expect($contrato->canBeProrrogated())->toBeFalse();
});

// ── Tests: ContractPolicy ─────────────────────────────────────────────────────

test('policy applyProroga deniega para contrato histórico Terminado', function (): void {
    ['institution' => $i, 'user' => $user, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $contrato = crearContratoHistorico($i, $c, $t);

    $policy = new ContractPolicy();

    expect($policy->applyProroga($user, $contrato))->toBeFalse();
});

test('policy applyProroga permite para contrato Vigente del año actual', function (): void {
    ['institution' => $i, 'user' => $user, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $contrato = crearContratoVigenteAnioActual($i, $c, $t);

    $policy = new ContractPolicy();

    expect($policy->applyProroga($user, $contrato))->toBeTrue();
});

test('policy applyProrrogaAdvanced solo permite para contrato Terminado de año anterior', function (): void {
    ['institution' => $i, 'user' => $user, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $historico = crearContratoHistorico($i, $c, $t);
    $actual    = crearContratoVigenteAnioActual($i, $c, $t);

    $policy = new ContractPolicy();

    expect($policy->applyProrrogaAdvanced($user, $historico))->toBeTrue();
    expect($policy->applyProrrogaAdvanced($user, $actual))->toBeFalse();
});

// ── Tests: ContractProrogaService ────────────────────────────────────────────

test('getMaxExtensionDate retorna 31/12 del año del contrato para histórico', function (): void {
    ['institution' => $i, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $year    = now()->year - 1;
    $contrato = crearContratoHistorico($i, $c, $t);

    $service = app(ContractProrogaService::class);
    $maxDate = $service->getMaxExtensionDate($contrato);

    expect($maxDate)->not->toBeNull()
        ->and($maxDate->format('Y-m-d'))->toBe("{$year}-12-31");
});

test('getMaxExtensionDate retorna null para contrato del año actual', function (): void {
    ['institution' => $i, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $contrato = crearContratoVigenteAnioActual($i, $c, $t);

    $service = app(ContractProrogaService::class);

    expect($service->getMaxExtensionDate($contrato))->toBeNull();
});

test('prórroga que excede 31/12 en contrato histórico lanza InvalidArgumentException', function (): void {
    ['institution' => $i, 'user' => $user, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $year    = now()->year - 1;
    $contrato = crearContratoHistorico($i, $c, $t); // end_date: 05/10/año-anterior

    $this->actingAs($user);

    $data = [
        'extension_type'    => 'tiempo',
        'extension_months'  => 6,   // 05/10 + 6 meses = 05/04 del año actual, supera 31/12
        'extension_days'    => null,
        'extension_value'   => null,
        'committed_value_id' => null,
        'approval_date'     => "{$year}-10-05",
        'reason'            => null,
        'institution_id'    => $i->id,
    ];

    expect(fn () => app(ContractProrogaService::class)->apply($contrato, $data))
        ->toThrow(\InvalidArgumentException::class);
});

test('prórroga de contrato histórico dentro del límite 31/12 se aplica correctamente', function (): void {
    ['institution' => $i, 'user' => $user, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $year    = now()->year - 1;
    $contrato = crearContratoHistorico($i, $c, $t); // end_date: 05/10/año-anterior

    $this->actingAs($user);

    $data = [
        'extension_type'    => 'tiempo',
        'extension_months'  => 1,   // 05/10 + 1 mes = 05/11, dentro del límite
        'extension_days'    => null,
        'extension_value'   => null,
        'committed_value_id' => null,
        'approval_date'     => "{$year}-10-05",
        'reason'            => 'Prórroga dentro del año',
        'institution_id'    => $i->id,
    ];

    $extension = app(ContractProrogaService::class)->apply($contrato, $data);

    expect($extension)->not->toBeNull()
        ->and($contrato->fresh()->end_date->format('Y-m-d'))->toBe("{$year}-11-05");
});

test('prórroga de contrato histórico no envía notificación', function (): void {
    Notification::fake();

    ['institution' => $i, 'user' => $user, 'colaborador' => $c, 'tipoContrato' => $t] = contextoBase();
    $year    = now()->year - 1;
    $contrato = crearContratoHistorico($i, $c, $t);

    $this->actingAs($user);

    $data = [
        'extension_type'    => 'tiempo',
        'extension_months'  => 1,
        'extension_days'    => null,
        'extension_value'   => null,
        'committed_value_id' => null,
        'approval_date'     => "{$year}-10-05",
        'reason'            => null,
        'institution_id'    => $i->id,
    ];

    app(ContractProrogaService::class)->apply($contrato, $data);

    Notification::assertNothingSent();
});
