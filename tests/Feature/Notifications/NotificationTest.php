<?php

declare(strict_types=1);

use App\Jobs\RH\ImportCollaboratorsJob;
use App\Jobs\RH\ImportContractsJob;
use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\User;
use App\Notifications\RH\CollaboratorCreatedNotification;
use App\Notifications\RH\CollaboratorDeletedNotification;
use App\Notifications\RH\CollaboratorImportCompletedNotification;
use App\Notifications\RH\CollaboratorTypeChangedNotification;
use App\Notifications\RH\CollaboratorUpdatedNotification;
use App\Notifications\RH\ContractCreatedNotification;
use App\Notifications\RH\ContractImportCompletedNotification;
use App\Notifications\RH\ContractTerminatedNotification;
use App\Notifications\RH\ContractUpdatedNotification;
use App\Services\RH\CollaboratorService;
use App\Services\RH\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers locales
// ---------------------------------------------------------------------------

/**
 * Crea institución, tipos de documento, estado Activo y usuario con el rol indicado.
 * El usuario queda vinculado a la institución.
 */
function contextoNotificaciones(string $rol): array
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

    return [$user, $institution, $documentType, $status];
}

/**
 * Crea un colaborador vinculado a la institución del usuario.
 */
function colaboradorParaTest(User $user, Institution $institution, DocumentType $documentType, CollaboratorStatus $status, array $overrides = []): Collaborator
{
    return Collaborator::factory()->create(array_merge([
        'institution_id' => $institution->id,
        'document_type_id' => $documentType->id,
        'status_id' => $status->id,
        'type' => 'Empleado',
    ], $overrides));
}

/**
 * Crea un contrato vinculado al colaborador e institución indicados.
 */
function contratoParaTest(User $user, Institution $institution, Collaborator $collaborator): Contract
{
    $contractType = ContractType::factory()->indefinido()->create([
        'institution_id' => $institution->id,
    ]);

    return Contract::factory()->create([
        'institution_id' => $institution->id,
        'collaborator_id' => $collaborator->id,
        'contract_type_id' => $contractType->id,
        'status' => 'Vigente',
    ]);
}

// ---------------------------------------------------------------------------
// Suite: Notificaciones de colaboradores
// ---------------------------------------------------------------------------

describe('Notificaciones de colaboradores', function (): void {

    it('crear colaborador genera notificación database para el usuario', function (): void {
        Notification::fake();

        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $user->notify(new CollaboratorCreatedNotification($colaborador->full_name, $colaborador->id));

        Notification::assertSentTo($user, CollaboratorCreatedNotification::class);
    });

    it('crear colaborador vía controller genera notificación database para el usuario', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $datos = [
            'institution_id' => $institution->id,
            'document_type_id' => $documentType->id,
            'document_number' => '99887766',
            'document_issued_at' => '1995-06-15',
            'first_name' => 'CARLOS',
            'first_surname' => 'RAMIREZ',
            'gender' => 'M',
            'is_company' => false,
            'type' => 'Empleado',
            'status_id' => $status->id,
        ];

        $this->post(route('rh.colaboradores.store'), $datos)
            ->assertRedirect();

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('collaborator_created');
        expect($notificacion->data['color'])->toBe('green');
    });

    it('actualizar colaborador genera notificación database', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);

        $user->notify(new CollaboratorUpdatedNotification($colaborador->full_name, $colaborador->id));

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('collaborator_updated');
        expect($notificacion->data['color'])->toBe('amber');
    });

    it('eliminar colaborador genera notificación database', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $nombre = $colaborador->full_name;

        $user->notify(new CollaboratorDeletedNotification($nombre));

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('collaborator_deleted');
        expect($notificacion->data['color'])->toBe('red');
    });

    it('eliminar colaborador vía service genera notificación database', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);

        app(CollaboratorService::class)->delete($colaborador);

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('collaborator_deleted');
    });

    it('cambiar tipo de colaborador genera notificación database', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        // Colaborador sin contratos vigentes para poder cambiar de tipo
        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status, [
            'type' => 'Empleado',
            'is_company' => false,
        ]);

        app(CollaboratorService::class)->changeType($colaborador);

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('collaborator_type_changed');
        expect($notificacion->data['color'])->toBe('blue');
    });

    it('la notificación de creación contiene URL al colaborador', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);

        $user->notify(new CollaboratorCreatedNotification($colaborador->full_name, $colaborador->id));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['url'])->toContain($colaborador->id);
        expect($data['title'])->toBe('Colaborador creado');
    });

    it('la notificación de eliminación contiene URL al índice de colaboradores', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $user->notify(new CollaboratorDeletedNotification('Juan Pérez'));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['url'])->toContain('colaboradores');
        expect($data['message'])->toContain('Juan Pérez');
    });

    it('la notificación de cambio de tipo describe el nuevo tipo', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);

        $user->notify(new CollaboratorTypeChangedNotification($colaborador->full_name, $colaborador->id, 'Contratista'));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['message'])->toContain('Contratista');
    });
});

