<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int    $step           = 1;
    public string $tipo           = 'cargos';
    public mixed   $archivo        = null;
    public array  $preview        = [];
    public array  $previewHeaders = [];
    public ?string $errorArchivo  = null;
    public bool   $procesando     = false;

    public function updatedArchivo(): void
    {
        $this->errorArchivo = null;
        $this->preview = [];
        $this->previewHeaders = [];

        $this->validate([
            'archivo' => 'file|mimes:xlsx,csv|max:5120',
        ], [
            'archivo.mimes' => 'Solo se permiten archivos .xlsx o .csv.',
            'archivo.max'   => 'El archivo no puede superar 5 MB.',
        ]);

        try {
            $rows = \Maatwebsite\Excel\Facades\Excel::toCollection(null, $this->archivo->getRealPath())
                ->first()
                ?->take(6)
                ->toArray() ?? [];

            if (empty($rows)) {
                $this->errorArchivo = 'El archivo está vacío o no tiene el formato correcto.';
                return;
            }

            $this->previewHeaders = array_values($rows[0] ?? []);
            $this->preview = array_slice($rows, 1, 5);
            $this->step = 2;
        } catch (\Exception $e) {
            $this->errorArchivo = 'No se pudo leer el archivo. Verifique que usa la plantilla oficial.';
        }
    }

    public function importar(): void
    {
        $this->authorize('import', \App\Models\RH\Position::class);

        $this->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
            'tipo'    => ['required', 'in:cargos,funciones,correos'],
        ], [
            'archivo.required' => 'Seleccione un archivo para importar.',
            'archivo.mimes'    => 'Solo se permiten archivos .xlsx o .csv.',
        ]);

        $this->procesando = true;

        $path         = $this->archivo->store('imports/cargos/' . auth()->id(), 'local');
        $institutionId = auth()->user()->institution_id;
        $userId        = auth()->id();

        match ($this->tipo) {
            'cargos'    => \App\Jobs\RH\ImportPositionsJob::dispatch($path, $institutionId, $userId),
            'funciones' => \App\Jobs\RH\ImportPositionFunctionsJob::dispatch($path, $institutionId, $userId),
            'correos'   => \App\Jobs\RH\ImportPositionEmailsJob::dispatch($path, $institutionId, $userId),
        };

        $this->archivo   = null;
        $this->preview   = [];
        $this->step      = 3;
        $this->procesando = false;
    }

    public function reiniciar(): void
    {
        $this->step           = 1;
        $this->archivo        = null;
        $this->preview        = [];
        $this->previewHeaders = [];
        $this->errorArchivo   = null;
        $this->procesando     = false;
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
        <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white">Configuración de la importación</h3>
        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">Seleccione el tipo de datos a importar y descargue la plantilla correspondiente.</p>

        <div class="space-y-5">

            {{-- Tipo de dato --}}
            <div>
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tipo de importación <span class="text-red-500" aria-hidden="true">*</span>
                </p>
                <div class="flex flex-wrap gap-3" role="radiogroup" aria-label="Tipo de importación">

                    {{-- Cargos --}}
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2.5 text-sm transition-colors
                        {{ $tipo === 'cargos' ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                        <input type="radio" wire:model.live="tipo" value="cargos" class="sr-only" />
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Cargos
                    </label>

                    {{-- Funciones del cargo --}}
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2.5 text-sm transition-colors
                        {{ $tipo === 'funciones' ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                        <input type="radio" wire:model.live="tipo" value="funciones" class="sr-only" />
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        Funciones del cargo
                    </label>

                    {{-- Correos del cargo --}}
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2.5 text-sm transition-colors
                        {{ $tipo === 'correos' ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                        <input type="radio" wire:model.live="tipo" value="correos" class="sr-only" />
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        Correos del cargo
                    </label>

                </div>
            </div>

            {{-- Bloque informativo según tipo --}}
            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-sm text-blue-700 dark:bg-blue-900/20 dark:border-blue-800/50 dark:text-blue-300" role="note">
                @if($tipo === 'cargos')
                    Crea o actualiza cargos. Si el cargo ya existe en la institución, actualiza sus datos.
                @elseif($tipo === 'funciones')
                    Agrega nuevas funciones al cargo indicado. No elimina las funciones existentes.
                @else
                    Agrega o reactiva correos del cargo. Si el correo ya existe, lo reactiva si estaba eliminado.
                @endif
            </div>

            {{-- Columnas requeridas y plantilla --}}
            <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/50 p-5 dark:border-blue-800/50 dark:bg-blue-900/10">
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Columnas requeridas en el archivo:
                </p>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    @if($tipo === 'cargos')
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-200">nombre_cargo (*)</code>
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-200">activo</code> (SI/NO)
                    @elseif($tipo === 'funciones')
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-200">cargo (*)</code>
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-200">descripcion_funcion (*)</code>
                    @else
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-200">cargo (*)</code>
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-200">correo_electronico (*)</code>
                    @endif
                </p>
                <a href="{{ route('rh.cargos.plantilla', $tipo) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Descargar plantilla {{ match($tipo) { 'cargos' => 'Cargos', 'funciones' => 'Funciones', 'correos' => 'Correos', default => '' } }} (.xlsx)
                </a>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Complete el archivo con los datos y luego súbalo en el siguiente paso. Máximo 500 filas por archivo.
                </p>
            </div>

            {{-- Zona de carga (drag & drop) --}}
            <div>
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Cargar archivo Excel o CSV <span class="text-red-500" aria-hidden="true">*</span>
                </p>
                <div
                    x-data="{
                        isDragging: false,
                        handleDrop(e) {
                            this.isDragging = false;
                            const file = e.dataTransfer.files[0];
                            if (!file) return;
                            const ext = file.name.split('.').pop().toLowerCase();
                            if (!['xlsx','csv'].includes(ext)) {
                                alert('Solo se permiten archivos .xlsx o .csv');
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
                        accept=".xlsx,.csv"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        id="archivo-input-cargos"
                        aria-label="Seleccionar archivo Excel o CSV"
                    />
                    <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                        Arrastre aquí su archivo o <span class="text-blue-600 dark:text-blue-400">haga clic para seleccionar</span>
                    </p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">.xlsx o .csv — máximo 5 MB</p>

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
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                {{ match($tipo) { 'cargos' => 'Cargos', 'funciones' => 'Funciones del cargo', 'correos' => 'Correos del cargo', default => $tipo } }}
            </span>
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
                <span wire:loading.remove wire:target="importar">
                    Importar {{ match($tipo) { 'cargos' => 'cargos', 'funciones' => 'funciones del cargo', 'correos' => 'correos del cargo', default => 'datos' } }}
                </span>
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
            <a href="{{ route('rh.cargos.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                Ver listado de cargos
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
