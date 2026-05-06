<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\RH\Position;
use App\Models\RH\PositionChangeHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function pcSetup(string $rol = 'rh-manager', string $statusContrato = 'Vigente', string $tipoColaborador = 'Empleado'): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    $documentType = DocumentType::factory()->create(['institution_id' => $institution->id]);
    $status = CollaboratorStatus::factory()->create(['institution_id' => $institution->id]);

    $colaborador = Collaborator::factory()->create([
        'institution_id' => $institution->id,
        'document_type_id' => $documentType->id,
        'status_id' => $status->id,
        'type' => $tipoColaborador,
    ]);

    $tipo = ContractType::factory()->create([
        'institution_id' => $institution->id,
        'code' => 'CT-'.Str::random(6),
    ]);

    $cargoActual = Position::factory()->create([
        'institution_id' => $institution->id,
        'name' => 'Analista de Nómina '.Str::random(4),
    ]);

    $contrato = Contract::factory()->create([
        'institution_id' => $institution->id,
        'collaborator_id' => $colaborador->id,
        'contract_type_id' => $tipo->id,
        'position_id' => $cargoActual->id,
        'salary' => 3_000_000,
        'fees' => 0,
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'status' => $statusContrato,
    ]);

    return [$user, $institution, $contrato, $cargoActual];
}

function pcNewPosition(string $institutionId, string $name = 'Coordinador'): Position
{
    return Position::factory()->create([
        'institution_id' => $institutionId,
        'name' => $name.' '.Str::random(4),
    ]);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('PositionChangeService — apply()', function (): void {

    it('crea histórico y actualiza el contrato sin tocar salario cuando no se ajusta compensación', function (): void {
        [$user, $institution, $contrato, $cargoActual] = pcSetup();
        $cargoNuevo = pcNewPosition($institution->id);

        $service = app(\App\Services\RH\PositionChangeService::class);
        $service->apply($contrato, [
            'new_position_id' => $cargoNuevo->id,
            'change_date' => now()->format('Y-m-d'),
            'adjust_compensation' => false,
            'new_amount' => null,
            'observations' => 'Ascenso',
        ]);

        $contrato->refresh();
        expect($contrato->position_id)->toBe($cargoNuevo->id);
        expect((float) $contrato->salary)->toBe(3_000_000.0);

        $hist = PositionChangeHistory::query()->where('contract_id', $contrato->id)->first();
        expect($hist)->not->toBeNull();
        expect($hist->previous_position_id)->toBe($cargoActual->id);
        expect($hist->new_position_id)->toBe($cargoNuevo->id);
        expect((float) $hist->old_salary)->toBe(3_000_000.0);
        expect((float) $hist->new_salary)->toBe(3_000_000.0);
    });

    it('actualiza el salario cuando se ajusta la compensación', function (): void {
        [$user, $institution, $contrato] = pcSetup();
        $cargoNuevo = pcNewPosition($institution->id);

        app(\App\Services\RH\PositionChangeService::class)->apply($contrato, [
            'new_position_id' => $cargoNuevo->id,
            'change_date' => now()->format('Y-m-d'),
            'adjust_compensation' => true,
            'new_amount' => 4_500_000,
            'observations' => null,
        ]);

        $contrato->refresh();
        expect((float) $contrato->salary)->toBe(4_500_000.0);

        $hist = PositionChangeHistory::query()->where('contract_id', $contrato->id)->first();
        expect((float) $hist->new_salary)->toBe(4_500_000.0);
    });

    it('actualiza honorarios en lugar de salario cuando el contrato es por honorarios', function (): void {
        [$user, $institution] = pcSetup();
        $documentType = DocumentType::factory()->create(['institution_id' => $institution->id]);
        $status = CollaboratorStatus::factory()->create(['institution_id' => $institution->id]);
        $contractor = Collaborator::factory()->create([
            'institution_id' => $institution->id,
            'document_type_id' => $documentType->id,
            'status_id' => $status->id,
        ]);
        $tipo = ContractType::factory()->create([
            'institution_id' => $institution->id,
            'code' => 'CT-'.Str::random(6),
        ]);
        $cargoActual = pcNewPosition($institution->id, 'Asesor');
        $contrato = Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $contractor->id,
            'contract_type_id' => $tipo->id,
            'position_id' => $cargoActual->id,
            'salary' => 0,
            'fees' => 5_000_000,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => 'Vigente',
        ]);

        $cargoNuevo = pcNewPosition($institution->id, 'Consultor');

        app(\App\Services\RH\PositionChangeService::class)->apply($contrato, [
            'new_position_id' => $cargoNuevo->id,
            'change_date' => now()->format('Y-m-d'),
            'adjust_compensation' => true,
            'new_amount' => 7_000_000,
            'observations' => null,
        ]);

        $contrato->refresh();
        expect((float) $contrato->fees)->toBe(7_000_000.0);
        expect((float) $contrato->salary)->toBe(0.0);
    });
});

