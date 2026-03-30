<?php

declare(strict_types=1);

use App\Imports\RH\CommittedValueImport;
use App\Imports\RH\ContractImport;
use App\Jobs\RH\ImportContractsJob;
use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers locales
// ---------------------------------------------------------------------------

/**
 * Crea institución, usuario con el rol indicado, 2 colaboradores con
 * document_number conocido y 2 tipos de contrato con name conocido.
 */
function contextoImportContratos(string $rol = 'rh-manager'): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    $documentType = DocumentType::factory()->create([
        'institution_id' => $institution->id,
        'code' => 'CC',
        'name' => 'Cédula de Ciudadanía',
    ]);

    $status = CollaboratorStatus::factory()->activo()->create([
        'institution_id' => $institution->id,
    ]);

    $collaborator1 = Collaborator::factory()->create([
        'institution_id' => $institution->id,
        'document_type_id' => $documentType->id,
        'document_number' => '11111111',
        'status_id' => $status->id,
        'type' => 'Contratista',
    ]);

    $collaborator2 = Collaborator::factory()->create([
        'institution_id' => $institution->id,
        'document_type_id' => $documentType->id,
        'document_number' => '22222222',
        'status_id' => $status->id,
        'type' => 'Contratista',
    ]);

    $contractType1 = ContractType::factory()->create([
        'institution_id' => $institution->id,
        'code' => 'OPS',
        'name' => 'OPS',
    ]);

    $contractType2 = ContractType::factory()->create([
        'institution_id' => $institution->id,
        'code' => 'CPS',
        'name' => 'Prestador de Servicios',
    ]);

    return compact('institution', 'user', 'collaborator1', 'collaborator2', 'contractType1', 'contractType2');
}

/**
 * Genera un archivo Excel en memoria con dos hojas:
 *  - "Contratos" con las filas indicadas
 *  - "Valores_comprometidos" con los valores comprometidos indicados
 *
 * @param  array<int, array<string, mixed>>  $filas
 * @param  array<int, array<string, mixed>>  $valoresComprometidos
 */
