<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OwenIt\Auditing\Models\Audit;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function fixCsvPath(): string
{
    return tempnam(sys_get_temp_dir(), 'fix_collab_').'.csv';
}

/**
 * Genera un CSV temporal con encabezado de la importación legacy.
 *
 * @param  array<int,array<string,string>>  $filas
 */
function fixCrearCsv(array $filas): string
{
    $headers = ['id', 'numdoc', 'tipo_colaborador', 'es_empresa'];
    $path = fixCsvPath();

    $handle = fopen($path, 'w');
    fputcsv($handle, $headers);
    foreach ($filas as $f) {
        fputcsv($handle, [
            $f['id'] ?? '0',
            $f['numdoc'] ?? '',
            $f['tipo_colaborador'] ?? '',
            $f['es_empresa'] ?? 'false',
        ]);
    }
    fclose($handle);

    return $path;
}

function fixSetup(string $nit = '860006560'): array
{
    $institution = Institution::factory()->create(['nit' => $nit]);
    $documentType = DocumentType::factory()->create(['institution_id' => $institution->id]);
    $status = CollaboratorStatus::factory()->create(['institution_id' => $institution->id]);

    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole('super-admin');

    return compact('institution', 'documentType', 'status', 'user');
}

function fixCrearColaborador(array $ctx, string $numdoc, string $type = 'Contratista', bool $isCompany = false): Collaborator
{
    return Collaborator::factory()->create([
        'institution_id' => $ctx['institution']->id,
        'document_type_id' => $ctx['documentType']->id,
        'status_id' => $ctx['status']->id,
        'document_number' => $numdoc,
        'type' => $type,
        'is_company' => $isCompany,
    ]);
}

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir().'/fix_collab_*.csv') ?: [] as $f) {
        @unlink($f);
    }
});

// ── Tests ─────────────────────────────────────────────────────────────────────

test('actualiza el tipo de Contratista a Empleado para filas con tipo_colaborador=Empleado', function (): void {
    $ctx = fixSetup();
    $a = fixCrearColaborador($ctx, '111');
    $b = fixCrearColaborador($ctx, '222');
    $c = fixCrearColaborador($ctx, '333');

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
        ['id' => '2', 'numdoc' => '222', 'tipo_colaborador' => 'Empleado'],
        ['id' => '3', 'numdoc' => '333', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->assertSuccessful();

    expect($a->fresh()->type)->toBe('Empleado');
    expect($b->fresh()->type)->toBe('Empleado');
    expect($c->fresh()->type)->toBe('Empleado');
});

test('dry-run no modifica la base de datos ni genera auditorías', function (): void {
    $ctx = fixSetup();
    $a = fixCrearColaborador($ctx, '111');

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
        '--dry-run' => true,
    ])->assertSuccessful();

    expect($a->fresh()->type)->toBe('Contratista');
    expect(Audit::count())->toBe(0);
});

test('colaboradores no encontrados en BD se reportan como skipped sin error', function (): void {
    $ctx = fixSetup();

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '999999', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->assertSuccessful();

    expect(Audit::count())->toBe(0);
});

test('colaboradores ya marcados como Empleado se cuentan como ya_correctos sin actualizar', function (): void {
    $ctx = fixSetup();
    $a = fixCrearColaborador($ctx, '111', 'Empleado');

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->assertSuccessful();

    expect($a->fresh()->type)->toBe('Empleado');
    expect(Audit::count())->toBe(0);
});

test('filas con es_empresa=true se ignoran aunque tipo_colaborador sea Empleado', function (): void {
    $ctx = fixSetup();
    $a = fixCrearColaborador($ctx, '111', 'Contratista', isCompany: true);

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado', 'es_empresa' => 'true'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->assertSuccessful();

    expect($a->fresh()->type)->toBe('Contratista');
});

test('falla con código FAILURE si el CSV no existe', function (): void {
    $ctx = fixSetup();

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => '/tmp/inexistente_'.uniqid().'.csv',
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsOutputToContain('No se encontró el archivo CSV')
        ->assertFailed();
});

test('falla si el NIT de la institución no existe', function (): void {
    $ctx = fixSetup();
    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => '000000',
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsOutputToContain('No se encontró una institución con NIT')
        ->assertFailed();
});