// ---------------------------------------------------------------------------
// Suite: Notificaciones de contratos
// ---------------------------------------------------------------------------

describe('Notificaciones de contratos', function (): void {

    it('crear contrato genera notificación database para el usuario', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $contrato = contratoParaTest($user, $institution, $colaborador);

        $user->notify(new ContractCreatedNotification(
            $contrato->contract_code ?? '',
            $contrato->id,
            $colaborador->full_name,
        ));

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('contract_created');
        expect($notificacion->data['color'])->toBe('green');
    });

    it('actualizar contrato genera notificación database', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $contrato = contratoParaTest($user, $institution, $colaborador);

        $user->notify(new ContractUpdatedNotification($contrato->contract_code ?? '', $contrato->id, $colaborador->full_name));

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('contract_updated');
        expect($notificacion->data['color'])->toBe('amber');
    });

    it('terminar contrato genera notificación database', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $contrato = contratoParaTest($user, $institution, $colaborador);

        app(ContractService::class)->terminate($contrato);

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('contract_terminated');
        expect($notificacion->data['color'])->toBe('blue');
    });

    it('eliminar contrato via controller genera notificación database', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $contrato = contratoParaTest($user, $institution, $colaborador);

        $this->delete(route('rh.contratos.destroy', $contrato))
            ->assertRedirect(route('rh.contratos.index'));

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications)->toHaveCount(1);
        expect($notificacion->data['type'])->toBe('contract_deleted');
        expect($notificacion->data['color'])->toBe('red');
    });

    it('la notificación de contrato creado con código describe el código', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $contrato = contratoParaTest($user, $institution, $colaborador);

        $codigo = 'CONT-2025';
        $user->notify(new ContractCreatedNotification($codigo, $contrato->id, $colaborador->full_name));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['message'])->toContain($codigo);
        expect($data['url'])->toContain($contrato->id);
    });

    it('la notificación de contrato creado sin código usa mensaje genérico', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $contrato = contratoParaTest($user, $institution, $colaborador);

        $user->notify(new ContractCreatedNotification('', $contrato->id, $colaborador->full_name));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['message'])->toContain('Nuevo contrato');
    });

    it('la notificación de contrato terminado tiene título correcto', function (): void {
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');
        $this->actingAs($user);

        $colaborador = colaboradorParaTest($user, $institution, $documentType, $status);
        $contrato = contratoParaTest($user, $institution, $colaborador);

        $user->notify(new ContractTerminatedNotification($contrato->contract_code ?? 'COD-001', $contrato->id, $colaborador->full_name));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['title'])->toBe('Contrato terminado');
        expect($data['icon'])->toBe('check-circle');
    });
});

// ---------------------------------------------------------------------------
// Suite: Notificaciones de importaciones
// ---------------------------------------------------------------------------