describe('ContractObserver', function (): void {

    it('NO crea histórico automáticamente al actualizar position_id directamente', function (): void {
        [$user, $institution, $contrato] = pcSetup();
        $cargoNuevo = pcNewPosition($institution->id);

        // Update directo sin pasar por el Service
        $contrato->update(['position_id' => $cargoNuevo->id, 'salary' => 4_000_000]);

        expect(PositionChangeHistory::query()->where('contract_id', $contrato->id)->count())->toBe(0);
    });
});

describe('Modal Livewire — open + autorización', function (): void {

    it('rh-manager puede abrir el modal', function (): void {
        [$user, , $contrato] = pcSetup('rh-manager');

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->assertSet('open', true);
    });

    it('rh-viewer NO puede abrir el modal', function (): void {
        [$user, , $contrato] = pcSetup('rh-viewer');

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->assertForbidden();
    });

    it('rechaza apertura cuando el contrato no es Vigente', function (): void {
        [$user, , $contrato] = pcSetup('rh-manager', statusContrato: 'Terminado');

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->assertForbidden();
    });
});

describe('Modal Livewire — flujo de guardado', function (): void {

    it('aplica cambio de cargo seleccionando nueva posición y guardando', function (): void {
        [$user, $institution, $contrato, $cargoActual] = pcSetup('rh-manager');
        $cargoNuevo = pcNewPosition($institution->id);

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->call('selectPosition', $cargoNuevo->id)
            ->set('changeDate', now()->format('Y-m-d'))
            ->set('observations', 'Ascenso')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('position-change-applied');

        expect(PositionChangeHistory::query()->where('contract_id', $contrato->id)->count())->toBe(1);
        $contrato->refresh();
        expect($contrato->position_id)->toBe($cargoNuevo->id);
    });

    it('valida que el cargo nuevo sea distinto al actual', function (): void {
        [$user, , $contrato, $cargoActual] = pcSetup('rh-manager');

        // Intento de seleccionar el mismo cargo: el SFC excluye al cargo actual de las sugerencias,
        // pero validamos que selectPosition rechaza el id actual.
        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->call('selectPosition', $cargoActual->id)
            ->call('save')
            ->assertHasErrors(['selectedPosition']);
    });

    it('valida fechas fuera del rango permitido', function (): void {
        [$user, $institution, $contrato] = pcSetup('rh-manager');
        $cargoNuevo = pcNewPosition($institution->id);

        // Fecha anterior al inicio del contrato
        $fechaInvalida = $contrato->start_date->copy()->subYear()->format('Y-m-d');

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->call('selectPosition', $cargoNuevo->id)
            ->set('changeDate', $fechaInvalida)
            ->call('save')
            ->assertHasErrors(['changeDate']);
    });

    it('exige nuevo monto cuando se activa ajuste de compensación', function (): void {
        [$user, $institution, $contrato] = pcSetup('rh-manager');
        $cargoNuevo = pcNewPosition($institution->id);

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->call('selectPosition', $cargoNuevo->id)
            ->set('adjustCompensation', true)
            ->set('newAmount', '')
            ->call('save')
            ->assertHasErrors();
    });

    it('aplica ajuste de salario cuando adjustCompensation y newAmount están activos', function (): void {
        [$user, $institution, $contrato] = pcSetup('rh-manager');
        $cargoNuevo = pcNewPosition($institution->id);

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->call('selectPosition', $cargoNuevo->id)
            ->set('adjustCompensation', true)
            ->set('newAmount', '4500000')
            ->call('save')
            ->assertHasNoErrors();

        $contrato->refresh();
        expect((float) $contrato->salary)->toBe(4_500_000.0);
    });

    it('ignora silenciosamente intento de seleccionar cargo de otra institución', function (): void {
        [$user, $institution, $contrato] = pcSetup('rh-manager');
        $otraInstitucion = Institution::factory()->create();
        $cargoOtraInst = pcNewPosition($otraInstitucion->id, 'Foráneo');

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->call('selectPosition', $cargoOtraInst->id)
            ->assertSet('selectedPosition', null)
            ->call('save')
            ->assertHasErrors(['selectedPosition']);
    });
});

