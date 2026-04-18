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

        {{-- ============================================================ --}}
        {{-- Columna principal --}}
        {{-- ============================================================ --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Card: Información básica --}}
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

            {{-- Tabs: Funciones / Responsabilidades / Autoridades / Correos --}}
            <div x-data="{ tab: 'funciones' }">

                {{-- Cabecera de tabs --}}
                <div class="flex items-center gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                     role="tablist"
                     aria-label="Secciones del cargo">

                    {{-- Tab Funciones --}}
                    <button type="button"
                            role="tab"
                            :aria-selected="tab === 'funciones'"
                            @click="tab = 'funciones'"
                            :class="tab === 'funciones'
                                ? 'bg-violet-600 text-white shadow-sm'
                                : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span class="hidden sm:inline">Funciones</span>
                        <span :class="tab === 'funciones' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                              class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold">
                            {{ $cargo->functions->count() }}
                        </span>
                    </button>

                    {{-- Tab Responsabilidades --}}
                    <button type="button"
                            role="tab"
                            :aria-selected="tab === 'responsabilidades'"
                            @click="tab = 'responsabilidades'"
                            :class="tab === 'responsabilidades'
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="hidden sm:inline">Responsabilidades</span>
                        <span :class="tab === 'responsabilidades' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                              class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold">
                            {{ $cargo->responsibilities->count() }}
                        </span>
                    </button>

                    {{-- Tab Autoridades --}}
                    <button type="button"
                            role="tab"
                            :aria-selected="tab === 'autoridades'"
                            @click="tab = 'autoridades'"
                            :class="tab === 'autoridades'
                                ? 'bg-amber-500 text-white shadow-sm'
                                : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="hidden sm:inline">Autoridades</span>
                        <span :class="tab === 'autoridades' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                              class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold">
                            {{ $cargo->authorities->count() }}
                        </span>
                    </button>

                    {{-- Tab Correos --}}
                    <button type="button"
                            role="tab"
                            :aria-selected="tab === 'correos'"
                            @click="tab = 'correos'"
                            :class="tab === 'correos'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="hidden sm:inline">Correos</span>
                        <span :class="tab === 'correos' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                              class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold">
                            {{ $cargo->emails->count() }}
                        </span>
                    </button>

                </div>

                {{-- ── Panel: Funciones ──────────────────────────────────── --}}
                <div x-show="tab === 'funciones'"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     role="tabpanel"
                     class="mt-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @forelse($cargo->functions as $funcion)
                        <div class="flex items-start gap-3 {{ !$loop->last ? 'mb-3 pb-3 border-b border-gray-100 dark:border-gray-700' : '' }}">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-medium text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
                                {{ $loop->iteration }}
                            </span>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $funcion->description }}</p>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10">
                            <svg class="mb-3 h-10 w-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-sm text-gray-400 italic dark:text-gray-500">Sin funciones registradas.</p>
                        </div>
                    @endforelse
                </div>

                {{-- ── Panel: Responsabilidades ──────────────────────────── --}}
                <div x-show="tab === 'responsabilidades'"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     role="tabpanel"
                     class="mt-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @forelse($cargo->responsibilities as $responsabilidad)
                        <div class="flex items-start gap-3 {{ !$loop->last ? 'mb-3 pb-3 border-b border-gray-100 dark:border-gray-700' : '' }}">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                {{ $loop->iteration }}
                            </span>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $responsabilidad->description }}</p>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10">
                            <svg class="mb-3 h-10 w-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-gray-400 italic dark:text-gray-500">Sin responsabilidades registradas.</p>
                        </div>
                    @endforelse
                </div>

                {{-- ── Panel: Autoridades ────────────────────────────────── --}}
                <div x-show="tab === 'autoridades'"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     role="tabpanel"
                     class="mt-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @forelse($cargo->authorities as $autoridad)
                        <div class="flex items-start gap-3 {{ !$loop->last ? 'mb-3 pb-3 border-b border-gray-100 dark:border-gray-700' : '' }}">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                {{ $loop->iteration }}
                            </span>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $autoridad->description }}</p>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10">
                            <svg class="mb-3 h-10 w-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <p class="text-sm text-gray-400 italic dark:text-gray-500">Sin autoridades registradas.</p>
                        </div>
                    @endforelse
                </div>

                {{-- ── Panel: Correos ────────────────────────────────────── --}}
                <div x-show="tab === 'correos'"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     role="tabpanel"
                     class="mt-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @forelse($cargo->emails as $correo)
                        <div class="flex items-center gap-2 {{ !$loop->last ? 'mb-2' : '' }}">
                            <svg class="h-4 w-4 shrink-0 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $correo->email }}</span>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10">
                            <svg class="mb-3 h-10 w-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                            </svg>
                            <p class="text-sm text-gray-400 italic dark:text-gray-500">Sin correos registrados.</p>
                        </div>
                    @endforelse
                </div>

            </div>{{-- /x-data tabs --}}

        </div>

        {{-- ============================================================ --}}
        {{-- Columna lateral --}}
        {{-- ============================================================ --}}
        <div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white">Resumen</h3>
                <dl class="space-y-3">
                    <div class="flex items-center justify-between">
                        <dt class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                            Funciones
                        </dt>
                        <dd class="text-sm font-semibold text-gray-800 dark:text-white">{{ $cargo->functions->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            Responsabilidades
                        </dt>
                        <dd class="text-sm font-semibold text-gray-800 dark:text-white">{{ $cargo->responsibilities->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Autoridades
                        </dt>
                        <dd class="text-sm font-semibold text-gray-800 dark:text-white">{{ $cargo->authorities->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Correos
                        </dt>
                        <dd class="text-sm font-semibold text-gray-800 dark:text-white">{{ $cargo->emails->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>

    </div>
@endsection
