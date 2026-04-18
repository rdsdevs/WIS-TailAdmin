<?php

declare(strict_types=1);

use App\Imports\RH\PositionAuthorityImport;
use App\Imports\RH\PositionEmailImport;
use App\Imports\RH\PositionFunctionImport;
use App\Imports\RH\PositionImport;
use App\Imports\RH\PositionResponsibilityImport;
use App\Jobs\RH\ImportPositionAuthoritiesJob;
use App\Jobs\RH\ImportPositionEmailsJob;
use App\Jobs\RH\ImportPositionFunctionsJob;
use App\Jobs\RH\ImportPositionResponsibilitiesJob;
use App\Jobs\RH\ImportPositionsJob;
use App\Models\Institution;
use App\Models\RH\Position;
use App\Models\RH\PositionAuthority;
use App\Models\RH\PositionEmail;
use App\Models\RH\PositionFunction;
use App\Models\RH\PositionResponsibility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers locales
// ---------------------------------------------------------------------------

/**
 * Crea institución y usuario con el rol indicado.
 */
function contextoImportacionCargos(string $rol): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    return [$user, $institution];
}

/**
 * Genera un UploadedFile Excel con filas de cargos usando PhpSpreadsheet.
 *
 * @param  array<int, array<string, string>>  $filas
 */