describe('Robustez del Service y Policy', function (): void {

    it('hace rollback transaccional si falla la creación del histórico', function (): void {
        [$user, $institution, $contrato, $cargoActual] = pcSetup('rh-manager');

        // Forzamos fallo: pasamos new_position_id inválido (UUID inexistente).
        // Como FK con cascadeOnDelete, MySQL lanza FK constraint error.
        try {
            app(\App\Services\RH\PositionChangeService::class)->apply($contrato, [
                'new_position_id' => '00000000-0000-0000-0000-000000000000',
                'change_date' => now()->format('Y-m-d'),
                'adjust_compensation' => true,
                'new_amount' => 9_999_999,
                'observations' => null,
            ]);
            $this->fail('Se esperaba excepción por FK inválida.');
        } catch (\Throwable $e) {
            // Se esperaba.
        }

        $contrato->refresh();
        // El position_id NO se actualiza
        expect($contrato->position_id)->toBe($cargoActual->id);
        // El salary NO se actualiza
        expect((float) $contrato->salary)->toBe(3_000_000.0);
        // No hay histórico
        expect(PositionChangeHistory::query()->where('contract_id', $contrato->id)->count())->toBe(0);
    });

    it('prioriza honorarios sobre salario cuando ambos son > 0', function (): void {
        [$user, $institution] = pcSetup();
        $documentType = DocumentType::factory()->create(['institution_id' => $institution->id]);
        $status = CollaboratorStatus::factory()->create(['institution_id' => $institution->id]);
        $col = Collaborator::factory()->create([
            'institution_id' => $institution->id,
            'document_type_id' => $documentType->id,
            'status_id' => $status->id,
        ]);
        $tipo = ContractType::factory()->create([
            'institution_id' => $institution->id,
            'code' => 'CT-'.Str::random(6),
        ]);
        $cargoActual = pcNewPosition($institution->id, 'Mixto');
        $contrato = Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $col->id,
            'contract_type_id' => $tipo->id,
            'position_id' => $cargoActual->id,
            'salary' => 1_000_000,
            'fees' => 2_000_000,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => 'Vigente',
        ]);

        $cargoNuevo = pcNewPosition($institution->id, 'NuevoMixto');

        app(\App\Services\RH\PositionChangeService::class)->apply($contrato, [
            'new_position_id' => $cargoNuevo->id,
            'change_date' => now()->format('Y-m-d'),
            'adjust_compensation' => true,
            'new_amount' => 5_000_000,
            'observations' => null,
        ]);

        $contrato->refresh();
        // Por prioridad de fees > 0, se actualiza fees y salary queda igual
        expect((float) $contrato->fees)->toBe(5_000_000.0);
        expect((float) $contrato->salary)->toBe(1_000_000.0);
    });

    it('policy applyPositionChange rechaza contratos de colaboradores tipo Contratista', function (): void {
        [$user, , $contratoContratista] = pcSetup('rh-manager', tipoColaborador: 'Contratista');

        expect($user->can('applyPositionChange', $contratoContratista))->toBeFalse();
    });

    it('policy applyPositionChange permite contratos de colaboradores tipo Empleado', function (): void {
        [$user, , $contratoEmpleado] = pcSetup('rh-manager', tipoColaborador: 'Empleado');

        expect($user->can('applyPositionChange', $contratoEmpleado))->toBeTrue();
    });

    it('modal Livewire devuelve forbidden si el colaborador es Contratista', function (): void {
        [$user, , $contratoContratista] = pcSetup('rh-manager', tipoColaborador: 'Contratista');

        Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contratoContratista->id)
            ->assertForbidden();
    });

    it('policy applyPositionChange rechaza usuarios de otra institución', function (): void {
        [$userA, $institutionA, $contratoA] = pcSetup('rh-manager');
        $institutionB = Institution::factory()->create();
        $userB = User::factory()->create(['institution_id' => $institutionB->id]);
        $userB->assignRole('rh-manager');

        expect($userB->can('applyPositionChange', $contratoA))->toBeFalse();
        expect($userA->can('applyPositionChange', $contratoA))->toBeTrue();
    });
});

