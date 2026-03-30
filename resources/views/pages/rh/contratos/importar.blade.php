@extends('layouts.app')

@section('title', 'Importar contratos')

@section('content')

    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Importar contratos</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Cargue un archivo Excel para registrar contratos de forma masiva.
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
                    <a href="{{ route('rh.contratos.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Contratos
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90" aria-current="page">Importar</li>
            </ol>
        </nav>
    </div>

    @include('layouts.partials.rh-subnav')

    {{-- Flash de éxito --}}
    @if(session('exito'))
        <div
            x-data="{ visible: true }"
            x-show="visible"
            x-init="setTimeout(() => visible = false, 6000)"
            class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400"
            role="alert"
        >
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('exito') }}
        </div>
    @endif

    {{-- Resultado previo del Job (si existe en caché) --}}
    @if(isset($resultadoPrevio))
        @if(isset($resultadoPrevio['error']))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20" role="alert">
                <p class="text-sm font-semibold text-red-800 dark:text-red-400">Error en la importación anterior</p>
                <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $resultadoPrevio['error'] }}</p>
            </div>
        @else
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    Resultado de la última importación
                    @if(!empty($resultadoPrevio['completado_at']))
                        <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">{{ $resultadoPrevio['completado_at'] }}</span>
                    @endif
                </h2>

                {{-- Métricas principales --}}
                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <div class="rounded-lg bg-green-50 p-3 text-center dark:bg-green-900/20">
                        <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $resultadoPrevio['imported'] ?? 0 }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400">Importados</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-3 text-center dark:bg-blue-900/20">
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $resultadoPrevio['updated'] ?? 0 }}</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">Actualizados</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700/50">
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $resultadoPrevio['skipped'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Omitidos</p>
                    </div>
                    <div class="rounded-lg bg-purple-50 p-3 text-center dark:bg-purple-900/20">
                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ $resultadoPrevio['committed_values_imported'] ?? 0 }}</p>
                        <p class="text-xs text-purple-600 dark:text-purple-400">Valores comprometidos</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-3 text-center dark:bg-amber-900/20">
                        <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $resultadoPrevio['committed_values_skipped'] ?? 0 }}</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">Valores omitidos</p>
                    </div>
                </div>

                {{-- Desglose por categoría --}}
                @if(!empty($resultadoPrevio['category_counts']))
                    <div class="mb-4 flex flex-wrap gap-2">
                        @if(($resultadoPrevio['category_counts']['historico'] ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                {{ $resultadoPrevio['category_counts']['historico'] }} históricos
                            </span>
                        @endif
                        @if(($resultadoPrevio['category_counts']['intermedio'] ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                {{ $resultadoPrevio['category_counts']['intermedio'] }} intermedios
                            </span>
                        @endif
                        @if(($resultadoPrevio['category_counts']['reciente'] ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                {{ $resultadoPrevio['category_counts']['reciente'] }} recientes
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Tabla de errores --}}
                @if(!empty($resultadoPrevio['row_errors']) || !empty($resultadoPrevio['committed_values_errors']))
                    @php
                        $todosLosErrores = array_merge(
                            $resultadoPrevio['row_errors'] ?? [],
                            $resultadoPrevio['committed_values_errors'] ?? []
                        );
                    @endphp
                    <div class="mt-4">
                        <h3 class="mb-2 text-sm font-medium text-red-700 dark:text-red-400">
                            Filas con errores ({{ count($todosLosErrores) }})
                        </h3>
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-red-200 dark:border-red-800">
                            <table class="min-w-full text-xs">
                                <thead class="bg-red-50 dark:bg-red-900/20">
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-left font-medium text-red-700 dark:text-red-400">Fila</th>
                                        <th scope="col" class="px-3 py-2 text-left font-medium text-red-700 dark:text-red-400">Campo</th>
                                        <th scope="col" class="px-3 py-2 text-left font-medium text-red-700 dark:text-red-400">Error</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-red-100 dark:divide-red-900/30">
                                    @foreach($todosLosErrores as $error)
                                        <tr class="bg-white dark:bg-gray-800">
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $error['fila'] }}</td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $error['campo'] }}</td>
                                            <td class="px-3 py-2 text-red-600 dark:text-red-400">{{ $error['mensaje'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <a href="{{ route('rh.contratos.index') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                        Ver contratos
                    </a>
                </div>
            </div>
        @endif
    @endif

    {{-- Componente Livewire --}}
    <livewire:rh.contract-import />

@endsection