function crearExcelCargos(array $filas = []): \Illuminate\Http\UploadedFile
{
    if (empty($filas)) {
        $filas = [
            ['nombre_cargo' => 'Coordinador de Prueba', 'activo' => 'SI'],
            ['nombre_cargo' => 'Analista de Prueba', 'activo' => 'SI'],
        ];
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $encabezados = array_keys($filas[0]);
    $sheet->fromArray($encabezados, null, 'A1');

    foreach ($filas as $indice => $fila) {
        $sheet->fromArray(array_values($fila), null, 'A'.($indice + 2));
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $ruta = sys_get_temp_dir().'/test_cargos_'.uniqid().'.xlsx';
    $writer->save($ruta);

    return new \Illuminate\Http\UploadedFile(
        $ruta,
        'cargos.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

/**
 * Genera un UploadedFile Excel con filas de funciones de cargo.
 *
 * @param  array<int, array<string, string>>  $filas
 */
function crearExcelFunciones(array $filas = []): \Illuminate\Http\UploadedFile
{
    if (empty($filas)) {
        $filas = [
            ['cargo' => 'Coordinador de Prueba', 'descripcion_funcion' => 'Coordinar actividades del área'],
        ];
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $encabezados = array_keys($filas[0]);
    $sheet->fromArray($encabezados, null, 'A1');

    foreach ($filas as $indice => $fila) {
        $sheet->fromArray(array_values($fila), null, 'A'.($indice + 2));
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $ruta = sys_get_temp_dir().'/test_funciones_'.uniqid().'.xlsx';
    $writer->save($ruta);

    return new \Illuminate\Http\UploadedFile(
        $ruta,
        'funciones.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

/**
 * Genera un UploadedFile Excel con filas de correos de cargo.
 *
 * @param  array<int, array<string, string>>  $filas
 */
function crearExcelCorreos(array $filas = []): \Illuminate\Http\UploadedFile
{
    if (empty($filas)) {
        $filas = [
            ['cargo' => 'Coordinador de Prueba', 'correo_electronico' => 'coordinador@ascun.edu.co'],
        ];
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $encabezados = array_keys($filas[0]);
    $sheet->fromArray($encabezados, null, 'A1');

    foreach ($filas as $indice => $fila) {
        $sheet->fromArray(array_values($fila), null, 'A'.($indice + 2));
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $ruta = sys_get_temp_dir().'/test_correos_'.uniqid().'.xlsx';
    $writer->save($ruta);

    return new \Illuminate\Http\UploadedFile(
        $ruta,
        'correos.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

// ---------------------------------------------------------------------------
// Autenticación y autorización
// ---------------------------------------------------------------------------

describe('Autenticación y autorización en importación de cargos', function (): void {

    it('usuario no autenticado es redirigido al login al acceder a la importación', function (): void {
        $this->get(route('rh.cargos.importar'))
            ->assertRedirect(route('login'));
    });

    it('rh-viewer no puede acceder a la página de importación de cargos', function (): void {
        [$user] = contextoImportacionCargos('rh-viewer');

        $this->actingAs($user)
            ->get(route('rh.cargos.importar'))
            ->assertForbidden();
    });

    it('rh-manager puede acceder a la página de importación de cargos', function (): void {
        [$user] = contextoImportacionCargos('rh-manager');

        $this->actingAs($user)
            ->get(route('rh.cargos.importar'))
            ->assertOk();
    });
});

// ---------------------------------------------------------------------------
// Descarga de plantillas
// ---------------------------------------------------------------------------

describe('Descarga de plantillas de importación de cargos', function (): void {

    it('rh-manager puede descargar la plantilla de cargos', function (): void {
        [$user] = contextoImportacionCargos('rh-manager');

        $this->actingAs($user)
            ->get(route('rh.cargos.plantilla', ['tipo' => 'cargos']))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    });

    it('rh-manager puede descargar la plantilla de funciones', function (): void {
        [$user] = contextoImportacionCargos('rh-manager');

        $this->actingAs($user)
            ->get(route('rh.cargos.plantilla', ['tipo' => 'funciones']))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    });
});

// ---------------------------------------------------------------------------
// Validación del formulario de importación
// ---------------------------------------------------------------------------

describe('Validación del formulario de importación de cargos', function (): void {

    it('rechaza la importación cuando no se envía el tipo', function (): void {
        [$user] = contextoImportacionCargos('rh-manager');
        $archivo = crearExcelCargos();

        $this->actingAs($user)
            ->post(route('rh.cargos.importar.store'), [
                'archivo' => $archivo,
                // 'tipo' omitido intencionalmente
            ])
            ->assertSessionHasErrors('tipo');
    });

    it('rechaza la importación con un tipo inválido', function (): void {
        [$user] = contextoImportacionCargos('rh-manager');
        $archivo = crearExcelCargos();

        $this->actingAs($user)
            ->post(route('rh.cargos.importar.store'), [
                'archivo' => $archivo,
                'tipo' => 'invalido',
            ])
            ->assertSessionHasErrors('tipo');
    });
});

// ---------------------------------------------------------------------------
// Importación de cargos — happy path
// ---------------------------------------------------------------------------

describe('Importación masiva de cargos', function (): void {

    it('importar archivo Excel con cargos válidos despacha el job y redirige con mensaje de éxito', function (): void {
        Bus::fake();

        [$user] = contextoImportacionCargos('rh-manager');
        $archivo = crearExcelCargos();

        $this->actingAs($user)
            ->post(route('rh.cargos.importar.store'), [
                'archivo' => $archivo,
                'tipo' => 'cargos',
            ])
            ->assertRedirect(route('rh.cargos.importar'))
            ->assertSessionHas('exito');

        Bus::assertDispatched(ImportPositionsJob::class);
    });

    it('importar cargo existente lo actualiza sin crear duplicado', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        // Cargo que ya existe en la base de datos
        Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Coordinador Existente',
            'is_active'      => true,
        ]);

        $import = new PositionImport(institutionId: (string) $institution->id);

        $filas = collect([
            collect([
                'nombre_cargo' => 'Coordinador Existente',
                'activo'       => 'NO',
            ]),
        ]);

        $import->collection($filas);

        // No se crea un duplicado
        expect(
            Position::where('institution_id', $institution->id)
                ->where('name', 'Coordinador Existente')
                ->count()
        )->toBe(1);

        // El registro fue actualizado, no insertado de nuevo
        expect($import->updated)->toBe(1);
        expect($import->imported)->toBe(0);

        // El campo is_active fue actualizado a false
        $cargo = Position::where('name', 'Coordinador Existente')->first();
        expect($cargo->is_active)->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// Importación de funciones
// ---------------------------------------------------------------------------

describe('Importación masiva de funciones de cargo', function (): void {

    it('importar funciones válidas despacha el job de funciones correctamente', function (): void {
        Bus::fake();

        [$user] = contextoImportacionCargos('rh-manager');
        $archivo = crearExcelFunciones();

        $this->actingAs($user)
            ->post(route('rh.cargos.importar.store'), [
                'archivo' => $archivo,
                'tipo' => 'funciones',
            ])
            ->assertRedirect(route('rh.cargos.importar'))
            ->assertSessionHas('exito');

        Bus::assertDispatched(ImportPositionFunctionsJob::class);
    });
});

// ---------------------------------------------------------------------------
// Importación de correos
// ---------------------------------------------------------------------------

describe('Importación masiva de correos de cargo', function (): void {

    it('importar correos válidos despacha el job de correos correctamente', function (): void {
        Bus::fake();

        [$user] = contextoImportacionCargos('rh-manager');
        $archivo = crearExcelCorreos();

        $this->actingAs($user)
            ->post(route('rh.cargos.importar.store'), [
                'archivo' => $archivo,
                'tipo' => 'correos',
            ])
            ->assertRedirect(route('rh.cargos.importar'))
            ->assertSessionHas('exito');

        Bus::assertDispatched(ImportPositionEmailsJob::class);
    });
});

// ---------------------------------------------------------------------------
// Pruebas directas de las clases Import
// ---------------------------------------------------------------------------

describe('Clase PositionImport — lógica interna', function (): void {

    it('crea cargos nuevos y actualiza existentes al procesar la colección', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        // Cargo preexistente que debe ser actualizado
        Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Cargo Preexistente',
            'is_active'      => true,
        ]);

        $import = new PositionImport(institutionId: (string) $institution->id);

        $filas = collect([
            collect([
                'nombre_cargo' => 'Cargo Preexistente',
                'activo'       => 'NO',
            ]),
            collect([
                'nombre_cargo' => 'Cargo Nuevo Importado',
                'activo'       => 'SI',
            ]),
        ]);

        $import->collection($filas);

        // El cargo nuevo fue insertado
        $this->assertDatabaseHas('positions', [
            'institution_id' => $institution->id,
            'name'           => 'Cargo Nuevo Importado',
            'is_active'      => true,
        ]);

        // El cargo preexistente fue actualizado a inactivo
        $this->assertDatabaseHas('positions', [
            'name'      => 'Cargo Preexistente',
            'is_active' => false,
        ]);

        expect($import->imported)->toBe(1);
        expect($import->updated)->toBe(1);
        expect($import->skipped)->toBe(0);
    });

});

describe('Clase PositionFunctionImport — lógica interna', function (): void {

    it('no duplica funciones idénticas en una reimportación', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        $cargo = Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Coordinador de Funciones',
            'is_active'      => true,
        ]);

        // Función ya registrada manualmente antes de la importación
        PositionFunction::create([
            'position_id' => $cargo->id,
            'description' => 'Elaborar informes mensuales de gestión',
        ]);

        $import = new PositionFunctionImport(institutionId: (string) $institution->id);

        // Reimportar la misma función
        $filas = collect([
            collect([
                'cargo'               => 'Coordinador de Funciones',
                'descripcion_funcion' => 'Elaborar informes mensuales de gestión',
            ]),
        ]);

        $import->collection($filas);

        // Solo debe existir un registro, no se creó duplicado
        expect(
            PositionFunction::where('position_id', $cargo->id)
                ->where('description', 'Elaborar informes mensuales de gestión')
                ->count()
        )->toBe(1);
    });

    it('crea función para un cargo existente', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        $cargo = Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Analista de Nómina',
            'is_active'      => true,
        ]);

        $import = new PositionFunctionImport(institutionId: (string) $institution->id);

        $filas = collect([
            collect([
                'cargo'               => 'Analista de Nómina',
                'descripcion_funcion' => 'Liquidar la nómina mensual del personal',
            ]),
        ]);

        $import->collection($filas);

        $this->assertDatabaseHas('position_functions', [
            'position_id' => $cargo->id,
            'description' => 'Liquidar la nómina mensual del personal',
        ]);

        expect($import->imported)->toBe(1);
        expect($import->skipped)->toBe(0);
    });
});

describe('Clase PositionEmailImport — lógica interna', function (): void {

    it('crea correo para un cargo existente', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        $cargo = Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Secretaria Ejecutiva',
            'is_active'      => true,
        ]);

        $import = new PositionEmailImport(institutionId: (string) $institution->id);

        $filas = collect([
            collect([
                'cargo'               => 'Secretaria Ejecutiva',
                'correo_electronico'  => 'secretaria@ascun.edu.co',
            ]),
        ]);

        $import->collection($filas);

        $this->assertDatabaseHas('position_emails', [
            'position_id' => $cargo->id,
            'email'       => 'secretaria@ascun.edu.co',
        ]);

        expect($import->imported)->toBe(1);
        expect($import->skipped)->toBe(0);
    });

    it('no duplica correos idénticos en una reimportación', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        $cargo = Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Asesor Jurídico',
            'is_active'      => true,
        ]);

        // Correo ya registrado antes de la importación
        PositionEmail::create([
            'position_id' => $cargo->id,
            'email'       => 'juridica@ascun.edu.co',
        ]);

        $import = new PositionEmailImport(institutionId: (string) $institution->id);

        // Reimportar el mismo correo
        $filas = collect([
            collect([
                'cargo'              => 'Asesor Jurídico',
                'correo_electronico' => 'juridica@ascun.edu.co',
            ]),
        ]);

        $import->collection($filas);

        // Solo debe existir un registro
        expect(
            PositionEmail::where('position_id', $cargo->id)
                ->where('email', 'juridica@ascun.edu.co')
                ->count()
        )->toBe(1);

        // El correo ya existía, se contabiliza como updated, no imported
        expect($import->imported)->toBe(0);
        expect($import->updated)->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// Importación de responsabilidades
// ---------------------------------------------------------------------------

/**
 * Genera un UploadedFile Excel con filas de responsabilidades de cargo.
 *
 * @param  array<int, array<string, string>>  $filas
 */
function crearExcelResponsabilidades(array $filas = []): \Illuminate\Http\UploadedFile
{
    if (empty($filas)) {
        $filas = [
            ['nombre_cargo' => 'Coordinador de Prueba', 'responsabilidad' => 'Garantizar el cumplimiento del plan de trabajo'],
        ];
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $encabezados = array_keys($filas[0]);
    $sheet->fromArray($encabezados, null, 'A1');

    foreach ($filas as $indice => $fila) {
        $sheet->fromArray(array_values($fila), null, 'A'.($indice + 2));
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $ruta = sys_get_temp_dir().'/test_responsabilidades_'.uniqid().'.xlsx';
    $writer->save($ruta);

    return new \Illuminate\Http\UploadedFile(
        $ruta,
        'responsabilidades.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

/**
 * Genera un UploadedFile Excel con filas de autoridades de cargo.
 *
 * @param  array<int, array<string, string>>  $filas
 */
function crearExcelAutoridades(array $filas = []): \Illuminate\Http\UploadedFile
{
    if (empty($filas)) {
        $filas = [
            ['nombre_cargo' => 'Coordinador de Prueba', 'autoridad' => 'Aprobar solicitudes de permiso del personal a cargo'],
        ];
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $encabezados = array_keys($filas[0]);
    $sheet->fromArray($encabezados, null, 'A1');

    foreach ($filas as $indice => $fila) {
        $sheet->fromArray(array_values($fila), null, 'A'.($indice + 2));
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $ruta = sys_get_temp_dir().'/test_autoridades_'.uniqid().'.xlsx';
    $writer->save($ruta);

    return new \Illuminate\Http\UploadedFile(
        $ruta,
        'autoridades.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

describe('Importación de responsabilidades', function (): void {

    it('importar responsabilidades válidas despacha el job correctamente', function (): void {
        Bus::fake();

        [$user] = contextoImportacionCargos('rh-manager');
        $archivo = crearExcelResponsabilidades();

        $this->actingAs($user)
            ->post(route('rh.cargos.importar.store'), [
                'archivo' => $archivo,
                'tipo'    => 'responsabilidades',
            ])
            ->assertRedirect(route('rh.cargos.importar'))
            ->assertSessionHas('exito');

        Bus::assertDispatched(ImportPositionResponsibilitiesJob::class);
    });

    it('cargo inexistente omite la fila sin lanzar excepción (skipped)', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        // No se crea ningún cargo — el nombre de la fila no existirá en el mapa
        $import = new PositionResponsibilityImport(institutionId: (string) $institution->id);

        $filas = collect([
            collect([
                'nombre_cargo'   => 'Cargo Que No Existe',
                'responsabilidad' => 'Responsabilidad sin cargo asociado',
            ]),
        ]);

        $import->collection($filas);

        expect($import->skipped)->toBe(1);
        expect($import->imported)->toBe(0);

        $this->assertDatabaseMissing('position_responsibilities', [
            'description' => 'Responsabilidad sin cargo asociado',
        ]);
    });

    it('reimportar una responsabilidad soft-deleted la restaura', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        $cargo = Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Jefe de Nómina',
            'is_active'      => true,
        ]);

        // Crear la responsabilidad y luego eliminarla lógicamente
        $responsabilidad = PositionResponsibility::create([
            'position_id' => $cargo->id,
            'description' => 'Supervisar el proceso de liquidación mensual',
        ]);
        $responsabilidad->delete();

        $this->assertSoftDeleted('position_responsibilities', [
            'id' => $responsabilidad->id,
        ]);

        $import = new PositionResponsibilityImport(institutionId: (string) $institution->id);

        // Reimportar la misma responsabilidad
        $filas = collect([
            collect([
                'nombre_cargo'   => 'Jefe de Nómina',
                'responsabilidad' => 'Supervisar el proceso de liquidación mensual',
            ]),
        ]);

        $import->collection($filas);

        // El registro debe haberse restaurado, no duplicado
        expect(
            PositionResponsibility::where('position_id', $cargo->id)
                ->where('description', 'Supervisar el proceso de liquidación mensual')
                ->count()
        )->toBe(1);

        expect($import->imported)->toBe(1);
        expect($import->skipped)->toBe(0);
    });

    it('fila con responsabilidad vacía es omitida', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        $cargo = Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Auxiliar Contable',
            'is_active'      => true,
        ]);

        $import = new PositionResponsibilityImport(institutionId: (string) $institution->id);

        $filas = collect([
            collect([
                'nombre_cargo'   => 'Auxiliar Contable',
                'responsabilidad' => '   ', // solo espacios en blanco
            ]),
        ]);

        $import->collection($filas);

        expect($import->skipped)->toBe(1);
        expect($import->imported)->toBe(0);

        $this->assertDatabaseMissing('position_responsibilities', [
            'position_id' => $cargo->id,
        ]);
    });
});

// ---------------------------------------------------------------------------
// Importación de autoridades
// ---------------------------------------------------------------------------

describe('Importación de autoridades', function (): void {

    it('importar autoridades válidas despacha el job correctamente', function (): void {
        Bus::fake();

        [$user] = contextoImportacionCargos('rh-manager');
        $archivo = crearExcelAutoridades();

        $this->actingAs($user)
            ->post(route('rh.cargos.importar.store'), [
                'archivo' => $archivo,
                'tipo'    => 'autoridades',
            ])
            ->assertRedirect(route('rh.cargos.importar'))
            ->assertSessionHas('exito');

        Bus::assertDispatched(ImportPositionAuthoritiesJob::class);
    });

    it('cargo inexistente omite la fila de autoridad sin lanzar excepción', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        // No se crea ningún cargo — el nombre de la fila no existirá en el mapa
        $import = new PositionAuthorityImport(institutionId: (string) $institution->id);

        $filas = collect([
            collect([
                'nombre_cargo' => 'Cargo Inexistente Para Autoridades',
                'autoridad'    => 'Autoridad sin cargo asociado',
            ]),
        ]);

        $import->collection($filas);

        expect($import->skipped)->toBe(1);
        expect($import->imported)->toBe(0);

        $this->assertDatabaseMissing('position_authorities', [
            'description' => 'Autoridad sin cargo asociado',
        ]);
    });

    it('reimportar una autoridad soft-deleted la restaura', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        $cargo = Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Director Académico',
            'is_active'      => true,
        ]);

        // Crear la autoridad y luego eliminarla lógicamente
        $autoridad = PositionAuthority::create([
            'position_id' => $cargo->id,
            'description' => 'Firmar actas de grado y diplomas institucionales',
        ]);
        $autoridad->delete();

        $this->assertSoftDeleted('position_authorities', [
            'id' => $autoridad->id,
        ]);

        $import = new PositionAuthorityImport(institutionId: (string) $institution->id);

        // Reimportar la misma autoridad
        $filas = collect([
            collect([
                'nombre_cargo' => 'Director Académico',
                'autoridad'    => 'Firmar actas de grado y diplomas institucionales',
            ]),
        ]);

        $import->collection($filas);

        // El registro debe haberse restaurado, no duplicado
        expect(
            PositionAuthority::where('position_id', $cargo->id)
                ->where('description', 'Firmar actas de grado y diplomas institucionales')
                ->count()
        )->toBe(1);

        expect($import->imported)->toBe(1);
        expect($import->skipped)->toBe(0);
    });

    it('fila con autoridad vacía es omitida', function (): void {
        [, $institution] = contextoImportacionCargos('rh-manager');

        $cargo = Position::create([
            'institution_id' => $institution->id,
            'name'           => 'Coordinador de Bienestar',
            'is_active'      => true,
        ]);

        $import = new PositionAuthorityImport(institutionId: (string) $institution->id);

        $filas = collect([
            collect([
                'nombre_cargo' => 'Coordinador de Bienestar',
                'autoridad'    => '   ', // solo espacios en blanco
            ]),
        ]);

        $import->collection($filas);

        expect($import->skipped)->toBe(1);
        expect($import->imported)->toBe(0);

        $this->assertDatabaseMissing('position_authorities', [
            'position_id' => $cargo->id,
        ]);
    });
});
