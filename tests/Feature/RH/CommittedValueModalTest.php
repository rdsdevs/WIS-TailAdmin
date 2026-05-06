<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ── Helpers locales (prefijo cvm* para evitar colisiones) ────────────────────

function cvmSetup(string $rol = 'rh-manager'): array
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
    ]);
    $tipo = ContractType::factory()->create([
        'institution_id' => $institution->id,
        'code' => 'CT-'.Str::random(6),
    ]);
    $contrato = Contract::factory()->create([
        'institution_id' => $institution->id,
        'collaborator_id' => $colaborador->id,
        'contract_type_id' => $tipo->id,
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'status' => 'Vigente',
    ]);

    return [$user, $institution, $contrato];
}

// ── Tests ────────────────────────────────────────────────────────────────────

describe('committed-value-form-modal (Livewire SFC)', function (): void {

    it('crea un valor comprometido al recibir el evento open-committed-value-create y guardar', function (): void {
        [$user, $institution, $contrato] = cvmSetup('rh-manager');

        Livewire::actingAs($user)
            ->test('rh.committed-value-form-modal', ['contractId' => $contrato->id])
            ->dispatch('open-committed-value-create', contractId: $contrato->id)
            ->assertSet('open', true)
            ->set('accountingAccount', '1110-05')
            ->set('costCenter', 'CC-200')
            ->set('amount', '3500000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('committed-value-saved');

        expect(CommittedValue::query()->where('contract_id', $contrato->id)->count())->toBe(1);
        $cv = CommittedValue::query()->where('contract_id', $contrato->id)->first();
        expect($cv->accounting_account)->toBe('1110-05');
        expect($cv->institution_id)->toBe($institution->id);
    });

    it('precarga los datos al recibir el evento open-committed-value-edit', function (): void {
        [$user, $institution, $contrato] = cvmSetup('rh-manager');
        $cv = CommittedValue::factory()->create([
            'institution_id' => $institution->id,
            'contract_id' => $contrato->id,
            'accounting_account' => '9999-99',
            'cost_center' => 'CC-LOAD',
            'amount' => 1_234_567,
        ]);

        Livewire::actingAs($user)
            ->test('rh.committed-value-form-modal', ['contractId' => $contrato->id])
            ->dispatch('open-committed-value-edit', committedValueId: $cv->id)
            ->assertSet('open', true)
            ->assertSet('committedValueId', $cv->id)
            ->assertSet('accountingAccount', '9999-99')
            ->assertSet('costCenter', 'CC-LOAD');
    });

    it('valida campos obligatorios', function (): void {
        [$user, , $contrato] = cvmSetup('rh-manager');

        Livewire::actingAs($user)
            ->test('rh.committed-value-form-modal', ['contractId' => $contrato->id])
            ->dispatch('open-committed-value-create', contractId: $contrato->id)
            ->set('accountingAccount', '')
            ->set('costCenter', '')
            ->set('amount', '')
            ->call('save')
            ->assertHasErrors(['accountingAccount', 'costCenter', 'amount']);
    });

    it('rechaza apertura si el rol no tiene permiso de creación', function (): void {
        [$user, , $contrato] = cvmSetup('rh-viewer');

        Livewire::actingAs($user)
            ->test('rh.committed-value-form-modal', ['contractId' => $contrato->id])
            ->dispatch('open-committed-value-create', contractId: $contrato->id)
            ->assertForbidden();
    });
});

describe('committed-value-delete-modal (Livewire SFC)', function (): void {

    it('elimina (soft-delete) al confirmar', function (): void {
        [$user, $institution, $contrato] = cvmSetup('rh-manager');
        $cv = CommittedValue::factory()->create([
            'institution_id' => $institution->id,
            'contract_id' => $contrato->id,
        ]);

        Livewire::actingAs($user)
            ->test('rh.committed-value-delete-modal', ['contractId' => $contrato->id])
            ->dispatch('open-committed-value-delete', committedValueId: $cv->id, label: 'demo')
            ->assertSet('open', true)
            ->call('confirm')
            ->assertDispatched('committed-value-deleted');

        expect(CommittedValue::query()->find($cv->id))->toBeNull();
        expect(CommittedValue::withTrashed()->find($cv->id))->not->toBeNull();
    });

    it('rechaza apertura si el rol no tiene permiso de eliminación', function (): void {
        [$user, $institution, $contrato] = cvmSetup('rh-viewer');
        $cv = CommittedValue::factory()->create([
            'institution_id' => $institution->id,
            'contract_id' => $contrato->id,
        ]);

        Livewire::actingAs($user)
            ->test('rh.committed-value-delete-modal', ['contractId' => $contrato->id])
            ->dispatch('open-committed-value-delete', committedValueId: $cv->id, label: 'demo')
            ->assertForbidden();
    });
});
