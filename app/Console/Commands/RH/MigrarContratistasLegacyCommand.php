<?php

declare(strict_types=1);

namespace App\Console\Commands\RH;

use App\Models\RH\Collaborator;
use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrarContratistasLegacyCommand extends Command
{
    protected $signature = 'rh:migrar-contratistas
                            {--contratos= : Ruta al CSV de contratistas (default: migate_db/contratistas.csv)}
                            {--comprometidos= : Ruta al CSV de comprometidos limpios (default: migate_db/comprometidos_contratistas_limpio.csv)}
                            {--dry-run : Simular sin escribir en BD}';

    protected $description = 'Migra contratistas legacy y sus valores comprometidos a la BD del nuevo sistema';

    // ── Catálogos (ASCUN) ────────────────────────────────────────────────────
    private const INSTITUTION_ID  = '019d1967-c1f5-7038-a1bf-f3408cc2a4c9';
    private const STATUS_ACTIVO   = '019d1967-c964-7364-a384-afdf74b15fbe';

    private const DOC_TYPES = [
        'CC'  => '019d1967-c944-71e7-859e-6de40aeb681c',
        'CE'  => '019d1967-c948-7007-a8eb-32fe515f3494',
        'NIT' => '019d1967-c94e-713d-bf52-d69b04844ab7',
        'PAP' => '019d1967-c953-7017-8cc3-065b537c7d28',
        'TI'  => '019d1967-c957-735a-b031-c4c1458cc463',
    ];

    private const CONTRACT_TYPES = [
        'CPS'                    => '019d1967-c999-7062-8014-113fe459c85e',
        'OPS'                    => '019d1967-c993-72f4-862e-c28df0cee618',
        'Arrendamiento De Depósito' => '019d1967-c9b2-7156-a17d-f62d8082a629',
        'COMPR'                  => '019d1967-c9b9-7190-99da-5e905037a271',
    ];

    // ── Estado interno ───────────────────────────────────────────────────────
    private bool $dryRun = false;

    private array $stats = [
        'colaboradores_creados'    => 0,
        'colaboradores_existentes' => 0,
        'contratos_creados'        => 0,
        'contratos_actualizados'   => 0,
        'contratos_saltados'       => 0,
        'comprometidos_creados'    => 0,
        'comprometidos_saltados'   => 0,
    ];

    /** @var array<string, string>  codigoLegacy → contract UUID */
    private array $codigoMap = [];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $csvContratos     = $this->option('contratos')
            ?? base_path('../../migate_db/contratistas.csv');
        $csvComprometidos = $this->option('comprometidos')
            ?? base_path('../../migate_db/comprometidos_contratistas_limpio.csv');

        if (! file_exists($csvContratos)) {
            $this->error("No se encontró el archivo: {$csvContratos}");
            return self::FAILURE;
        }

        if (! file_exists($csvComprometidos)) {
            $this->error("No se encontró el archivo: {$csvComprometidos}");
            return self::FAILURE;
        }

        if ($this->dryRun) {
            $this->warn('⚠ Modo simulación (--dry-run): no se escribirá en BD');
        }

        $this->info('');
        $this->info('══════════════════════════════════════════════');
        $this->info('  Migración de contratistas legacy → BD nueva');
        $this->info('══════════════════════════════════════════════');

        // Paso 1: Enriquecer mapa con contratos ya existentes en BD
        $this->info('');
        $this->info('Paso 0 — Cargando contratos existentes en BD...');
        $this->codigoMap = Contract::query()
            ->where('institution_id', self::INSTITUTION_ID)
            ->whereNotNull('contract_code')
            ->pluck('id', 'contract_code')
            ->toArray();
        $this->info("  Contratos pre-existentes en BD: " . count($this->codigoMap));

        // Paso 2: Migrar contratos y colaboradores
        $this->info('');
        $this->info('Paso 1 — Migrando colaboradores y contratos...');
        $this->migrarContratos($csvContratos);

        // Paso 3: Migrar comprometidos
        $this->info('');
        $this->info('Paso 2 — Migrando valores comprometidos...');
        $this->migrarComprometidos($csvComprometidos);

        // Reporte final
        $this->imprimirReporte();

        return self::SUCCESS;
    }

    // ── Paso 1: Contratos ────────────────────────────────────────────────────

    private function migrarContratos(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $data = array_combine($headers, array_map('trim', $row));
            $this->procesarFila($data);
            $bar->advance();
        }

        $bar->finish();
        fclose($handle);
        $this->info('');
    }

    private function procesarFila(array $data): void
    {
        $numdoc   = trim($data['numdoc'] ?? '');
        $tipodoc  = trim($data['tipodoc'] ?? 'CC');
        $nombres  = trim($data['nombres'] ?? '');
        $apellidos = trim($data['apellidos'] ?? '');
        $email    = trim($data['email'] ?? '');
        $codigo   = trim($data['codigocontrato'] ?? '');
        $tipo     = trim($data['tipocontrato'] ?? 'CPS');
        $inicio   = $this->parseDate($data['fechaincio'] ?? '');
        $fin      = $this->parseDate($data['fechafin'] ?? '');
        $objeto   = trim($data['objeto'] ?? '');
        $obligs   = trim($data['obligaciones'] ?? '');
        $honorarios = (float) ($data['honorarios'] ?? 0);
        $estado   = $this->mapEstado(trim($data['estado'] ?? 'Terminado'));

        if (! $numdoc || ! $inicio) {
            $this->stats['contratos_saltados']++;
            return;
        }

        $docTypeId      = self::DOC_TYPES[$tipodoc] ?? self::DOC_TYPES['CC'];
        $contractTypeId = self::CONTRACT_TYPES[$tipo] ?? self::CONTRACT_TYPES['CPS'];

        DB::transaction(function () use (
            $numdoc, $tipodoc, $docTypeId, $nombres, $apellidos, $email,
            $codigo, $contractTypeId, $inicio, $fin,
            $objeto, $obligs, $honorarios, $estado
        ): void {
            // 1. Colaborador
            $collaborator = $this->resolverColaborador(
                $numdoc, $docTypeId, $tipodoc, $nombres, $apellidos, $email
            );

            if (! $collaborator) {
                $this->stats['contratos_saltados']++;
                return;
            }

            // 2. Contrato
            $existing = Contract::query()
                ->where('institution_id', self::INSTITUTION_ID)
                ->where('collaborator_id', $collaborator->id)
                ->where('start_date', $inicio)
                ->first();

            $contractData = [
                'institution_id'   => self::INSTITUTION_ID,
                'collaborator_id'  => $collaborator->id,
                'contract_type_id' => $contractTypeId,
                'contract_code'    => $codigo ?: null,
                'start_date'       => $inicio,
                'end_date'         => $fin ?: null,
                'object'           => $objeto ?: null,
                'obligations'      => $obligs ?: null,
                'fees'             => $honorarios,
                'salary'           => 0,
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

            if ($codigo) {
                $this->codigoMap[$codigo] = $id;
            }
        });
    }

    private function resolverColaborador(
        string $numdoc,
        string $docTypeId,
        string $tipodoc,
        string $nombres,
        string $apellidos,
        string $email,
    ): ?Collaborator {
        $existing = Collaborator::query()
            ->where('institution_id', self::INSTITUTION_ID)
            ->where('document_number', $numdoc)
            ->first();

        if ($existing) {
            $this->stats['colaboradores_existentes']++;
            return $existing;
        }

        // Crear colaborador nuevo
        $isCompany   = ($tipodoc === 'NIT');
        $partes      = $isCompany ? [] : $this->partirNombreApellido($nombres, $apellidos);

        $collaboratorData = [
            'institution_id'   => self::INSTITUTION_ID,
            'document_type_id' => $docTypeId,
            'document_number'  => $numdoc,
            'is_company'       => $isCompany,
            'company_name'     => $isCompany ? $nombres : null,
            'first_name'       => $partes['first_name'] ?? null,
            'second_name'      => $partes['second_name'] ?? null,
            'first_surname'    => $partes['first_surname'] ?? null,
            'second_surname'   => $partes['second_surname'] ?? null,
            'email'            => $email ?: null,
            'type'             => 'Contratista',
            'status_id'        => self::STATUS_ACTIVO,
        ];

        if ($this->dryRun) {
            $this->stats['colaboradores_creados']++;
            // Retornar instancia sin persistir para continuar el flujo
            return new Collaborator(array_merge(['id' => (string) Str::uuid()], $collaboratorData));
        }

        $collaborator = Collaborator::create($collaboratorData);
        $this->stats['colaboradores_creados']++;
        return $collaborator;
    }

    // ── Paso 2: Comprometidos ────────────────────────────────────────────────

    private function migrarComprometidos(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $data    = array_combine($headers, array_map('trim', $row));
            $codigo  = trim($data['codigocontrato'] ?? '');
            $cuenta  = trim($data['cuenta_contable'] ?? '');
            $centro  = trim($data['centro_decosto'] ?? '');
            $valor   = (float) ($data['valor_contrato'] ?? 0);

            // Buscar en mapa en memoria; si no existe o el UUID no está en BD,
            // hacer fallback con lookup directo por contract_code
            $contractId = $this->codigoMap[$codigo] ?? null;

            if ($contractId) {
                $existe = Contract::where('id', $contractId)->exists();
                if (! $existe) {
                    $contractId = null;
                }
            }

            if (! $contractId) {
                $contractId = Contract::query()
                    ->where('institution_id', self::INSTITUTION_ID)
                    ->where('contract_code', $codigo)
                    ->value('id');
            }

            if (! $contractId) {
                $this->stats['comprometidos_saltados']++;
                $bar->advance();
                continue;
            }

            // Evitar duplicados: mismo contrato + cuenta + centro
            $yaExiste = CommittedValue::query()
                ->where('contract_id', $contractId)
                ->where('accounting_account', $cuenta)
                ->where('cost_center', $centro)
                ->exists();

            if (! $yaExiste && ! $this->dryRun) {
                CommittedValue::create([
                    'institution_id'     => self::INSTITUTION_ID,
                    'contract_id'        => $contractId,
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
        if (! $value || $value === '0000-00-00') {
            return null;
        }

        // dd/mm/yyyy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        // yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function mapEstado(string $estado): string
    {
        return match (strtolower(trim($estado))) {
            'vigente'    => 'Vigente',
            'liquidado'  => 'Liquidado',
            'finalizado',
            'terminado'  => 'Terminado',
            default      => 'Terminado',
        };
    }

    /** Divide "NOMBRES" y "APELLIDOS" en hasta 2 partes cada uno */
    private function partirNombreApellido(string $nombres, string $apellidos): array
    {
        $ns = preg_split('/\s+/', trim($nombres), 2);
        $as = preg_split('/\s+/', trim($apellidos), 2);

        return [
            'first_name'    => $ns[0] ?? null,
            'second_name'   => $ns[1] ?? null,
            'first_surname' => $as[0] ?? null,
            'second_surname' => $as[1] ?? null,
        ];
    }

    private function imprimirReporte(): void
    {
        $dr = $this->dryRun ? ' (simulado)' : '';

        $this->info('');
        $this->info('══════════════════════════════════════════════');
        $this->info("  Resultado de la migración{$dr}");
        $this->info('══════════════════════════════════════════════');
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Colaboradores creados',      $this->stats['colaboradores_creados']],
                ['Colaboradores ya existían',  $this->stats['colaboradores_existentes']],
                ['Contratos creados',          $this->stats['contratos_creados']],
                ['Contratos actualizados',     $this->stats['contratos_actualizados']],
                ['Contratos saltados',         $this->stats['contratos_saltados']],
                ['Comprometidos creados',      $this->stats['comprometidos_creados']],
                ['Comprometidos saltados',     $this->stats['comprometidos_saltados']],
            ]
        );
    }
}