function crearExcelContratos(array $filas = [], array $valoresComprometidos = []): \Illuminate\Http\UploadedFile
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;

    // ── Hoja 1: Contratos ────────────────────────────────────────────────────
    $sheetContratos = $spreadsheet->getActiveSheet();
    $sheetContratos->setTitle('Contratos');

    $headersContratos = [
        'documento_colaborador',
        'tipo_contrato',
        'fecha_inicio_ddmmyyyy',
        'fecha_fin_ddmmyyyy',
        'objeto',
        'obligaciones',
        'honorarios',
        'salario',
        'correo_cargo',
        'estado',
        'num_contrato_solo_2019',
        'codigo_contrato_solo_2019',
    ];

    $sheetContratos->fromArray($headersContratos, null, 'A1');

    foreach ($filas as $rowIndex => $fila) {
        $rowData = [];
        foreach ($headersContratos as $col) {
            $rowData[] = $fila[$col] ?? '';
        }
        $sheetContratos->fromArray($rowData, null, 'A'.($rowIndex + 2));
    }

    // ── Hoja 2: Valores_comprometidos ────────────────────────────────────────
    $sheetValores = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Valores_comprometidos');
    $spreadsheet->addSheet($sheetValores);

    $headersValores = ['codigo_contrato', 'cuenta_contable', 'centro_de_costo', 'valor'];
    $sheetValores->fromArray($headersValores, null, 'A1');

    foreach ($valoresComprometidos as $rowIndex => $valor) {
        $rowData = [];
        foreach ($headersValores as $col) {
            $rowData[] = $valor[$col] ?? '';
        }
        $sheetValores->fromArray($rowData, null, 'A'.($rowIndex + 2));
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $path = sys_get_temp_dir().'/test_contratos_'.uniqid().'.xlsx';
    $writer->save($path);

    return new \Illuminate\Http\UploadedFile(
        $path,
        'test_contratos.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

/**
 * Retorna un array base con una fila de contrato válida para los campos dados.
 *
 * @param  array<string, mixed>  $sobreescribir
 */
function filaContratoBase(array $sobreescribir = []): array
{
    return array_merge([
        'documento_colaborador' => '11111111',
        'tipo_contrato' => 'OPS',
        'fecha_inicio_ddmmyyyy' => '01/03/2022',
        'fecha_fin_ddmmyyyy' => '31/12/2022',
        'objeto' => 'Prestación de servicios profesionales',
        'obligaciones' => 'Cumplir con las tareas asignadas',
        'honorarios' => '3000000',
        'salario' => '0',
        'correo_cargo' => 'ops@test.co',
        'estado' => 'Vigente',
        'num_contrato_solo_2019' => '001',
        'codigo_contrato_solo_2019' => '001-2022',
    ], $sobreescribir);
}

// ---------------------------------------------------------------------------
// Suite principal
// ---------------------------------------------------------------------------

describe('Importación masiva de contratos', function (): void {

    // -----------------------------------------------------------------------
    // Acceso y autorización
    // -----------------------------------------------------------------------

    describe('Acceso y autorización', function (): void {

        it('rh-manager puede acceder a la página de importación', function (): void {
            $ctx = contextoImportContratos('rh-manager');

            $this->actingAs($ctx['user'])
                ->get(route('rh.contratos.importar'))
                ->assertOk();
        });

        it('admin puede acceder a la página de importación', function (): void {
            $ctx = contextoImportContratos('admin');

            $this->actingAs($ctx['user'])
                ->get(route('rh.contratos.importar'))
                ->assertOk();
        });

        it('rh-viewer NO puede acceder (403)', function (): void {
            $ctx = contextoImportContratos('rh-viewer');

            $this->actingAs($ctx['user'])
                ->get(route('rh.contratos.importar'))
                ->assertForbidden();
        });

        it('usuario no autenticado es redirigido al login', function (): void {
            $this->get(route('rh.contratos.importar'))
                ->assertRedirect(route('login'));
        });

        it('contractor-manager puede acceder', function (): void {
            $ctx = contextoImportContratos('contractor-manager');

            $this->actingAs($ctx['user'])
                ->get(route('rh.contratos.importar'))
                ->assertOk();
        });

    });

    // -----------------------------------------------------------------------
    // Descarga de plantilla
    // -----------------------------------------------------------------------

    describe('Descarga de plantilla', function (): void {

        it('rh-manager puede descargar la plantilla', function (): void {
            $ctx = contextoImportContratos('rh-manager');

            $this->actingAs($ctx['user'])
                ->get(route('rh.contratos.plantilla'))
                ->assertOk()
                ->assertHeader(
                    'Content-Type',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                );
        });

        it('la respuesta es un archivo xlsx', function (): void {
            $ctx = contextoImportContratos('rh-manager');

            $response = $this->actingAs($ctx['user'])
                ->get(route('rh.contratos.plantilla'));

            $response->assertOk();
            $contentType = $response->headers->get('Content-Type');
            expect($contentType)->toContain('spreadsheetml');
        });

        it('rh-viewer puede descargar la plantilla (ruta sin policy)', function (): void {
            // La ruta de plantilla solo exige autenticación, no policy específica.
            // Cualquier usuario autenticado puede descargar la plantilla.
            $ctx = contextoImportContratos('rh-viewer');

            $this->actingAs($ctx['user'])
                ->get(route('rh.contratos.plantilla'))
                ->assertOk();
        });

    });

    // -----------------------------------------------------------------------
    // Validaciones del FormRequest
    // -----------------------------------------------------------------------

    describe('Validaciones del FormRequest', function (): void {

        it('rechaza cuando no se envía archivo', function (): void {
            $ctx = contextoImportContratos('rh-manager');

            $this->actingAs($ctx['user'])
                ->post(route('rh.contratos.importar.store'), [])
                ->assertSessionHasErrors('archivo');
        });

        it('rechaza archivo que no es xlsx', function (): void {
            $ctx = contextoImportContratos('rh-manager');

            $archivoInvalido = \Illuminate\Http\UploadedFile::fake()->create('datos.pdf', 100, 'application/pdf');

            $this->actingAs($ctx['user'])
                ->post(route('rh.contratos.importar.store'), [
                    'archivo' => $archivoInvalido,
                ])
                ->assertSessionHasErrors('archivo');
        });

        it('rechaza sin autenticación', function (): void {
            $archivo = crearExcelContratos([filaContratoBase()]);

            $this->post(route('rh.contratos.importar.store'), [
                'archivo' => $archivo,
            ])->assertRedirect(route('login'));
        });

        it('rh-viewer NO puede hacer POST (403)', function (): void {
            $ctx = contextoImportContratos('rh-viewer');
            $archivo = crearExcelContratos([filaContratoBase()]);

            $this->actingAs($ctx['user'])
                ->post(route('rh.contratos.importar.store'), [
                    'archivo' => $archivo,
                ])
                ->assertForbidden();
        });

    });

    // -----------------------------------------------------------------------
    // Encolamiento del Job
    // -----------------------------------------------------------------------

    describe('Encolamiento del Job', function (): void {

        it('importar archivo válido encola ImportContractsJob', function (): void {
            Bus::fake();

            $ctx = contextoImportContratos('rh-manager');
            $archivo = crearExcelContratos([filaContratoBase()]);

            $this->actingAs($ctx['user'])
                ->post(route('rh.contratos.importar.store'), [
                    'archivo' => $archivo,
                ])
                ->assertRedirect(route('rh.contratos.importar'));

            Bus::assertDispatched(ImportContractsJob::class);
        });

        it('el job se despacha con el institutionId del usuario autenticado', function (): void {
            Bus::fake();

            $ctx = contextoImportContratos('rh-manager');
            $archivo = crearExcelContratos([filaContratoBase()]);

            $this->actingAs($ctx['user'])
                ->post(route('rh.contratos.importar.store'), [
                    'archivo' => $archivo,
                ]);

            Bus::assertDispatched(ImportContractsJob::class, function (ImportContractsJob $job) use ($ctx): bool {
                return str_contains(serialize($job), $ctx['institution']->id);
            });
        });

    });

    // -----------------------------------------------------------------------
    // ContractImport — contratos históricos (antes de 2019)
    // -----------------------------------------------------------------------

    describe('ContractImport — contratos históricos (antes 2019)', function (): void {

        it('importa contrato histórico sin num_contrato ni codigo_contrato', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect([
                    'documento_colaborador' => '11111111',
                    'tipo_contrato' => 'OPS',
                    'fecha_inicio_ddmmyyyy' => '15/06/2015',
                    'fecha_fin_ddmmyyyy' => '31/12/2015',
                    'objeto' => 'Servicios profesionales históricos',
                    'obligaciones' => '',
                    'honorarios' => '2000000',
                    'salario' => '0',
                    'correo_cargo' => '',
                    'estado' => 'Terminado',
                    'num_contrato_solo_2019' => '',
                    'codigo_contrato_solo_2019' => '',
                ]),
            ]);

            $import->collection($rows);

            expect($import->getImported())->toBe(1);
            expect($import->getSkipped())->toBe(0);
            expect(
                Contract::where('institution_id', $ctx['institution']->id)
                    ->where('collaborator_id', $ctx['collaborator1']->id)
                    ->whereDate('start_date', '2015-06-15')
                    ->exists()
            )->toBeTrue();
        });

        it('contrato histórico acepta num_contrato vacío', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect([
                    'documento_colaborador' => '11111111',
                    'tipo_contrato' => 'OPS',
                    'fecha_inicio_ddmmyyyy' => '01/01/2010',
                    'fecha_fin_ddmmyyyy' => '',
                    'objeto' => 'Contrato sin número',
                    'obligaciones' => '',
                    'honorarios' => '0',
                    'salario' => '1160000',
                    'correo_cargo' => '',
                    'estado' => 'Terminado',
                    'num_contrato_solo_2019' => '',
                    'codigo_contrato_solo_2019' => '',
                ]),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(0);
            expect($import->getImported())->toBe(1);
        });

        it('crea el contrato en la base de datos con el collaborator_id correcto', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect([
                    'documento_colaborador' => '22222222',
                    'tipo_contrato' => 'Prestador de Servicios',
                    'fecha_inicio_ddmmyyyy' => '01/03/2018',
                    'fecha_fin_ddmmyyyy' => '30/06/2018',
                    'objeto' => 'Objeto del contrato histórico',
                    'obligaciones' => '',
                    'honorarios' => '1500000',
                    'salario' => '0',
                    'correo_cargo' => '',
                    'estado' => 'Liquidado',
                    'num_contrato_solo_2019' => '',
                    'codigo_contrato_solo_2019' => '',
                ]),
            ]);

            $import->collection($rows);

            $contrato = Contract::where('institution_id', $ctx['institution']->id)
                ->where('collaborator_id', $ctx['collaborator2']->id)
                ->first();

            expect($contrato)->not->toBeNull();
            expect($contrato->collaborator_id)->toBe($ctx['collaborator2']->id);
            expect($contrato->contract_number)->toBeNull();
            expect($contrato->contract_code)->toBeNull();
        });

    });

    // -----------------------------------------------------------------------
    // ContractImport — contratos intermedios (2019–2024)
    // -----------------------------------------------------------------------

    describe('ContractImport — contratos intermedios (2019-2024)', function (): void {

        it('importa contrato intermedio con num_contrato y codigo_contrato', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/03/2022',
                    'fecha_fin_ddmmyyyy' => '31/12/2022',
                    'num_contrato_solo_2019' => '042',
                    'codigo_contrato_solo_2019' => '042-2022',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getImported())->toBe(1);
            expect($import->getSkipped())->toBe(0);
            expect(
                Contract::where('institution_id', $ctx['institution']->id)
                    ->where('contract_code', '042-2022')
                    ->exists()
            )->toBeTrue();
        });

        it('rechaza contrato intermedio sin num_contrato', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/06/2021',
                    'fecha_fin_ddmmyyyy' => '31/12/2021',
                    'num_contrato_solo_2019' => '',
                    'codigo_contrato_solo_2019' => '010-2021',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            expect($import->getImported())->toBe(0);

            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('num_contrato');
        });

        it('rechaza contrato intermedio con num_contrato de formato inválido (1 dígito)', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/02/2023',
                    'fecha_fin_ddmmyyyy' => '30/11/2023',
                    'num_contrato_solo_2019' => '5',
                    'codigo_contrato_solo_2019' => '005-2023',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('num_contrato');
        });

        it('rechaza contrato intermedio con num_contrato de formato inválido (4 dígitos)', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/02/2023',
                    'fecha_fin_ddmmyyyy' => '30/11/2023',
                    'num_contrato_solo_2019' => '0050',
                    'codigo_contrato_solo_2019' => '005-2023',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('num_contrato');
        });

        it('rechaza contrato intermedio sin codigo_contrato', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/04/2020',
                    'fecha_fin_ddmmyyyy' => '31/12/2020',
                    'num_contrato_solo_2019' => '015',
                    'codigo_contrato_solo_2019' => '',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('codigo_contrato');
        });

        it('rechaza codigo_contrato con formato inválido', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/05/2021',
                    'fecha_fin_ddmmyyyy' => '30/11/2021',
                    'num_contrato_solo_2019' => '008',
                    'codigo_contrato_solo_2019' => 'CONT-2021',  // Formato inválido: debe ser NNN-AAAA
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('codigo_contrato');
        });

    });

    // -----------------------------------------------------------------------
    // ContractImport — contratos recientes (2025+)
    // -----------------------------------------------------------------------

    describe('ContractImport — contratos recientes (2025+)', function (): void {

        it('importa contrato reciente con num_contrato y codigo_contrato', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/02/2025',
                    'fecha_fin_ddmmyyyy' => '31/12/2025',
                    'num_contrato_solo_2019' => '003',
                    'codigo_contrato_solo_2019' => '003-2025',
                    'estado' => 'Vigente',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getImported())->toBe(1);
            expect($import->getSkipped())->toBe(0);
            expect(
                Contract::where('institution_id', $ctx['institution']->id)
                    ->where('contract_code', '003-2025')
                    ->exists()
            )->toBeTrue();
        });

        it('expone el contract_code en getCreatedContractCodes()', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '15/01/2025',
                    'fecha_fin_ddmmyyyy' => '14/01/2026',
                    'num_contrato_solo_2019' => '007',
                    'codigo_contrato_solo_2019' => '007-2025',
                    'estado' => 'Vigente',
                ])),
            ]);

            $import->collection($rows);

            $mapa = $import->getCreatedContractCodes();
            expect($mapa)->toHaveKey('007-2025');

            $contratoId = $mapa['007-2025'];
            expect(Contract::find($contratoId))->not->toBeNull();
        });

    });

    // -----------------------------------------------------------------------
    // ContractImport — resolución de FKs
    // -----------------------------------------------------------------------

    describe('ContractImport — resolución de FKs', function (): void {

        it('rechaza contrato con document_number de colaborador inexistente', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'documento_colaborador' => '99999999',  // No existe en la institución
                    'fecha_inicio_ddmmyyyy' => '01/03/2022',
                    'fecha_fin_ddmmyyyy' => '31/12/2022',
                    'num_contrato_solo_2019' => '099',
                    'codigo_contrato_solo_2019' => '099-2022',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            expect($import->getImported())->toBe(0);

            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('documento_colaborador');
        });

        it('rechaza contrato con tipo_contrato inexistente', function (): void {
            $ctx = contextoImportContratos();

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'tipo_contrato' => 'Tipo Inexistente',
                    'fecha_inicio_ddmmyyyy' => '01/06/2022',
                    'fecha_fin_ddmmyyyy' => '30/11/2022',
                    'num_contrato_solo_2019' => '055',
                    'codigo_contrato_solo_2019' => '055-2022',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('tipo_contrato');
        });

        it('no puede resolver colaborador de otra institución', function (): void {
            $ctx = contextoImportContratos();

            // Colaborador en una institución diferente con el mismo document_number
            $otraInstitucion = Institution::factory()->create();
            $otroStatus = CollaboratorStatus::factory()->activo()->create([
                'institution_id' => $otraInstitucion->id,
            ]);
            $otroTipoDoc = DocumentType::factory()->create([
                'institution_id' => $otraInstitucion->id,
                'code' => 'CC',
            ]);

            Collaborator::factory()->create([
                'institution_id' => $otraInstitucion->id,
                'document_type_id' => $otroTipoDoc->id,
                'document_number' => '33333333',
                'status_id' => $otroStatus->id,
            ]);

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,  // Institución original
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'documento_colaborador' => '33333333',  // Solo existe en otra institución
                    'fecha_inicio_ddmmyyyy' => '01/07/2022',
                    'fecha_fin_ddmmyyyy' => '31/12/2022',
                    'num_contrato_solo_2019' => '077',
                    'codigo_contrato_solo_2019' => '077-2022',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            expect($import->getImported())->toBe(0);
        });

    });

    // -----------------------------------------------------------------------
    // ContractImport — overwrite
    // -----------------------------------------------------------------------

    describe('ContractImport — overwrite', function (): void {

        it('omite duplicado por contract_code cuando overwrite=false', function (): void {
            $ctx = contextoImportContratos();

            // Contrato ya existente con el mismo código
            Contract::factory()->create([
                'institution_id' => $ctx['institution']->id,
                'collaborator_id' => $ctx['collaborator1']->id,
                'contract_type_id' => $ctx['contractType1']->id,
                'contract_code' => '010-2023',
                'contract_number' => '010',
                'start_date' => '2023-01-01',
                'object' => 'Objeto original',
                'status' => 'Vigente',
            ]);

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/01/2023',
                    'fecha_fin_ddmmyyyy' => '31/12/2023',
                    'num_contrato_solo_2019' => '010',
                    'codigo_contrato_solo_2019' => '010-2023',
                    'objeto' => 'Objeto nuevo que no debe guardarse',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            expect($import->getImported())->toBe(0);

            // El objeto original debe seguir intacto
            $contrato = Contract::where('contract_code', '010-2023')
                ->where('institution_id', $ctx['institution']->id)
                ->first();
            expect($contrato->object)->toBe('Objeto original');
        });

        it('actualiza contrato por contract_code cuando overwrite=true', function (): void {
            $ctx = contextoImportContratos();

            $contratoExistente = Contract::factory()->create([
                'institution_id' => $ctx['institution']->id,
                'collaborator_id' => $ctx['collaborator1']->id,
                'contract_type_id' => $ctx['contractType1']->id,
                'contract_code' => '020-2023',
                'contract_number' => '020',
                'start_date' => '2023-02-01',
                'object' => 'Objeto antes de actualizar',
                'status' => 'Vigente',
            ]);

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: true,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/02/2023',
                    'fecha_fin_ddmmyyyy' => '31/12/2023',
                    'num_contrato_solo_2019' => '020',
                    'codigo_contrato_solo_2019' => '020-2023',
                    'objeto' => 'Objeto actualizado correctamente',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getUpdated())->toBe(1);
            expect($import->getImported())->toBe(0);

            $contratoExistente->refresh();
            expect($contratoExistente->object)->toBe('Objeto actualizado correctamente');
        });

    });

    // -----------------------------------------------------------------------
    // CommittedValueImport
    // -----------------------------------------------------------------------

    describe('CommittedValueImport', function (): void {

        it('importa valores comprometidos para un contrato reciente', function (): void {
            $ctx = contextoImportContratos();

            $contrato = Contract::factory()->create([
                'institution_id' => $ctx['institution']->id,
                'collaborator_id' => $ctx['collaborator1']->id,
                'contract_type_id' => $ctx['contractType1']->id,
                'contract_code' => '005-2024',
                'contract_number' => '005',
                'start_date' => '2024-01-01',
                'status' => 'Vigente',
            ]);

            $import = new CommittedValueImport(
                institutionId: $ctx['institution']->id,
                contractCodeMap: ['005-2024' => $contrato->id],
            );

            $rows = collect([
                collect([
                    'codigo_contrato' => '005-2024',
                    'cuenta_contable' => '511000',
                    'centro_de_costo' => 'CC-001',
                    'valor' => '5000000',
                ]),
            ]);

            $import->collection($rows);

            expect($import->getImported())->toBe(1);
            expect($import->getSkipped())->toBe(0);

            expect(
                CommittedValue::where('contract_id', $contrato->id)
                    ->where('accounting_account', '511000')
                    ->where('amount', 5000000)
                    ->exists()
            )->toBeTrue();
        });

        it('rechaza valor comprometido con codigo_contrato no mapeado', function (): void {
            $ctx = contextoImportContratos();

            $import = new CommittedValueImport(
                institutionId: $ctx['institution']->id,
                contractCodeMap: [],  // Mapa vacío
            );

            $rows = collect([
                collect([
                    'codigo_contrato' => '999-2025',
                    'cuenta_contable' => '511000',
                    'centro_de_costo' => 'CC-001',
                    'valor' => '1000000',
                ]),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            expect($import->getImported())->toBe(0);

            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('codigo_contrato');
        });

        it('rechaza valor comprometido negativo', function (): void {
            $ctx = contextoImportContratos();

            $contrato = Contract::factory()->create([
                'institution_id' => $ctx['institution']->id,
                'collaborator_id' => $ctx['collaborator1']->id,
                'contract_type_id' => $ctx['contractType1']->id,
                'contract_code' => '006-2024',
                'contract_number' => '006',
                'start_date' => '2024-03-01',
                'status' => 'Vigente',
            ]);

            $import = new CommittedValueImport(
                institutionId: $ctx['institution']->id,
                contractCodeMap: ['006-2024' => $contrato->id],
            );

            $rows = collect([
                collect([
                    'codigo_contrato' => '006-2024',
                    'cuenta_contable' => '511000',
                    'centro_de_costo' => 'CC-002',
                    'valor' => '-500000',  // Negativo: debe rechazarse
                ]),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            expect($import->getImported())->toBe(0);

            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('valor');
        });

    });

    // -----------------------------------------------------------------------
    // ImportContractsJob
    // -----------------------------------------------------------------------

    describe('ImportContractsJob', function (): void {

        it('el job guarda el resultado en cache', function (): void {
            Storage::fake('local');

            $ctx = contextoImportContratos();

            // Generar un Excel real y guardarlo en el storage falso.
            // El job llama Storage::path('private/' . $filePath), por eso el archivo
            // debe almacenarse bajo la clave 'private/<relativePath>' en el disco local.
            $archivo = crearExcelContratos([
                filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/04/2022',
                    'fecha_fin_ddmmyyyy' => '31/12/2022',
                    'num_contrato_solo_2019' => '030',
                    'codigo_contrato_solo_2019' => '030-2022',
                ]),
            ]);

            $relativePath = 'imports/contratos/'.$ctx['user']->id.'/test_job_'.uniqid().'.xlsx';
            Storage::disk('local')->put($relativePath, file_get_contents($archivo->getRealPath()));

            $job = new ImportContractsJob(
                filePath: $relativePath,
                institutionId: $ctx['institution']->id,
                overwrite: false,
                userId: $ctx['user']->id,
            );

            $job->handle();

            $resultado = Cache::get("import_contracts_result_{$ctx['user']->id}");

            expect($resultado)->not->toBeNull();
            expect($resultado)->toHaveKeys(['imported', 'updated', 'skipped', 'completado_at']);
        });

        it('el job limpia el archivo temporal después de importar', function (): void {
            Storage::fake('local');

            $ctx = contextoImportContratos();

            $archivo = crearExcelContratos([
                filaContratoBase([
                    'fecha_inicio_ddmmyyyy' => '01/05/2022',
                    'fecha_fin_ddmmyyyy' => '31/12/2022',
                    'num_contrato_solo_2019' => '031',
                    'codigo_contrato_solo_2019' => '031-2022',
                ]),
            ]);

            $relativePath = 'imports/contratos/'.$ctx['user']->id.'/limpiar_'.uniqid().'.xlsx';
            Storage::disk('local')->put($relativePath, file_get_contents($archivo->getRealPath()));

            expect(Storage::disk('local')->exists($relativePath))->toBeTrue();

            $job = new ImportContractsJob(
                filePath: $relativePath,
                institutionId: $ctx['institution']->id,
                overwrite: false,
                userId: $ctx['user']->id,
            );

            $job->handle();

            // El job borra $filePath del disco local
            expect(Storage::disk('local')->exists($relativePath))->toBeFalse();
        });

        it('el job guarda error en cache cuando falla', function (): void {
            $ctx = contextoImportContratos();

            $job = new ImportContractsJob(
                filePath: 'ruta/inexistente/archivo.xlsx',
                institutionId: $ctx['institution']->id,
                overwrite: false,
                userId: $ctx['user']->id,
            );

            $excepcion = new \RuntimeException('Archivo no encontrado');
            $job->failed($excepcion);

            $resultado = Cache::get("import_contracts_result_{$ctx['user']->id}");

            expect($resultado)->not->toBeNull();
            expect($resultado)->toHaveKey('error');
            expect($resultado['error'])->toContain('Archivo no encontrado');
        });

    });

    // -----------------------------------------------------------------------
    // Multi-tenant
    // -----------------------------------------------------------------------

    describe('Multi-tenant', function (): void {

        it('no puede resolver colaborador de otra institución', function (): void {
            $ctx = contextoImportContratos();

            // Crear colaborador con el mismo documento en otra institución
            $otraInstitucion = Institution::factory()->create();
            $otroStatus = CollaboratorStatus::factory()->activo()->create([
                'institution_id' => $otraInstitucion->id,
            ]);
            $otroTipoDoc = DocumentType::factory()->create([
                'institution_id' => $otraInstitucion->id,
                'code' => 'CC',
            ]);
            Collaborator::factory()->create([
                'institution_id' => $otraInstitucion->id,
                'document_type_id' => $otroTipoDoc->id,
                'document_number' => '55555555',
                'status_id' => $otroStatus->id,
            ]);

            // El import usa la institución original, donde '55555555' no existe
            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'documento_colaborador' => '55555555',
                    'fecha_inicio_ddmmyyyy' => '01/08/2022',
                    'fecha_fin_ddmmyyyy' => '31/12/2022',
                    'num_contrato_solo_2019' => '088',
                    'codigo_contrato_solo_2019' => '088-2022',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            expect($import->getImported())->toBe(0);
            expect(
                Contract::where('institution_id', $ctx['institution']->id)->exists()
            )->toBeFalse();
        });

        it('no puede resolver tipo de contrato de otra institución', function (): void {
            $ctx = contextoImportContratos();

            // Tipo de contrato en otra institución
            $otraInstitucion = Institution::factory()->create();
            ContractType::factory()->create([
                'institution_id' => $otraInstitucion->id,
                'code' => 'OTRO',
                'name' => 'Tipo Exclusivo Otra Institución',
            ]);

            $import = new ContractImport(
                institutionId: $ctx['institution']->id,
                overwrite: false,
            );

            $rows = collect([
                collect(filaContratoBase([
                    'tipo_contrato' => 'Tipo Exclusivo Otra Institución',  // Solo existe en la otra institución
                    'fecha_inicio_ddmmyyyy' => '01/09/2022',
                    'fecha_fin_ddmmyyyy' => '31/12/2022',
                    'num_contrato_solo_2019' => '090',
                    'codigo_contrato_solo_2019' => '090-2022',
                ])),
            ]);

            $import->collection($rows);

            expect($import->getSkipped())->toBe(1);
            expect($import->getImported())->toBe(0);

            $errores = $import->getRowErrors();
            $camposConError = array_column($errores, 'campo');
            expect($camposConError)->toContain('tipo_contrato');
        });

    });

});