test('respeta la cancelación de la confirmación y no aplica cambios', function (): void {
    $ctx = fixSetup();
    $a = fixCrearColaborador($ctx, '111');

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'no')
        ->expectsOutput('Operación cancelada por el usuario.')
        ->assertSuccessful();

    expect($a->fresh()->type)->toBe('Contratista');
    expect(Audit::count())->toBe(0);
});

test('funciona sin --user-id usando el primer super-admin como firmante', function (): void {
    $ctx = fixSetup();
    $colab = fixCrearColaborador($ctx, '111');

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->assertSuccessful();

    expect($colab->fresh()->type)->toBe('Empleado');
});

test('respeta institution_id y no cruza tenants', function (): void {
    $ctxA = fixSetup('860000001');
    $ctxB = fixSetup('860000002');

    $colabA = fixCrearColaborador($ctxA, '777');
    $colabB = fixCrearColaborador($ctxB, '777');

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '777', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctxA['institution']->nit,
        '--user-id' => $ctxA['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->assertSuccessful();

    expect($colabA->fresh()->type)->toBe('Empleado');
    expect($colabB->fresh()->type)->toBe('Contratista');
});

test('filas con tipo_colaborador no estándar generan warning pero no abortan', function (): void {
    $ctx = fixSetup();
    fixCrearColaborador($ctx, '111');

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Otro'],
        ['id' => '2', 'numdoc' => '222', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->expectsOutputToContain('Filas con tipo_colaborador inválido:')
        ->assertSuccessful();
});

test('falla si el user-id pasado no existe', function (): void {
    $ctx = fixSetup();
    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => '00000000-0000-0000-0000-000000000000',
    ])
        ->expectsOutputToContain('No se encontró un usuario con ID')
        ->assertFailed();
});

test('procesa correctamente un CSV con BOM en el encabezado', function (): void {
    $ctx = fixSetup();
    $colab = fixCrearColaborador($ctx, '111');

    $path = fixCsvPath();
    $handle = fopen($path, 'w');
    fwrite($handle, "\u{FEFF}");
    fputcsv($handle, ['id', 'numdoc', 'tipo_colaborador', 'es_empresa']);
    fputcsv($handle, ['1', '111', 'Empleado', 'false']);
    fclose($handle);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $path,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->assertSuccessful();

    expect($colab->fresh()->type)->toBe('Empleado');
});

test('deduplica numdoc repetidos en el CSV', function (): void {
    $ctx = fixSetup();
    $colab = fixCrearColaborador($ctx, '111');

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
        ['id' => '2', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
        ['id' => '3', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->expectsOutputToContain('Candidatos a Empleado en CSV')
        ->assertSuccessful();

    expect($colab->fresh()->type)->toBe('Empleado');
});

test('aborta toda la transacción si falla un update y devuelve FAILURE', function (): void {
    $ctx = fixSetup();
    $colabA = fixCrearColaborador($ctx, '111');
    $colabB = fixCrearColaborador($ctx, '222');

    // Forzar un error: hacer que la columna no acepte el valor (rompiendo el modelo no es trivial,
    // pero podemos simular con un cierre que sustituye la BD por un mock parcial).
    // Estrategia: mockear DB::transaction para que lance.
    \DB::shouldReceive('transaction')
        ->once()
        ->andThrow(new \RuntimeException('Falla simulada'));

    $csv = fixCrearCsv([
        ['id' => '1', 'numdoc' => '111', 'tipo_colaborador' => 'Empleado'],
        ['id' => '2', 'numdoc' => '222', 'tipo_colaborador' => 'Empleado'],
    ]);

    $this->artisan('rh:fix-collaborator-types', [
        'csv' => $csv,
        '--institution-nit' => $ctx['institution']->nit,
        '--user-id' => $ctx['user']->id,
    ])
        ->expectsConfirmation('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', 'yes')
        ->expectsOutputToContain('La transacción fue revertida')
        ->assertFailed();

    expect($colabA->fresh()->type)->toBe('Contratista');
    expect($colabB->fresh()->type)->toBe('Contratista');
});
