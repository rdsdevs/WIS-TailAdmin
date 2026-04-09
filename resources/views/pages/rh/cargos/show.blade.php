@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $cargo->name }}</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Detalle del cargo
            </p>
        </div>
        <div class="flex items-center gap-3">
            @can('update', $cargo)
            <a href="{{ route('rh.cargos.edit', $cargo) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
            @endcan
            <nav aria-label="Migas de pan">
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Inicio
                            <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('rh.cargos.index') }}"
                           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Cargos
                            <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm font-medium text-gray-800 dark:text-white/90">Detalle</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('layouts.partials.rh-subnav')

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Datos principales --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">Información del cargo</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nombre</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $cargo->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Estado</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cargo->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $cargo->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Funciones --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Funciones del cargo
                    <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        {{ $cargo->functions->count() }}
                    </span>
                </h3>
                @forelse($cargo->functions as $funcion)
                    <div class="flex items-start gap-3 {{ !$loop->last ? 'mb-3 pb-3 border-b border-gray-100 dark:border-gray-700' : '' }}">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            {{ $loop->iteration }}
                        </span>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $funcion->description }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic dark:text-gray-500">No hay funciones registradas para este cargo.</p>
                @endforelse
            </div>
        </div>

        {{-- Correos --}}
        <div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Correos informativos
                    <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        {{ $cargo->emails->count() }}
                    </span>
                </h3>
                @forelse($cargo->emails as $correo)
                    <div class="flex items-center gap-2 {{ !$loop->last ? 'mb-2' : '' }}">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $correo->email }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic dark:text-gray-500">Sin correos asociados.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
