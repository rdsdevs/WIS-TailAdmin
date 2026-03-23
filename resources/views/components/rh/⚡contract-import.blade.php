<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Jobs\RH\ImportContractsJob;

new class extends Component {
    use WithFileUploads;

    public int     $step            = 1;
    public bool    $sobrescribir    = false;
    public         $archivo         = null;
    public array   $preview         = [];
    public array   $previewHeaders  = [];
    public ?string $errorArchivo    = null;
    public bool    $procesando      = false;
    public array   $categorySummary = [
        'historico' => 0,
        'intermedio' => 0,
        'reciente' => 0,
        'sin_fecha' => 0,
    ];

    public function updatedArchivo(): void
    {
        $this->errorArchivo = null;
        $this->preview = [];
        $this->previewHeaders = [];
        $this->categorySummary = ['historico' => 0, 'intermedio' => 0, 'reciente' => 0, 'sin_fecha' => 0];

        $this->validate([
            'archivo' => 'file|mimes:xlsx|max:5120',
        ], [
            'archivo.mimes' => 'Solo se permiten archivos .xlsx.',
            'archivo.max'   => 'El archivo no puede superar 5 MB.',
        ]);

        try {
            $coleccion = \Maatwebsite\Excel\Facades\Excel::toCollection(null, $this->archivo->getRealPath())
                ->first();

            if ($coleccion === null || $coleccion->isEmpty()) {
                $this->errorArchivo = 'El archivo está vacío o no tiene el formato correcto.';
                return;
            }

            $rows = $coleccion->toArray();

            if (empty($rows)) {
                $this->errorArchivo = 'El archivo está vacío o no tiene el formato correcto.';
                return;
            }

            $this->previewHeaders = array_values($rows[0] ?? []);
            $this->preview = array_slice($rows, 1, 5);

            // Calcular resumen de categorías usando todas las filas de datos
            $this->categorySummary = ['historico' => 0, 'intermedio' => 0, 'reciente' => 0, 'sin_fecha' => 0];

            foreach (array_slice($rows, 1) as $fila) {
                $fechaRaw = is_array($fila)
                    ? ($fila[2] ?? '')
                    : ($fila['fecha_inicio'] ?? ($fila[2] ?? ''));

                $year = $this->extractYear($fechaRaw);

                if ($year === null) {
                    $this->categorySummary['sin_fecha']++;
                } elseif ($year < 2019) {
                    $this->categorySummary['historico']++;
                } elseif ($year <= 2024) {
                    $this->categorySummary['intermedio']++;
                } else {
                    $this->categorySummary['reciente']++;
                }
            }

            $this->step = 2;
        } catch (\Exception $e) {
            $this->errorArchivo = 'No se pudo leer el archivo. Verifique que usa la plantilla oficial.';
        }
    }

    public function importar(): void
    {
        $this->authorize('import', \App\Models\RH\Contract::class);

        $this->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [
            'archivo.required' => 'Seleccione un archivo para importar.',
            'archivo.mimes'    => 'Solo se permiten archivos .xlsx.',
        ]);

        $this->procesando = true;

        $path = $this->archivo->store('imports/contratos/' . auth()->id(), 'local');

        ImportContractsJob::dispatch(
            filePath: $path,
            institutionId: auth()->user()->institution_id,
            overwrite: $this->sobrescribir,
            userId: auth()->id(),
        );

        $this->archivo = null;
        $this->preview = [];
        $this->step = 3;
        $this->procesando = false;
    }

    public function reiniciar(): void
    {
        $this->step = 1;
        $this->archivo = null;
        $this->preview = [];
        $this->previewHeaders = [];
        $this->errorArchivo = null;
        $this->procesando = false;
        $this->categorySummary = ['historico' => 0, 'intermedio' => 0, 'reciente' => 0, 'sin_fecha' => 0];
    }

    private function extractYear(mixed $raw): ?int
    {
        $str = trim((string) $raw);

        if (strlen($str) >= 4) {
            if (str_contains($str, '/') || str_contains($str, '-')) {
                $sep   = str_contains($str, '/') ? '/' : '-';
                $parts = explode($sep, $str);
                if (count($parts) === 3) {
                    $year = strlen($parts[0]) === 4 ? (int) $parts[0] : (int) end($parts);
                    return ($year > 1900 && $year < 2100) ? $year : null;
                }
            }

            if (ctype_digit($str) && strlen($str) === 4) {
                $y = (int) $str;
                return ($y > 1900 && $y < 2100) ? $y : null;
            }
        }

        return null;
    }
};
?>

