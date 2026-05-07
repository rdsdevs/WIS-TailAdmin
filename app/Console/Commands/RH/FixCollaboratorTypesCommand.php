<?php

declare(strict_types=1);

namespace App\Console\Commands\RH;

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Throwable;

class FixCollaboratorTypesCommand extends Command
{
    protected $signature = 'rh:fix-collaborator-types
                            {csv : Ruta absoluta al CSV con la columna tipo_colaborador}
                            {--institution-nit=860006560 : NIT de la institución (default ASCUN)}
                            {--user-id= : UUID del usuario que firma la corrección en auditoría}
                            {--dry-run : Simular sin escribir en BD}';

    protected $description = 'Corrige el campo type de colaboradores mal importados, leyendo tipo_colaborador desde un CSV';

    private bool $dryRun = false;

    /** @var array{filas_leidas:int,candidatos:int,actualizados:int,ya_correctos:int,no_encontrados:int,ignorados_empresa:int,invalidos:int,errores:int} */
    private array $stats = [
        'filas_leidas' => 0,
        'candidatos' => 0,
        'actualizados' => 0,
        'ya_correctos' => 0,
        'no_encontrados' => 0,
        'ignorados_empresa' => 0,
        'invalidos' => 0,
        'errores' => 0,
    ];

    /** @var array<int,string> */
    private array $noEncontrados = [];

    /** @var array<int,array{id:string,valor:string}> */
    private array $invalidosLog = [];

    /** @var array<int,array{document_number:string,type_actual:?string}> */
    private array $omitidosTipoInesperado = [];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $csvPath = (string) $this->argument('csv');

        if (! file_exists($csvPath)) {
            $this->error("No se encontró el archivo CSV: {$csvPath}");

            return self::FAILURE;
        }

        $institution = Institution::query()
            ->where('nit', (string) $this->option('institution-nit'))
            ->first();

        if (! $institution) {
            $this->error("No se encontró una institución con NIT: {$this->option('institution-nit')}");

            return self::FAILURE;
        }

        $actingUser = $this->resolverUsuarioFirmante();
        if ($actingUser === false) {
            return self::FAILURE;
        }

        if ($this->dryRun) {
            $this->warn('[DRY-RUN] No se escribirá en BD ni se generarán auditorías.');
        } else {
            if (! $this->confirm('¿Confirma actualizar el campo type de los colaboradores afectados en la BD?', false)) {
                $this->info('Operación cancelada por el usuario.');

                return self::SUCCESS;
            }
        }

        $this->info('');
        $this->info('══════════════════════════════════════════════');
        $this->info('  Corrección de tipo de colaboradores');
        $this->info('══════════════════════════════════════════════');
        $this->info("  Institución: {$institution->name} (NIT {$institution->nit})");
        if ($actingUser !== null) {
            $this->info("  Usuario firmante: {$actingUser->name}");
        } else {
            $this->warn('  Sin usuario firmante: las auditorías quedarán con user_id null.');
        }
        $this->info('');

        try {
            if ($actingUser !== null) {
                Auth::login($actingUser);
            }

            $candidatos = $this->leerCandidatosDesdeCsv($csvPath);

            if ($candidatos === []) {
                $this->warn('No se encontraron filas con tipo_colaborador = Empleado en el CSV.');
                $this->imprimirReporte();

                return self::SUCCESS;
            }

            try {
                $this->procesarCandidatos($candidatos, (string) $institution->id);
            } catch (Throwable $e) {
                $this->stats['errores']++;
                $this->error('La transacción fue revertida: '.$e->getMessage());
                $this->imprimirReporte();

                return self::FAILURE;
            }
        } finally {
            if (Auth::check()) {
                Auth::logout();
            }
        }

        $this->imprimirReporte();

        return $this->stats['errores'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return User|null|false null = sin firmante; false = error fatal; User = ok.
     */
    private function resolverUsuarioFirmante(): User|null|false
    {
        $userId = $this->option('user-id');
        if ($userId !== null && $userId !== '') {
            $user = User::find($userId);
            if (! $user) {
                $this->error("No se encontró un usuario con ID: {$userId}");

                return false;
            }

            return $user;
        }

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole === null) {
            return null;
        }

        return User::role($superAdminRole)->orderBy('created_at')->first();
    }

    /**
     * Lee el CSV y devuelve los document_number candidatos a actualizar.
     *
     * @return array<int,string>
     */
    private function leerCandidatosDesdeCsv(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->error("No se pudo abrir el archivo: {$csvPath}");

            return [];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            $this->error('El archivo CSV está vacío o no tiene encabezado.');

            return [];
        }

        $headers = array_map(fn (string $h): string => trim($h), $headers);
        if ($headers !== [] && isset($headers[0])) {
            $headers[0] = str_replace("\u{FEFF}", '', $headers[0]);
        }

        $candidatos = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $this->stats['filas_leidas']++;

            $data = array_combine($headers, array_map(fn (string $v): string => trim($v), $row));

            $tipo = $data['tipo_colaborador'] ?? '';
            $tipoNormalizado = ucfirst(strtolower($tipo));

            if (! in_array($tipoNormalizado, ['Empleado', 'Contratista'], true)) {
                $this->stats['invalidos']++;
                $this->invalidosLog[] = [
                    'id' => $data['id'] ?? '?',
                    'valor' => $tipo,
                ];

                continue;
            }