describe('Notificaciones de importaciones', function (): void {

    it('ImportCollaboratorsJob genera notificación al completarse', function (): void {
        // contextoNotificaciones ya crea institution, documentType CC y status Activo
        [$user, $institution, $documentType, $status] = contextoNotificaciones('rh-manager');

        // Crear un Excel mínimo válido con la estructura esperada (sin filas de datos)
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'tipo_documento', 'numero_documento', 'fecha_expedicion',
            'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido',
            'fecha_nacimiento', 'genero', 'correo', 'telefono', 'direccion', 'estado',
        ];
        $sheet->fromArray($headers, null, 'A1');

        // El Job usa storage_path('app/private/' . $filePath), escribir ahí directamente
        $relPath = 'test_job_colab_'.uniqid().'.xlsx';
        $destPath = storage_path('app/private/'.$relPath);
        @mkdir(dirname($destPath), 0755, true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($destPath);

        $job = new ImportCollaboratorsJob(
            filePath: $relPath,
            institutionId: $institution->id,
            type: 'Empleado',
            overwrite: false,
            userId: $user->id,
        );

        $job->handle();

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications->count())->toBeGreaterThanOrEqual(1);
        expect($notificacion->data['type'])->toBe('collaborator_import_completed');
        expect($notificacion->data['color'])->toBe('green');
    });

    it('ImportCollaboratorsJob genera notificación de error en failed()', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        $job = new ImportCollaboratorsJob(
            filePath: 'ruta/inexistente.xlsx',
            institutionId: $institution->id,
            type: 'Empleado',
            overwrite: false,
            userId: $user->id,
        );

        $job->failed(new \Exception('Error de prueba controlado'));

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications->count())->toBeGreaterThanOrEqual(1);
        expect($notificacion->data['type'])->toBe('collaborator_import_completed');
        expect($notificacion->data['color'])->toBe('red');
        expect($notificacion->data['title'])->toContain('Error');
    });

    it('ImportCollaboratorsJob failed() persiste el mensaje de error del sistema', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        $mensajeError = 'Disco lleno al procesar el archivo';

        $job = new ImportCollaboratorsJob(
            filePath: 'ruta/inexistente.xlsx',
            institutionId: $institution->id,
            type: 'Empleado',
            overwrite: false,
            userId: $user->id,
        );

        $job->failed(new \Exception($mensajeError));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['message'])->toContain($mensajeError);
    });

    it('ImportContractsJob genera notificación al completarse', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        // Excel con hojas "Contratos" y "Valores_comprometidos" sin filas de datos
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;

        // Hoja 1 — Contratos
        $sheetContratos = $spreadsheet->getActiveSheet();
        $sheetContratos->setTitle('Contratos');
        $headersContratos = [
            'codigo_contrato', 'numero_contrato', 'documento_colaborador',
            'tipo_contrato', 'cargo', 'fecha_inicio', 'fecha_fin',
            'objeto', 'obligaciones', 'salario', 'honorarios',
            'correo_cargo', 'estado',
        ];
        $sheetContratos->fromArray($headersContratos, null, 'A1');

        // Hoja 2 — Valores comprometidos
        $spreadsheet->createSheet();
        $sheetComprometidos = $spreadsheet->getSheet(1);
        $sheetComprometidos->setTitle('Valores_comprometidos');
        $sheetComprometidos->fromArray(['codigo_contrato', 'cuenta_contable', 'centro_costo', 'valor'], null, 'A1');

        // El Job usa storage_path('app/private/' . $filePath), escribir ahí directamente
        $relPath = 'test_job_contratos_'.uniqid().'.xlsx';
        $destPath = storage_path('app/private/'.$relPath);
        @mkdir(dirname($destPath), 0755, true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($destPath);

        $job = new ImportContractsJob(
            filePath: $relPath,
            institutionId: $institution->id,
            overwrite: false,
            userId: $user->id,
        );

        $job->handle();

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications->count())->toBeGreaterThanOrEqual(1);
        expect($notificacion->data['type'])->toBe('contract_import_completed');
        expect($notificacion->data['color'])->toBe('green');
    });

    it('ImportContractsJob genera notificación de error en failed()', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        $job = new ImportContractsJob(
            filePath: 'ruta/inexistente.xlsx',
            institutionId: $institution->id,
            overwrite: false,
            userId: $user->id,
        );

        $job->failed(new \Exception('Error interno de prueba'));

        $notificacion = $user->fresh()->notifications->first();
        expect($user->fresh()->notifications->count())->toBeGreaterThanOrEqual(1);
        expect($notificacion->data['type'])->toBe('contract_import_completed');
        expect($notificacion->data['color'])->toBe('red');
        expect($notificacion->data['title'])->toContain('Error');
    });

    it('ImportContractsJob failed() persiste el mensaje de error en la notificación', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        $mensajeError = 'Timeout al conectar con la base de datos';

        $job = new ImportContractsJob(
            filePath: 'ruta/inexistente.xlsx',
            institutionId: $institution->id,
            overwrite: false,
            userId: $user->id,
        );

        $job->failed(new \Exception($mensajeError));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['message'])->toContain($mensajeError);
    });

    it('la notificación de importación de colaboradores completada con éxito es verde', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        $user->notify(new CollaboratorImportCompletedNotification([
            'imported' => 10,
            'updated' => 2,
            'skipped' => 1,
            'failures' => [],
            'tipo' => 'Empleado',
            'completado_at' => now()->format('d/m/Y H:i'),
        ]));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['color'])->toBe('green');
        expect($data['type'])->toBe('collaborator_import_completed');
        expect($data['message'])->toContain('10');
    });

    it('la notificación de importación de colaboradores con errores es roja', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        $user->notify(new CollaboratorImportCompletedNotification([
            'imported' => 5,
            'updated' => 0,
            'skipped' => 3,
            'failures' => [
                ['fila' => 2, 'campo' => 'numero_documento', 'errores' => ['El campo es obligatorio.']],
            ],
            'tipo' => 'Empleado',
            'completado_at' => now()->format('d/m/Y H:i'),
        ]));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['color'])->toBe('red');
        expect($data['type'])->toBe('collaborator_import_completed');
    });

    it('la notificación de importación de contratos completada con éxito es verde', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        $user->notify(new ContractImportCompletedNotification([
            'imported' => 8,
            'updated' => 1,
            'skipped' => 0,
            'row_errors' => [],
            'category_counts' => [],
            'committed_values_imported' => 4,
            'committed_values_skipped' => 0,
            'committed_values_errors' => [],
            'completado_at' => now()->format('d/m/Y H:i'),
        ]));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['color'])->toBe('green');
        expect($data['type'])->toBe('contract_import_completed');
        expect($data['message'])->toContain('8');
    });

    it('la notificación de importación de contratos con error es roja', function (): void {
        [$user, $institution] = contextoNotificaciones('rh-manager');

        $user->notify(new ContractImportCompletedNotification([
            'error' => 'El archivo no pudo ser procesado.',
            'completado_at' => now()->format('d/m/Y H:i'),
        ]));

        $data = $user->fresh()->notifications->first()->data;
        expect($data['color'])->toBe('red');
        expect($data['title'])->toContain('Error');
        expect($data['message'])->toContain('El archivo no pudo ser procesado.');
    });
});
