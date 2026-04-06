@extends('layouts.app')

@section('content')

    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Importar cargos</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Cargue un archivo Excel para registrar cargos, funciones o correos de forma masiva.
            </p>
        </div>
        <nav aria-label="Migas de pan">
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Inicio
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('rh.cargos.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Cargos
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90" aria-current="page">Importar</li>
            </ol>
        </nav>
    </div>

    @include('layouts.partials.rh-subnav')

    {{-- Flash --}}
    @if(session('exito'))
        <div
            x-data="{ visible: true }"
            x-show="visible"
            x-init="setTimeout(() => visible = false, 5000)"
            class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
            role="alert"
        >
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            {{ session('exito') }}
        </div>
    @endif

    {{-- Resultado previo del Job (si existe en caché) --}}
    @if(isset($resultadoPrevio))
        @if(isset($resultadoPrevio['error']))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300" role="alert">
                <p class="font-semibold">La importación anterior falló:</p>
                <p class="mt-1">{{ $resultadoPrevio['error'] }}</p>
            </div>
        @else
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-800/50 dark:bg-green-900/10">
                <p class="font-semibold text-green-800 dark:text-green-300">
                    Resultado de la última importación
                    @if(!empty($resultadoPrevio['completado_at']))
                        ({{ $resultadoPrevio['completado_at'] }})
                    @endif
                    :
                </p>
                <div class="mt-3 flex flex-wrap gap-4 text-sm">
                    <span class="text-green-700 dark:text-green-400">
                        <strong>{{ $resultadoPrevio['imported'] ?? 0 }}</strong> importados
                    </span>
                    <span class="text-amber-700 dark:text-amber-400">
                        <strong>{{ $resultadoPrevio['updated'] ?? 0 }}</strong> actualizados
                    </span>
                    <span class="text-gray-600 dark:text-gray-300">
                        <strong>{{ $resultadoPrevio['skipped'] ?? 0 }}</strong> omitidos
                    </span>
                    @if(!empty($resultadoPrevio['failures']))
                        <span class="text-red-700 dark:text-red-400">
                            <strong>{{ count($resultadoPrevio['failures']) }}</strong> con errores
                        </span>
                    @endif
                </div>
                @if(!empty($resultadoPrevio['failures']))
                    <div class="mt-3 max-h-40 overflow-y-auto rounded-lg bg-white/50 p-3 dark:bg-gray-800/50">
                        @foreach($resultadoPrevio['failures'] as $failure)
                            <p class="text-xs text-red-600 dark:text-red-400">
                                Fila {{ $failure['fila'] }} &mdash; {{ $failure['campo'] }}: {{ implode(', ', $failure['errores']) }}
                            </p>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @endif

    <livewire:rh.position-import />

@endsection