describe('Backfill seeder', function (): void {

    it('es idempotente: dos corridas no duplican histórico', function (): void {
        [$user, $institution, $contrato] = pcSetup();

        $seeder = new \Database\Seeders\RH\PositionChangeBackfillSeeder;
        $seeder->run();
        $seeder->run();

        expect(PositionChangeHistory::query()->where('contract_id', $contrato->id)->count())->toBe(1);
    });

    it('omite contratos sin position_id', function (): void {
        [$user, $institution] = pcSetup();
        $documentType = DocumentType::factory()->create(['institution_id' => $institution->id]);
        $status = CollaboratorStatus::factory()->create(['institution_id' => $institution->id]);
        $col = Collaborator::factory()->create([
            'institution_id' => $institution->id,
            'document_type_id' => $documentType->id,
            'status_id' => $status->id,
        ]);
        $tipo = ContractType::factory()->create([
            'institution_id' => $institution->id,
            'code' => 'CT-'.Str::random(6),
        ]);
        $contratoSinCargo = Contract::factory()->create([
            'institution_id' => $institution->id,
            'collaborator_id' => $col->id,
            'contract_type_id' => $tipo->id,
            'position_id' => null,
            'status' => 'Vigente',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
        ]);

        (new \Database\Seeders\RH\PositionChangeBackfillSeeder)->run();

        expect(PositionChangeHistory::query()->where('contract_id', $contratoSinCargo->id)->count())->toBe(0);
    });
});

describe('Sugerencias del autocomplete', function (): void {

    it('solo muestra cargos de la institución del contrato', function (): void {
        [$user, $institution, $contrato] = pcSetup('rh-manager');
        $cargoMismaInst = pcNewPosition($institution->id, 'Profesional');
        $otraInstitucion = Institution::factory()->create();
        pcNewPosition($otraInstitucion->id, 'Foráneo');

        $component = Livewire::actingAs($user)
            ->test('rh.position-change-modal')
            ->dispatch('open-position-change-modal', contractId: $contrato->id)
            ->set('positionSearch', 'Pro');

        $sugerencias = $component->get('positionSuggestions');
        $ids = collect($sugerencias)->pluck('id')->all();

        expect($ids)->toContain($cargoMismaInst->id);
    });
});
