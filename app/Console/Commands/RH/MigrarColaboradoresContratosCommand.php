<?php

declare(strict_types=1);

namespace App\Console\Commands\RH;

use App\Models\RH\Collaborator;
use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrarColaboradoresContratosCommand extends Command
{
    protected $signature = 'rh:migrar-colaboradores-contratos
                            {--colaboradores= : Ruta al CSV de colaboradores}
                            {--contratos= : Ruta al CSV de contratos_colaboradores}
                            {--comprometidos= : Ruta al CSV de comprometidos_contratos_colaboradores}
                            {--dry-run : Simular sin escribir en BD}';

    protected $description = 'Migra contratos de colaboradores (formato NNN-YYYY) y sus valores comprometidos';

    // ── Catálogos ────────────────────────────────────────────────────────────
    private const INSTITUTION_ID = '019d1967-c1f5-7038-a1bf-f3408cc2a4c9';

    private const TIPO_CONTRATO_MAP = [
        '3'  => '019d1967-c999-7062-8014-113fe459c85e', // CPS
        '6'  => '019d1967-c989-728a-a49d-970b3e66d259', // IDFD (Indefinido)
        '9'  => '019d1967-c999-7062-8014-113fe459c85e', // CPS
        '10' => '019d1967-c999-7062-8014-113fe459c85e', // CPS (default)
    ];

    // ── Estado ───────────────────────────────────────────────────────────────
    private bool $dryRun = false;

    private array $stats = [
        'contratos_creados'      => 0,
        'contratos_actualizados' => 0,
        'contratos_saltados'     => 0,
        'comprometidos_creados'  => 0,
        'comprometidos_saltados' => 0,
    ];

    /** @var array<string, string>  contrato_id_legacy → contract UUID */
    private array $contratoIdMap = [];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $csvColabs    = $this->option('colaboradores')
            ?? base_path('../../migate_db/colaboradores.csv');
        $csvContratos = $this->option('contratos')
            ?? base_path('../../migate_db/contratos_colaboradores.csv');
        $csvComp      = $this->option('comprometidos')
            ?? base_path('../../migate_db/comprometidos_contratos_colaboradores.csv');

        foreach ([$csvColabs, $csvContratos, $csvComp] as $path) {
            if (! file_exists($path)) {
                $this->error("No se encontró: {$path}");
                return self::FAILURE;
            }
        }

        if ($this->dryRun) {
            $this->warn('⚠ Modo simulación (--dry-run): no se escribirá en BD');
        }

        $this->info('');
        $this->info('══════════════════════════════════════════════════════');
        $this->info('  Migración de contratos de colaboradores (NNN-YYYY)');
        $this->info('══════════════════════════════════════════════════════');

        // Cargar mapa colaboradores legacy_id → document_number
        $this->info('');
        $this->info('Paso 0 — Cargando catálogos...');
        $mapaColabs = $this->cargarColaboradores($csvColabs);
        $this->info("  Colaboradores en CSV: " . count($mapaColabs));

        // Enriquecer con contratos ya en BD
        $this->contratoIdMap = Contract::query()
            ->where('institution_id', self::INSTITUTION_ID)
            ->whereNotNull('contract_code')
            ->pluck('id', 'contract_code')
            ->toArray();
        $this->info("  Contratos pre-existentes en BD: " . count($this->contratoIdMap));

        // Paso 1: Contratos
        $this->info('');
        $this->info('Paso 1 — Migrando contratos...');
        $this->migrarContratos($csvContratos, $mapaColabs);

        // Paso 2: Comprometidos
        $this->info('');
        $this->info('Paso 2 — Migrando valores comprometidos...');
        $this->migrarComprometidos($csvComp);

        $this->imprimirReporte();

        return self::SUCCESS;
    }

    // ── Carga colaboradores CSV ──────────────────────────────────────────────

    /** @return array<string, array>  id_legacy → fila del CSV */
    private function cargarColaboradores(string $path): array
    {
        $handle  = fopen($path, 'r');
        $headers = array_map('trim', fgetcsv($handle));
        $mapa    = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }
            $data        = array_combine($headers, array_map('trim', $row));
            $mapa[$data['id']] = $data;
        }

        fclose($handle);
        return $mapa;
    }

    // ── Paso 1: Contratos ────────────────────────────────────────────────────

    private function migrarContratos(string $path, array $mapaColabs): void
    {
        $handle  = fopen($path, 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }
            $data = array_combine($headers, array_map('trim', $row));
            $this->procesarContrato($data, $mapaColabs);
            $bar->advance();
        }

        $bar->finish();
        fclose($handle);
        $this->info('');
    }

    private function procesarContrato(array $data, array $mapaColabs): void
    {
        $legacyId    = $data['id'];
        $codigo      = trim($data['codigo_contrato'] ?? '');
        $numContrato = trim($data['num_contrato'] ?? '');
        $colabLegacy = trim($data['colaborador_id'] ?? '');
        $tipoLegacy  = trim($data['tipo_contrato_id'] ?? '9');
        $inicio      = $this->parseDate($data['fecha_inicio'] ?? '');
        $fin         = $this->parseDate($data['fecha_fin'] ?? '');
        $objeto      = trim($data['objeto'] ?? '');
        $obligs      = trim($data['obligaciones'] ?? '');
        $honorarios  = (float) ($data['honorarios'] ?? 0);
        $salario     = (float) ($data['salario'] ?? 0);
        $correo      = trim($data['correo_cargo'] ?? '');
        $estado      = $this->mapEstado($data['estado_contrato'] ?? '');

        // Validaciones mínimas
        if (! $inicio || (! $codigo && ! $numContrato)) {
            $this->stats['contratos_saltados']++;
            return;
        }

        // Resolver colaborador
        $colabData = $mapaColabs[$colabLegacy] ?? null;
        if (! $colabData) {
            $this->stats['contratos_saltados']++;
            return;
        }

        $numdoc = trim($colabData['numdoc'] ?? '');
        if (! $numdoc) {
            $this->stats['contratos_saltados']++;
            return;
        }

        $contractTypeId = self::TIPO_CONTRATO_MAP[$tipoLegacy]
            ?? self::TIPO_CONTRATO_MAP['9'];

        DB::transaction(function () use (
            $legacyId, $codigo, $numContrato, $numdoc,
            $contractTypeId, $inicio, $fin,
            $objeto, $obligs, $honorarios, $salario, $correo, $estado
        ): void {
            // Buscar colaborador en BD por documento
            $collaborator = Collaborator::query()
                ->where('institution_id', self::INSTITUTION_ID)
                ->where('document_number', $numdoc)
                ->first();

            if (! $collaborator) {
                $this->stats['contratos_saltados']++;
                return;
            }

            // Buscar contrato existente por código
            $existing = null;
            if ($codigo) {
                $existing = Contract::query()
                    ->where('institution_id', self::INSTITUTION_ID)
                    ->where('contract_code', $codigo)
                    ->first();
            }

            if (! $existing) {
                $existing = Contract::query()
                    ->where('institution_id', self::INSTITUTION_ID)
                    ->where('collaborator_id', $collaborator->id)
                    ->where('start_date', $inicio)
                    ->first();
            }

            $contractData = [
                'institution_id'   => self::INSTITUTION_ID,
                'collaborator_id'  => $collaborator->id,
                'contract_type_id' => $contractTypeId,
                'contract_number'  => $numContrato ?: null,
                'contract_code'    => $codigo ?: null,
                'start_date'       => $inicio,
                'end_date'         => $fin ?: null,
                'object'           => $objeto ?: null,
                'obligations'      => $obligs ?: null,
                'fees'             => $honorarios,
                'salary'           => $salario,
                'position_email'   => $correo ?: null,
                'status'           => $estado,
            ];

            if ($existing) {
                if (! $this->dryRun) {
                    $existing->update($contractData);
                }
                $this->stats['contratos_actualizados']++;
                $id = $existing->id;
            } else {
                $id = (string) Str::uuid();
                if (! $this->dryRun) {
                    Contract::create(array_merge(['id' => $id], $contractData));
                }
                $this->stats['contratos_creados']++;
            }

            // Guardar mapa legacy_id → UUID para comprometidos
            $this->contratoIdMap[$legacyId] = $id;
            if ($codigo) {
                $this->contratoIdMap[$codigo] = $id;
            }
        });
    }

    // ── Paso 2: Comprometidos ────────────────────────────────────────────────

    private function migrarComprometidos(string $path): void
    {
        $handle  = fopen($path, 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $data       = array_combine($headers, array_map('trim', $row));
            $contratoId = trim($data['contrato_id'] ?? '');
            $cuenta     = trim($data['cuenta_contable'] ?? '');
            $centro     = trim($data['centro_de_costo'] ?? '');
            $valor      = (float) ($data['valor_comprometido'] ?? 0);

            if (! $cuenta || ! $centro || $valor <= 0) {
                $this->stats['comprometidos_saltados']++;
                $bar->advance();
                continue;
            }

            // Buscar UUID del contrato: primero por legacy_id, luego verificar en BD
            $contractUuid = $this->contratoIdMap[$contratoId] ?? null;

            if ($contractUuid) {
                $existe = Contract::where('id', $contractUuid)->exists();
                if (! $existe) {
                    $contractUuid = null;
                }
            }

            if (! $contractUuid) {
                $this->stats['comprometidos_saltados']++;
                $bar->advance();
                continue;
            }

            $yaExiste = CommittedValue::query()
                ->where('contract_id', $contractUuid)
                ->where('accounting_account', $cuenta)
                ->where('cost_center', $centro)
                ->exists();

            if (! $yaExiste && ! $this->dryRun) {
                CommittedValue::create([
                    'institution_id'     => self::INSTITUTION_ID,
                    'contract_id'        => $contractUuid,
                    'accounting_account' => $cuenta,
                    'cost_center'        => $centro,
                    'amount'             => $valor,
                ]);
            }

            if (! $yaExiste) {
                $this->stats['comprometidos_creados']++;
            } else {
                $this->stats['comprometidos_saltados']++;
            }

            $bar->advance();
        }

        $bar->finish();
        fclose($handle);
        $this->info('');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if (! $value || $value === '0000-00-00' || $value === 'NULL') {
            return null;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return substr($value, 0, 10);
        }
        return null;
    }

    private function mapEstado(string $estado): string
    {
        return match (strtolower(trim($estado))) {
            'vigente'   => 'Vigente',
            'liquidado' => 'Liquidado',
            'terminado',
            'finalizado' => 'Terminado',
            default      => 'Terminado',
        };
    }

    private function imprimirReporte(): void
    {
        $dr = $this->dryRun ? ' (simulado)' : '';
        $this->info('');
        $this->info('══════════════════════════════════════════════════════');
        $this->info("  Resultado de la migración{$dr}");
        $this->info('══════════════════════════════════════════════════════');
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Contratos creados',         $this->stats['contratos_creados']],
                ['Contratos actualizados',     $this->stats['contratos_actualizados']],
                ['Contratos saltados',         $this->stats['contratos_saltados']],
                ['Comprometidos creados',      $this->stats['comprometidos_creados']],
                ['Comprometidos saltados',     $this->stats['comprometidos_saltados']],
            ]
        );
    }
}