            if ($tipoNormalizado !== 'Empleado') {
                continue;
            }

            if ($this->esEmpresa($data['es_empresa'] ?? '')) {
                $this->stats['ignorados_empresa']++;

                continue;
            }

            $numdoc = $this->normalizarNumdoc($data['numdoc'] ?? '');
            if ($numdoc === '') {
                $this->stats['invalidos']++;
                $this->invalidosLog[] = [
                    'id' => $data['id'] ?? '?',
                    'valor' => '(numdoc vacío)',
                ];

                continue;
            }

            $candidatos[] = $numdoc;
        }

        fclose($handle);

        $candidatos = array_values(array_unique($candidatos));
        $this->stats['candidatos'] = count($candidatos);

        return $candidatos;
    }

    /**
     * @param  array<int,string>  $candidatos
     */
    private function procesarCandidatos(array $candidatos, string $institutionId): void
    {
        $colaboradores = Collaborator::query()
            ->byInstitution($institutionId)
            ->whereIn('document_number', $candidatos)
            ->get()
            ->keyBy('document_number');

        $aActualizar = [];
        $previewRows = [];

        foreach ($candidatos as $numdoc) {
            /** @var Collaborator|null $c */
            $c = $colaboradores->get($numdoc);

            if ($c === null) {
                $this->stats['no_encontrados']++;
                $this->noEncontrados[] = $numdoc;

                continue;
            }

            if ($c->type === 'Empleado') {
                $this->stats['ya_correctos']++;

                continue;
            }

            if ($c->type !== 'Contratista') {
                $this->stats['errores']++;
                $this->omitidosTipoInesperado[] = [
                    'document_number' => (string) $c->document_number,
                    'type_actual' => $c->type,
                ];

                continue;
            }

            $aActualizar[] = $c;
            $previewRows[] = [
                $c->document_number,
                $c->full_name,
                $c->type,
                'Empleado',
            ];
        }

        if ($previewRows !== []) {
            $this->info('Colaboradores a actualizar:');
            $this->table(
                ['Documento', 'Nombre', 'Tipo actual', 'Nuevo tipo'],
                $previewRows
            );
        }

        if ($this->dryRun || $aActualizar === []) {
            return;
        }

        DB::transaction(function () use ($aActualizar): void {
            foreach ($aActualizar as $colaborador) {
                $colaborador->update(['type' => 'Empleado']);
                $this->stats['actualizados']++;
            }
        });
    }

    private function normalizarNumdoc(string $raw): string
    {
        $valor = preg_replace('/\s+/u', '', trim($raw)) ?? '';

        return str_replace(['.', '-', "\u{00A0}", "\u{FEFF}"], '', $valor);
    }

    private function esEmpresa(string $raw): bool
    {
        $valor = strtoupper(trim($raw));

        return in_array($valor, ['SI', 'SÍ', 'S', '1', 'TRUE', 'VERDADERO'], true);
    }

    private function imprimirReporte(): void
    {
        $titulo = $this->dryRun ? 'Resultado [DRY-RUN]' : 'Resultado';

        $this->info('');
        $this->info('══════════════════════════════════════════════');
        $this->info("  {$titulo}");
        $this->info('══════════════════════════════════════════════');

        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Filas leídas',                    $this->stats['filas_leidas']],
                ['Candidatos a Empleado en CSV',    $this->stats['candidatos']],
                ['Actualizados',                    $this->stats['actualizados']],
                ['Ya estaban como Empleado',        $this->stats['ya_correctos']],
                ['No encontrados en BD',            $this->stats['no_encontrados']],
                ['Ignorados (empresa)',             $this->stats['ignorados_empresa']],
                ['Filas inválidas',                 $this->stats['invalidos']],
                ['Errores',                         $this->stats['errores']],
            ]
        );

        if ($this->noEncontrados !== []) {
            $this->warn('Documentos no encontrados en BD:');
            foreach (array_slice($this->noEncontrados, 0, 20) as $doc) {
                $this->line("  - {$doc}");
            }
            if (count($this->noEncontrados) > 20) {
                $this->line('  ... ('.(count($this->noEncontrados) - 20).' más)');
            }
        }

        if ($this->invalidosLog !== []) {
            $this->warn('Filas con tipo_colaborador inválido:');
            foreach (array_slice($this->invalidosLog, 0, 20) as $f) {
                $this->line("  - id={$f['id']} valor='{$f['valor']}'");
            }
            if (count($this->invalidosLog) > 20) {
                $this->line('  ... ('.(count($this->invalidosLog) - 20).' más)');
            }
        }

        if ($this->omitidosTipoInesperado !== []) {
            $this->warn('Colaboradores omitidos con tipo inesperado (no Empleado ni Contratista):');
            foreach (array_slice($this->omitidosTipoInesperado, 0, 20) as $f) {
                $tipoActual = $f['type_actual'] ?? '(null)';
                $this->line("  - {$f['document_number']} type_actual='{$tipoActual}'");
            }
            if (count($this->omitidosTipoInesperado) > 20) {
                $this->line('  ... ('.(count($this->omitidosTipoInesperado) - 20).' más)');
            }
        }
    }
}
