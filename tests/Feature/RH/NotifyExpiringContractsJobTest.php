<?php

declare(strict_types=1);

use App\Jobs\RH\NotifyExpiringContractsJob;
use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\Contract;
use App\Models\RH\ContractExpiringNotificationLog;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\User;
use App\Notifications\RH\ContractExpiringSoonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    Notification::fake();
});

// ── Helpers locales ──────────────────────────────────────────────────────────

function crearContratoPorVencer(Institution $institution, int $diasRestantes, string $status = 'Vigente'): Contract
{
    $colaborador = Collaborator::factory()->create([
        'institution_id' => $institution->id,
        'document_type_id' => DocumentType::factory()->create(),
        'status_id' => CollaboratorStatus::factory()->create(),
    ]);

    return Contract::factory()->create([
        'institution_id' => $institution->id,
        'collaborator_id' => $colaborador->id,
        'contract_type_id' => ContractType::factory()->create(),
        'start_date' => now()->subMonths(6)->toDateString(),
        'end_date' => now()->addDays($diasRestantes)->toDateString(),
        'status' => $status,
    ]);
}

function crearGestor(Institution $institution, string $rol): User
{
    $user = User::factory()->create([
        'institution_id' => $institution->id,
        'is_active' => true,
    ]);
    $user->assignRole($rol);

    return $user;
}

// ── Tests ────────────────────────────────────────────────────────────────────

describe('NotifyExpiringContractsJob', function (): void {

    it('no envía notificaciones si no hay contratos próximos a vencer', function (): void {
        $institution = Institution::factory()->create();
        crearGestor($institution, 'rh-manager');
        // contrato con fin a 100 días — fuera de cualquier threshold
        crearContratoPorVencer($institution, 100);

        (new NotifyExpiringContractsJob)->handle();

        Notification::assertNothingSent();
        expect(ContractExpiringNotificationLog::count())->toBe(0);
    });

    it('notifica a admin, rh-manager y contractor-manager de la institución del contrato', function (): void {
        $institution = Institution::factory()->create();
        $admin = crearGestor($institution, 'admin');
        $rhManager = crearGestor($institution, 'rh-manager');
        $contractorManager = crearGestor($institution, 'contractor-manager');
        // Usuario sin rol válido — no debe recibir notificación
        $rhViewer = crearGestor($institution, 'rh-viewer');

        crearContratoPorVencer($institution, 5); // dentro del threshold de 7

        (new NotifyExpiringContractsJob)->handle();

        Notification::assertSentTo($admin, ContractExpiringSoonNotification::class);
        Notification::assertSentTo($rhManager, ContractExpiringSoonNotification::class);
        Notification::assertSentTo($contractorManager, ContractExpiringSoonNotification::class);
        Notification::assertNotSentTo($rhViewer, ContractExpiringSoonNotification::class);
    });

    it('es idempotente: no duplica notificaciones del mismo threshold en ejecuciones consecutivas', function (): void {
        $institution = Institution::factory()->create();
        $manager = crearGestor($institution, 'rh-manager');
        crearContratoPorVencer($institution, 5);

        (new NotifyExpiringContractsJob)->handle();
        (new NotifyExpiringContractsJob)->handle();

        // El threshold 7 dispara una sola vez. Los thresholds 15 y 30 también
        // matchean (5 < 15 y 5 < 30), por lo que el primer run inserta 3 logs
        // (uno por threshold). El segundo run no debe agregar ninguno.
        expect(ContractExpiringNotificationLog::count())->toBe(3);
        // Cada manager recibió exactamente 3 notificaciones (una por threshold).
        Notification::assertSentToTimes($manager, ContractExpiringSoonNotification::class, 3);
    });

    it('ignora contratos cuyo estado no es Vigente', function (): void {
        $institution = Institution::factory()->create();
        crearGestor($institution, 'rh-manager');
        crearContratoPorVencer($institution, 5, 'Terminado');

        (new NotifyExpiringContractsJob)->handle();

        Notification::assertNothingSent();
        expect(ContractExpiringNotificationLog::count())->toBe(0);
    });
});
