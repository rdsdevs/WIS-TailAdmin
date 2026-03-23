<?php

declare(strict_types=1);

use App\Imports\RH\CollaboratorImport;
use App\Jobs\RH\ImportCollaboratorsJob;
use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers locales
// ---------------------------------------------------------------------------

/**
 * Crea institución, tipos de documento (CC y NIT), estado Activo y usuario con el rol indicado.
 */
function contextoImportacion(string $rol): array
{
    $institution = Institution::factory()->create();
    $user        = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    $documentTypeCC = DocumentType::factory()->create([
        'institution_id' => $institution->id,
        'code'           => 'CC',
        'name'           => 'Cédula de Ciudadanía',
    ]);

    $documentTypeNIT = DocumentType::factory()->create([
        'institution_id' => $institution->id,
        'code'           => 'NIT',
        'name'           => 'Número de Identificación Tributaria',
    ]);

    $status = CollaboratorStatus::factory()->activo()->create([
        'institution_id' => $institution->id,
    ]);

    return [$user, $institution, $documentTypeCC, $documentTypeNIT, $status];
}

/**
 * Genera un archivo Excel en memoria con datos de persona natural.
 */
function crearExcelEmpleado(array $campos = []): \Illuminate\Http\UploadedFile
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();

    $headers = [
        'tipo_documento', 'numero_documento', 'fecha_expedicion',
        'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido',
        'fecha_nacimiento', 'genero', 'correo', 'telefono', 'direccion', 'estado',
    ];

    $defaults = [
        'CC', '1234567890', '15/03/1990',
        'ANA', 'ISABEL', 'REYES', 'TORRES',
        '10/01/1990', 'F', 'ana@test.co', '3001234567', 'Calle 10', 'Activo',
    ];

    // Mezclar defaults con los campos personalizados en orden de $headers
    $fila = $defaults;
    foreach ($campos as $columna => $valor) {
        $idx = array_search($columna, $headers, true);
        if ($idx !== false) {
            $fila[$idx] = $valor;
        }
    }

    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray($fila, null, 'A2');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $path   = sys_get_temp_dir().'/test_import_'.uniqid().'.xlsx';
    $writer->save($path);

    return new \Illuminate\Http\UploadedFile(
        $path,
        'test.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

/**
 * Genera un archivo Excel en memoria con datos de empresa (persona jurídica).
 */
function crearExcelEmpresa(): \Illuminate\Http\UploadedFile
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();

    $headers = [
        'tipo_documento', 'numero_documento', 'fecha_expedicion',
        'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido',
        'fecha_nacimiento', 'genero', 'correo', 'telefono', 'direccion', 'estado',
        'es_empresa', 'razon_social', 'nit', 'representante_legal',
    ];

    $fila = [
        '', '', '', '', '', '', '', '', '',
        'empresa@test.co', '3001234567', 'Calle 1', 'Activo',
        'SI', 'CONSULTORES LTDA', '900123456-1', 'JUAN PEREZ',
    ];

    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray($fila, null, 'A2');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $path   = sys_get_temp_dir().'/test_empresa_'.uniqid().'.xlsx';
    $writer->save($path);

    return new \Illuminate\Http\UploadedFile(
        $path,
        'test.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

// ---------------------------------------------------------------------------
// Suite principal
// ---------------------------------------------------------------------------

describe('Importación masiva de colaboradores', function (): void {

    // -----------------------------------------------------------------------
    // Acceso a la página de importación
    // -----------------------------------------------------------------------

    it('rh-manager puede acceder a la página de importación', function (): void {
        [$user] = contextoImportacion('rh-manager');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.importar'))
            ->assertOk();
    });

    it('rh-viewer no puede acceder a la página de importación', function (): void {
        [$user] = contextoImportacion('rh-viewer');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.importar'))
            ->assertForbidden();
    });

    it('usuario no autenticado es redirigido al login', function (): void {
        $this->get(route('rh.colaboradores.importar'))
            ->assertRedirect(route('login'));
    });

    // -----------------------------------------------------------------------
    // Descarga de plantillas
    // -----------------------------------------------------------------------

    it('rh-manager puede descargar la plantilla de empleados', function (): void {
        [$user] = contextoImportacion('rh-manager');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.plantilla', ['tipo' => 'empleados']))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    });

    it('rh-manager puede descargar la plantilla de contratistas', function (): void {
        [$user] = contextoImportacion('rh-manager');

        $this->actingAs($user)
            ->get(route('rh.colaboradores.plantilla', ['tipo' => 'contratistas']))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    });

    // -----------------------------------------------------------------------
    // Autorización por tipo en la importación (POST)
    // -----------------------------------------------------------------------

    it('contractor-manager no puede importar empleados', function (): void {
        Bus::fake();
        [$user] = contextoImportacion('contractor-manager');
        $archivo = crearExcelEmpleado();

        $this->actingAs($user)
            ->post(route('rh.colaboradores.importar.store'), [
                'archivo' => $archivo,
                'tipo'    => 'Empleado',
            ])
            ->assertForbidden();
    });

    it('employee-manager no puede importar contratistas', function (): void {
        Bus::fake();
        [$user] = contextoImportacion('employee-manager');
        $archivo = crearExcelEmpleado();

        $this->actingAs($user)
            ->post(route('rh.colaboradores.importar.store'), [
                'archivo' => $archivo,
                'tipo'    => 'Contratista',
            ])
            ->assertForbidden();
    });

    // -----------------------------------------------------------------------
    // Encolamiento del Job
    // -----------------------------------------------------------------------

    it('importar empleados válidos encola el job correctamente', function (): void {
        Bus::fake();

        [$user] = contextoImportacion('rh-manager');
        $archivo = crearExcelEmpleado();

        $this->actingAs($user)
            ->post(route('rh.colaboradores.importar.store'), [
                'archivo' => $archivo,
                'tipo'    => 'Empleado',
            ])
            ->assertRedirect(route('rh.colaboradores.importar'));

        Bus::assertDispatched(ImportCollaboratorsJob::class);
    });

    it('importar contratistas válidos encola el job correctamente', function (): void {
        Bus::fake();

        [$user] = contextoImportacion('rh-manager');
        $archivo = crearExcelEmpleado(['correo' => 'contratista@test.co', 'numero_documento' => '9988776655']);

        $this->actingAs($user)
            ->post(route('rh.colaboradores.importar.store'), [
                'archivo' => $archivo,
                'tipo'    => 'Contratista',
            ])
            ->assertRedirect(route('rh.colaboradores.importar'));

        Bus::assertDispatched(ImportCollaboratorsJob::class, function (ImportCollaboratorsJob $job): bool {
            // Verificamos que el job lleva el tipo Contratista inspeccionando su payload serializado
            return str_contains(serialize($job), 'Contratista');
        });
    });

    // -----------------------------------------------------------------------
    // Validaciones del FormRequest
    // -----------------------------------------------------------------------

    it('rechaza archivo con extensión inválida', function (): void {
        [$user] = contextoImportacion('rh-manager');

        $archivoInvalido = \Illuminate\Http\UploadedFile::fake()->create('datos.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.importar.store'), [
                'archivo' => $archivoInvalido,
                'tipo'    => 'Empleado',
            ])
            ->assertSessionHasErrors('archivo');
    });

    it('rechaza cuando el tipo es inválido', function (): void {
        [$user] = contextoImportacion('rh-manager');
        $archivo = crearExcelEmpleado();

        $this->actingAs($user)
            ->post(route('rh.colaboradores.importar.store'), [
                'archivo' => $archivo,
                'tipo'    => 'Otro',
            ])
            ->assertSessionHasErrors('tipo');
    });

    it('rechaza cuando no se envía el archivo', function (): void {
        [$user] = contextoImportacion('rh-manager');

        $this->actingAs($user)
            ->post(route('rh.colaboradores.importar.store'), [
                'tipo' => 'Empleado',
            ])
            ->assertSessionHasErrors('archivo');
    });

    it('rechaza cuando no se envía el tipo', function (): void {
        [$user] = contextoImportacion('rh-manager');
        $archivo = crearExcelEmpleado();

        $this->actingAs($user)
            ->post(route('rh.colaboradores.importar.store'), [
                'archivo' => $archivo,
            ])
            ->assertSessionHasErrors('tipo');
    });

    // -----------------------------------------------------------------------
    // Lógica interna de CollaboratorImport (prueba de clase directa)
    // -----------------------------------------------------------------------

    it('CollaboratorImport importa persona natural correctamente', function (): void {
        $institution = Institution::factory()->create();

        DocumentType::factory()->create([
            'institution_id' => $institution->id,
            'code'           => 'CC',
            'name'           => 'Cédula de Ciudadanía',
        ]);

        CollaboratorStatus::factory()->activo()->create([
            'institution_id' => $institution->id,
        ]);

        $import = new CollaboratorImport(
            institutionId: $institution->id,
            type: 'Empleado',
            overwrite: false,
        );

        $collection = collect([
            collect([
                'tipo_documento'   => 'CC',
                'numero_documento' => '99887766',
                'fecha_expedicion' => '15/03/2005',
                'primer_nombre'    => 'CARLOS',
                'segundo_nombre'   => '',
                'primer_apellido'  => 'RAMIREZ',
                'segundo_apellido' => '',
                'fecha_nacimiento' => '01/01/1990',
                'genero'           => 'M',
                'correo'           => 'carlos@test.co',
                'telefono'         => '3001234567',
                'direccion'        => 'Calle 10',
                'estado'           => 'Activo',
                'es_empresa'       => 'NO',
            ]),
        ]);

        $import->collection($collection);

        expect(
            Collaborator::where('document_number', '99887766')
                ->where('institution_id', $institution->id)
                ->exists()
        )->toBeTrue();

        $colaborador = Collaborator::where('document_number', '99887766')->first();
        expect($colaborador->first_name)->toBe('CARLOS');
        expect($colaborador->first_surname)->toBe('RAMIREZ');
        expect($colaborador->type)->toBe('Empleado');
        expect($colaborador->is_company)->toBeFalse();
        expect($import->getImported())->toBe(1);
    });

    it('CollaboratorImport omite duplicado cuando overwrite es false', function (): void {
        $institution = Institution::factory()->create();

        $documentType = DocumentType::factory()->create([
            'institution_id' => $institution->id,
            'code'           => 'CC',
            'name'           => 'Cédula de Ciudadanía',
        ]);

        $status = CollaboratorStatus::factory()->activo()->create([
            'institution_id' => $institution->id,
        ]);

        // Colaborador ya existente en la base de datos
        Collaborator::factory()->create([
            'institution_id'   => $institution->id,
            'document_type_id' => $documentType->id,
            'document_number'  => '55443322',
            'status_id'        => $status->id,
            'type'             => 'Empleado',
        ]);

        $import = new CollaboratorImport(
            institutionId: $institution->id,
            type: 'Empleado',
            overwrite: false,
        );

        $collection = collect([
            collect([
                'tipo_documento'   => 'CC',
                'numero_documento' => '55443322',
                'fecha_expedicion' => '10/05/2000',
                'primer_nombre'    => 'LUCIA',
                'segundo_nombre'   => '',
                'primer_apellido'  => 'VARGAS',
                'segundo_apellido' => '',
                'fecha_nacimiento' => '15/07/1985',
                'genero'           => 'F',
                'correo'           => 'nuevo@test.co',
                'telefono'         => '3109876543',
                'direccion'        => 'Carrera 5',
                'estado'           => 'Activo',
                'es_empresa'       => 'NO',
            ]),
        ]);

        $import->collection($collection);

        // Solo debe existir el registro original, sin duplicados
        expect(
            Collaborator::where('document_number', '55443322')
                ->where('institution_id', $institution->id)
                ->count()
        )->toBe(1);

        expect($import->getSkipped())->toBe(1);
        expect($import->getImported())->toBe(0);
    });

    it('CollaboratorImport actualiza duplicado cuando overwrite es true', function (): void {
        $institution = Institution::factory()->create();

        $documentType = DocumentType::factory()->create([
            'institution_id' => $institution->id,
            'code'           => 'CC',
            'name'           => 'Cédula de Ciudadanía',
        ]);

        $status = CollaboratorStatus::factory()->activo()->create([
            'institution_id' => $institution->id,
        ]);

        $colaborador = Collaborator::factory()->create([
            'institution_id'   => $institution->id,
            'document_type_id' => $documentType->id,
            'document_number'  => '77665544',
            'status_id'        => $status->id,
            'type'             => 'Empleado',
            'email'            => 'original@test.co',
        ]);

        $import = new CollaboratorImport(
            institutionId: $institution->id,
            type: 'Empleado',
            overwrite: true,
        );

        $collection = collect([
            collect([
                'tipo_documento'   => 'CC',
                'numero_documento' => '77665544',
                'fecha_expedicion' => '20/06/2001',
                'primer_nombre'    => $colaborador->first_name,
                'segundo_nombre'   => '',
                'primer_apellido'  => $colaborador->first_surname,
                'segundo_apellido' => '',
                'fecha_nacimiento' => '22/03/1988',
                'genero'           => 'M',
                'correo'           => 'actualizado@test.co',
                'telefono'         => '3201234567',
                'direccion'        => 'Avenida 15',
                'estado'           => 'Activo',
                'es_empresa'       => 'NO',
            ]),
        ]);

        $import->collection($collection);

        $this->assertDatabaseHas('collaborators', [
            'id'    => $colaborador->id,
            'email' => 'actualizado@test.co',
        ]);

        expect($import->getUpdated())->toBe(1);
        expect($import->getImported())->toBe(0);
    });

    it('CollaboratorImport asigna NIT automáticamente para empresas', function (): void {
        $institution = Institution::factory()->create();

        $documentTypeNIT = DocumentType::factory()->create([
            'institution_id' => $institution->id,
            'code'           => 'NIT',
            'name'           => 'Número de Identificación Tributaria',
        ]);

        CollaboratorStatus::factory()->activo()->create([
            'institution_id' => $institution->id,
        ]);

        $import = new CollaboratorImport(
            institutionId: $institution->id,
            type: 'Contratista',
            overwrite: false,
        );

        $collection = collect([
            collect([
                'tipo_documento'      => '',
                'numero_documento'    => '',
                'fecha_expedicion'    => '',
                'primer_nombre'       => '',
                'segundo_nombre'      => '',
                'primer_apellido'     => '',
                'segundo_apellido'    => '',
                'fecha_nacimiento'    => '',
                'genero'              => '',
                'correo'              => 'empresa@test.co',
                'telefono'            => '3001234567',
                'direccion'           => 'Calle 1',
                'estado'              => 'Activo',
                'es_empresa'          => 'SI',
                'razon_social'        => 'CONSULTORES LTDA',
                'nit'                 => '900123456-1',
                'representante_legal' => 'JUAN PEREZ',
            ]),
        ]);

        $import->collection($collection);

        $empresa = Collaborator::where('document_number', '900123456-1')
            ->where('institution_id', $institution->id)
            ->first();

        expect($empresa)->not->toBeNull();
        expect($empresa->is_company)->toBeTrue();
        expect($empresa->company_name)->toBe('CONSULTORES LTDA');
        expect($empresa->document_type_id)->toBe($documentTypeNIT->id);
        expect($empresa->legal_representative)->toBe('JUAN PEREZ');
    });

});