<div class="space-y-6">

    {{-- Indicador de pasos --}}
    <div class="flex items-center gap-0">
        @foreach([1 => 'Configurar', 2 => 'Cargar archivo', 3 => 'Resultado'] as $n => $label)
            <div class="flex items-center {{ $n < 3 ? 'flex-1' : '' }}">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold
                        {{ $step >= $n ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                        @if($step > $n)
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        @else
                            {{ $n }}
                        @endif
                    </div>
                    <span class="hidden text-sm font-medium sm:block {{ $step >= $n ? 'text-gray-800 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $label }}
                    </span>
                </div>
                @if($n < 3)
                    <div class="mx-3 flex-1 h-px {{ $step > $n ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ── PASO 1: Configuración ── --}}
    @if($step === 1)
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white">Importar contratos</h3>
        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
            Cargue un archivo Excel con los contratos históricos, intermedios o recientes de sus colaboradores.
        </p>

        <div class="space-y-5">

            {{-- Tabla informativa de categorías --}}
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-300">Período</th>
                            <th scope="col" class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-300">num_contrato</th>
                            <th scope="col" class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-300">codigo_contrato</th>
                            <th scope="col" class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-300">Valores comprometidos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700/50 dark:bg-gray-900">
                        <tr>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium">Antes de 2019</td>
                            <td class="px-3 py-2 text-gray-400 dark:text-gray-500">—</td>
                            <td class="px-3 py-2 text-gray-400 dark:text-gray-500">—</td>
                            <td class="px-3 py-2 text-gray-400 dark:text-gray-500">—</td>
                        </tr>
                        <tr class="bg-blue-50/40 dark:bg-blue-900/10">
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium">2019 – 2024</td>
                            <td class="px-3 py-2 text-blue-700 dark:text-blue-400">Requerido</td>
                            <td class="px-3 py-2 text-blue-700 dark:text-blue-400">Requerido</td>
                            <td class="px-3 py-2 text-gray-400 dark:text-gray-500">—</td>
                        </tr>
                        <tr class="bg-green-50/40 dark:bg-green-900/10">
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium">2025 en adelante</td>
                            <td class="px-3 py-2 text-green-700 dark:text-green-400">Requerido</td>
                            <td class="px-3 py-2 text-green-700 dark:text-green-400">Requerido</td>
                            <td class="px-3 py-2 text-green-700 dark:text-green-400">Requerido</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Opción sobrescribir --}}
            <div>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600">
                    <input
                        type="checkbox"
                        wire:model.live="sobrescribir"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                    />
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Sobrescribir contratos existentes</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Si un contrato ya existe en el sistema, se actualizarán sus datos.
                            De lo contrario, los duplicados serán omitidos.
                        </p>
                    </div>
                </label>
            </div>

            {{-- Descarga de plantilla --}}
            <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/50 p-5 dark:border-blue-800/50 dark:bg-blue-900/10">
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Descargue la plantilla oficial para registrar los contratos:
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('rh.contratos.plantilla') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Descargar plantilla contratos (.xlsx)
                    </a>
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Complete el archivo con los datos y luego súbalo a continuación. Máximo 500 filas por archivo.
                </p>
            </div>

            {{-- Zona de carga (drag & drop) --}}
            <div>
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Cargar archivo Excel <span class="text-red-500" aria-hidden="true">*</span>
                </p>
                <div
                    x-data="{
                        isDragging: false,
                        handleDrop(e) {
                            this.isDragging = false;
                            const file = e.dataTransfer.files[0];
                            if (!file) return;
                            const ext = file.name.split('.').pop().toLowerCase();
                            if (ext !== 'xlsx') {
                                alert('Solo se permiten archivos .xlsx');
                                return;
                            }
                            if (file.size > 5 * 1024 * 1024) {
                                alert('El archivo no puede superar 5 MB');
                                return;
                            }
                            @this.upload('archivo', file, () => {}, () => {}, (event) => {});
                        }
                    }"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop($event)"
                    :class="isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'"
                    class="relative rounded-xl border-2 border-dashed p-8 text-center transition-colors"
                    role="region"
                    aria-label="Zona de carga de archivo"
                >
                    <input
                        type="file"
                        wire:model="archivo"
                        accept=".xlsx"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        id="archivo-contratos-input"
                        aria-label="Seleccionar archivo Excel de contratos"
                    />
                    <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                        Arrastre aquí su archivo o <span class="text-blue-600 dark:text-blue-400">haga clic para seleccionar</span>
                    </p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">.xlsx — máximo 5 MB</p>

                    <div wire:loading wire:target="archivo" class="mt-3">
                        <div class="mx-auto h-1.5 w-40 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full w-1/2 animate-pulse rounded-full bg-blue-500"></div>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">Procesando archivo...</p>
                    </div>
                </div>

                @if($errorArchivo)
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">{{ $errorArchivo }}</p>
                @endif
                @error('archivo')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>
    @endif

    {{-- ── PASO 2: Preview y confirmación ── --}}
    @if($step === 2)
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">Vista previa del archivo</h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Verifique que los datos se ven correctamente antes de importar.</p>
            </div>
            <button
                wire:click="reiniciar"
                class="shrink-0 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                &#8592; Volver
            </button>
        </div>

        {{-- Resumen del archivo --}}
        <div class="mb-4 flex flex-wrap gap-3">
            @if($sobrescribir)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                Sobrescribirá contratos existentes
            </span>
            @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                Omitirá duplicados
            </span>
            @endif
        </div>

        {{-- Badges de categorías --}}
        <div class="flex flex-wrap gap-3 mb-4">
            {{-- Total --}}
            <div class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                <span class="font-bold">{{ array_sum($categorySummary) }}</span> contratos encontrados
            </div>

            {{-- Históricos --}}
            @if($categorySummary['historico'] > 0)
            <div class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                <span class="font-bold">{{ $categorySummary['historico'] }}</span> históricos (antes 2019)
            </div>
            @endif

            {{-- Intermedios --}}
            @if($categorySummary['intermedio'] > 0)
            <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                <span class="font-bold">{{ $categorySummary['intermedio'] }}</span> intermedios (2019–2024)
            </div>
            @endif

            {{-- Recientes --}}
            @if($categorySummary['reciente'] > 0)
            <div class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                <span class="font-bold">{{ $categorySummary['reciente'] }}</span> recientes (2025+) — requieren valores comprometidos
            </div>
            @endif

            {{-- Sin fecha --}}
            @if($categorySummary['sin_fecha'] > 0)
            <div class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <span class="font-bold">{{ $categorySummary['sin_fecha'] }}</span> filas sin fecha válida (serán rechazadas)
            </div>
            @endif
        </div>

        {{-- Tabla preview --}}
        @if(!empty($preview))
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        @foreach($previewHeaders as $header)
                            <th scope="col" class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-300">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700/50 dark:bg-gray-900">
                    @foreach($preview as $fila)
                        <tr>
                            @foreach($fila as $celda)
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                    {{ $celda ?? '—' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Mostrando las primeras 5 filas del archivo.</p>
        @endif

        {{-- Botón importar --}}
        <div class="mt-6 flex items-center gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
            <button
                wire:click="importar"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-900"
            >
                <span wire:loading.remove wire:target="importar" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </span>
                <span wire:loading wire:target="importar" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Procesando...
                </span>
                <span wire:loading.remove wire:target="importar">Iniciar importación</span>
            </button>
            <button
                wire:click="reiniciar"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- ── PASO 3: Resultado ── --}}
    @if($step === 3)
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col items-center gap-3 py-4 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30" aria-hidden="true">
                <svg class="h-7 w-7 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-white">Importación en proceso</h3>
            <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
                El archivo fue enviado para procesar. La importación se ejecuta en segundo plano.
                Puede seguir usando el sistema normalmente.
            </p>
        </div>

        <div class="mt-4 flex items-center justify-center gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
            <a href="{{ route('rh.contratos.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                Ver contratos
            </a>
            <button
                wire:click="reiniciar"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Nueva importación
            </button>
        </div>
    </div>
    @endif

</div>
