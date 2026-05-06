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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ── Helpers locales (prefijo cv* para evitar colisiones con ContractTest) ────

function cvBuildInstitutionAndUser(string $rol = 'rh-manager'): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    return [$user, $institution];
}

function cvBuildContract(Institution $institution, ?int $year = null): Contract
{
    $year ??= now()->year;

    $documentType = DocumentType::factory()->create(['institution_id' => $institution->id]);
    $status = CollaboratorStatus::factory()->create(['institution_id' => $institution->id]);
    $colaborador = Collaborator::factory()->create([
        'institution_id' => $institution->id,
        'document_type_id' => $documentType->id,
        'status_id' => $status->id,
    ]);
    $tipo = ContractType::factory()->create([
        'institution_id' => $institution->id,
        'code' => 'CT-'.\Illuminate\Support\Str::random(6),
    ]);

    return Contract::factory()->create([
        'institution_id' => $institution->id,
        'collaborator_id' => $colaborador->id,
        'contract_type_id' => $tipo->id,
        'start_date' => now()->setYear($year)->startOfYear()->toDateString(),
        'end_date' => now()->setYear($year)->endOfYear()->toDateString(),
        'status' => 'Vigente',
    ]);
}

// ── Tests ────────────────────────────────────────────────────────────────────

describe('CommittedValueController', function (): void {

    describe('index', function (): void {
        it('lista los valores comprometidos paginados de un contrato', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contrato = cvBuildContract($institution);
            CommittedValue::factory()->count(3)->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
            ]);

            $this->actingAs($user)
                ->get(route('rh.contratos.valores-comprometidos.index', $contrato))
                ->assertOk()
                ->assertSee('Valores comprometidos');
        });

        it('no muestra valores soft-deleted', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contrato = cvBuildContract($institution);
            $cv = CommittedValue::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
                'cost_center' => 'CC-VISIBLE',
            ]);
            $eliminado = CommittedValue::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
                'cost_center' => 'CC-OCULTO',
            ]);
            $eliminado->delete();

            $response = $this->actingAs($user)
                ->get(route('rh.contratos.valores-comprometidos.index', $contrato));

            $response->assertOk()
                ->assertSee('CC-VISIBLE')
                ->assertDontSee('CC-OCULTO');
        });
    });

    describe('store', function (): void {
        it('rh-manager crea un valor comprometido y queda asociado al contrato y la institución', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contrato = cvBuildContract($institution);

            $this->actingAs($user)
                ->post(route('rh.contratos.valores-comprometidos.store', $contrato), [
                    'accounting_account' => '1110-05',
                    'cost_center' => 'CC-100',
                    'amount' => '5000000',
                ])
                ->assertRedirect(route('rh.contratos.valores-comprometidos.index', $contrato))
                ->assertSessionHas('exito');

            expect(CommittedValue::query()->where('contract_id', $contrato->id)->count())->toBe(1);
            $cv = CommittedValue::query()->where('contract_id', $contrato->id)->first();
            expect($cv->institution_id)->toBe($institution->id);
            expect((float) $cv->amount)->toBe(5_000_000.0);
        });

        it('rechaza la creación con datos inválidos', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contrato = cvBuildContract($institution);

            $this->actingAs($user)
                ->post(route('rh.contratos.valores-comprometidos.store', $contrato), [
                    'accounting_account' => '',
                    'cost_center' => str_repeat('X', 250),
                    'amount' => '-10',
                ])
                ->assertSessionHasErrors(['accounting_account', 'cost_center', 'amount']);

            expect(CommittedValue::query()->count())->toBe(0);
        });

        it('rechaza la creación si el rol no tiene permiso (rh-viewer)', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-viewer');
            $contrato = cvBuildContract($institution);

            $this->actingAs($user)
                ->post(route('rh.contratos.valores-comprometidos.store', $contrato), [
                    'accounting_account' => '1110-05',
                    'cost_center' => 'CC-100',
                    'amount' => '5000000',
                ])
                ->assertForbidden();
        });

        it('rechaza la creación si el contrato pertenece a otra institución (multi-tenancy)', function (): void {
            [$user] = cvBuildInstitutionAndUser('rh-manager');
            $otraInstitucion = Institution::factory()->create();
            $contratoAjeno = cvBuildContract($otraInstitucion);

            $this->actingAs($user)
                ->post(route('rh.contratos.valores-comprometidos.store', $contratoAjeno), [
                    'accounting_account' => '1110-05',
                    'cost_center' => 'CC-100',
                    'amount' => '5000000',
                ])
                ->assertForbidden();
        });

        it('impide gestionar valores en contratos de años anteriores', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contratoHistorico = cvBuildContract($institution, year: now()->year - 2);

            $this->actingAs($user)
                ->post(route('rh.contratos.valores-comprometidos.store', $contratoHistorico), [
                    'accounting_account' => '1110-05',
                    'cost_center' => 'CC-100',
                    'amount' => '5000000',
                ])
                ->assertForbidden();
        });
    });

    describe('update', function (): void {
        it('actualiza accounting_account, cost_center y amount', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contrato = cvBuildContract($institution);
            $cv = CommittedValue::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
                'accounting_account' => '0000-00',
                'cost_center' => 'CC-OLD',
                'amount' => 1_000_000,
            ]);

            $this->actingAs($user)
                ->put(route('rh.contratos.valores-comprometidos.update', [$contrato, $cv]), [
                    'accounting_account' => '2222-22',
                    'cost_center' => 'CC-NEW',
                    'amount' => '7500000',
                ])
                ->assertRedirect(route('rh.contratos.valores-comprometidos.index', $contrato))
                ->assertSessionHas('exito');

            $cv->refresh();
            expect($cv->accounting_account)->toBe('2222-22');
            expect($cv->cost_center)->toBe('CC-NEW');
            expect((float) $cv->amount)->toBe(7_500_000.0);
        });

        it('no permite cambiar contract_id ni institution_id desde el payload', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contrato = cvBuildContract($institution);
            $otroContrato = cvBuildContract($institution);
            $cv = CommittedValue::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
            ]);

            $this->actingAs($user)
                ->put(route('rh.contratos.valores-comprometidos.update', [$contrato, $cv]), [
                    'accounting_account' => '2222-22',
                    'cost_center' => 'CC-NEW',
                    'amount' => '500000',
                    'contract_id' => $otroContrato->id,
                    'institution_id' => Institution::factory()->create()->id,
                ])
                ->assertRedirect();

            $cv->refresh();
            expect($cv->contract_id)->toBe($contrato->id);
            expect($cv->institution_id)->toBe($institution->id);
        });

        it('rechaza update de un valor de otra institución', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $otra = Institution::factory()->create();
            $contratoAjeno = cvBuildContract($otra);
            $cvAjeno = CommittedValue::factory()->create([
                'institution_id' => $otra->id,
                'contract_id' => $contratoAjeno->id,
            ]);

            $this->actingAs($user)
                ->put(route('rh.contratos.valores-comprometidos.update', [$contratoAjeno, $cvAjeno]), [
                    'accounting_account' => '1111',
                    'cost_center' => 'CC-X',
                    'amount' => '100',
                ])
                ->assertForbidden();
        });

        it('retorna 404 cuando el valor no pertenece al contrato de la URL (scopeBindings)', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contratoA = cvBuildContract($institution);
            $contratoB = cvBuildContract($institution);
            $cvDeB = CommittedValue::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contratoB->id,
            ]);

            $this->actingAs($user)
                ->put(route('rh.contratos.valores-comprometidos.update', [$contratoA, $cvDeB]), [
                    'accounting_account' => '1111',
                    'cost_center' => 'CC-X',
                    'amount' => '100',
                ])
                ->assertNotFound();
        });
    });

    describe('destroy', function (): void {
        it('soft-deletea un valor comprometido', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contrato = cvBuildContract($institution);
            $cv = CommittedValue::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
            ]);

            $this->actingAs($user)
                ->delete(route('rh.contratos.valores-comprometidos.destroy', [$contrato, $cv]))
                ->assertRedirect()
                ->assertSessionHas('exito');

            expect(CommittedValue::query()->find($cv->id))->toBeNull();
            expect(CommittedValue::withTrashed()->find($cv->id))->not->toBeNull();
        });

        it('bloquea la eliminación cuando una prórroga referencia el valor', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-manager');
            $contrato = cvBuildContract($institution);
            $cv = CommittedValue::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
            ]);
            ContractExtension::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
                'committed_value_id' => $cv->id,
                'extension_type' => 'valor',
                'approval_date' => now()->toDateString(),
            ]);

            $this->actingAs($user)
                ->delete(route('rh.contratos.valores-comprometidos.destroy', [$contrato, $cv]))
                ->assertRedirect()
                ->assertSessionHas('error');

            expect(CommittedValue::query()->find($cv->id))->not->toBeNull();
        });

        it('rechaza delete por rol viewer', function (): void {
            [$user, $institution] = cvBuildInstitutionAndUser('rh-viewer');
            $contrato = cvBuildContract($institution);
            $cv = CommittedValue::factory()->create([
                'institution_id' => $institution->id,
                'contract_id' => $contrato->id,
            ]);

            $this->actingAs($user)
                ->delete(route('rh.contratos.valores-comprometidos.destroy', [$contrato, $cv]))
                ->assertForbidden();

            expect(CommittedValue::query()->find($cv->id))->not->toBeNull();
        });
    });
});
