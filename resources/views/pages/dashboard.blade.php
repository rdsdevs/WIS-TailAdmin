@extends('layouts.app')

@section('content')

@php
$roleConfig = match($role) {
    'super-admin'        => ['titulo' => 'Panel de control — Super Administrador',    'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300', 'dot' => 'bg-purple-500', 'etiqueta' => 'Sistema global'],
    'admin'              => ['titulo' => 'Panel de control — Administrador',           'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',         'dot' => 'bg-blue-500',   'etiqueta' => 'Mi institución'],
    'rh-manager'         => ['titulo' => 'Panel de control — Recursos Humanos',        'badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300', 'dot' => 'bg-indigo-500', 'etiqueta' => 'Recursos Humanos'],
    'contractor-manager' => ['titulo' => 'Panel de control — Gestión de Contratistas', 'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',     'dot' => 'bg-amber-500',  'etiqueta' => 'Contratistas'],
    'employee-manager'   => ['titulo' => 'Panel de control — Gestión de Empleados',    'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',         'dot' => 'bg-teal-500',   'etiqueta' => 'Empleados'],
    'rh-viewer'          => ['titulo' => 'Panel de control — Consulta RH',             'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',             'dot' => 'bg-gray-400',   'etiqueta' => 'Solo lectura'],
    default              => ['titulo' => 'Panel de control',                           'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',             'dot' => 'bg-gray-400',   'etiqueta' => 'Sistema'],
};
@endphp

<div class="space-y-6">

    {{-- ── Encabezado ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                {{ $roleConfig['titulo'] }}
            </h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Bienvenido, <span class="font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->name }}</span>.
                @if($role === 'super-admin')
                    Vista global del sistema WIS ASCUN.
                @elseif($role === 'rh-viewer')
                    Consulta de información de Recursos Humanos.
                @else
                    Resumen de tu institución.
                @endif
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $roleConfig['badge'] }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $roleConfig['dot'] }}"></span>
            {{ $roleConfig['etiqueta'] }}
        </span>
    </div>

    {{-- ── Métricas principales ─────────────────────────────────────────────── --}}
    @if(in_array($role, ['super-admin', 'admin']))

        {{-- Grid 4 columnas para super-admin / admin --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

            @if($role === 'super-admin')
            {{-- Card: Instituciones --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['institutions']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Instituciones</p>
                </div>
            </div>
            @endif

            {{-- Card: Usuarios --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['users']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Usuarios del sistema</p>
                </div>
            </div>

            {{-- Card: Colaboradores --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['collaborators']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Colaboradores</p>
                </div>
            </div>

            {{-- Card: Contratos totales --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['contracts']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Contratos totales</p>
                </div>
            </div>

        </div>

    @elseif(in_array($role, ['rh-manager', 'rh-viewer']))

        {{-- Grid 3 columnas para rh-manager / rh-viewer --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            {{-- Card: Colaboradores --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['collaborators']) }}</p>
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        Colaboradores
                        @if($role === 'rh-manager')
                        <span class="rounded px-1.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">{{ $metrics['empleados'] }}E / {{ $metrics['contratistas'] }}C</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card: Contratos totales --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['contracts']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Contratos totales</p>
                </div>
            </div>

            {{-- Card: Contratos vigentes --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['vigentes']) }}</p>
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        Contratos vigentes
                        <span class="rounded px-1.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Activos</span>
                    </div>
                </div>
            </div>

        </div>

    @elseif($role === 'contractor-manager')

        {{-- Grid 4 cards especializadas para contractor-manager --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

            {{-- Card: Contratistas activos --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['contratistas']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Contratistas activos</p>
                </div>
            </div>

            {{-- Card: Contratos vigentes --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['vigentes']) }}</p>
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        Contratos vigentes
                        <span class="rounded px-1.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Activos</span>
                    </div>
                </div>
            </div>

            {{-- Card: Certificaciones --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#EEF2FF]">
                        <svg class="h-6 w-6 text-[#2a31d8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ number_format($metrics['certificaciones_total'] ?? 0) }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Certificaciones</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 border-t border-gray-100 pt-3 dark:border-gray-700">
                    <span class="flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $metrics['certificaciones_verificadas'] ?? 0 }} Verificadas
                    </span>
                    <span class="text-gray-200 dark:text-gray-700 select-none">|</span>
                    <span class="flex items-center gap-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $metrics['certificaciones_por_verificar'] ?? 0 }} Por verificar
                    </span>
                </div>
            </div>

            {{-- Card: Terminados --}}
            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['terminados']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Contratos terminados</p>
                </div>
            </div>

        </div>

        {{-- ── Actividad reciente — contractor-manager ── --}}
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- Últimos contratistas registrados --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Últimos contratistas registrados
                    <span class="ml-1 text-xs font-normal text-gray-400">(últimos 30 días)</span>
                </h4>
                @forelse($metrics['recientes_contratistas'] ?? [] as $colaborador)
                    <div class="flex items-center gap-3 py-2.5 border-b border-gray-100 last:border-0 dark:border-gray-700">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 text-xs font-bold dark:bg-amber-900/30 dark:text-amber-300">
                            @if($colaborador->is_company)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                            @else
                                {{ strtoupper(substr($colaborador->first_name ?? '?', 0, 1)) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-800 dark:text-white">
                                @if($colaborador->is_company)
                                    {{ $colaborador->company_name ?? '—' }}
                                @else
                                    {{ trim(($colaborador->first_name ?? '') . ' ' . ($colaborador->first_surname ?? '')) ?: '—' }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-400">{{ $colaborador->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">Sin registros en los últimos 30 días.</p>
                @endforelse
            </div>

            {{-- Últimos contratos registrados --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Últimos contratos registrados
                    <span class="ml-1 text-xs font-normal text-gray-400">(últimos 30 días)</span>
                </h4>
                @forelse($metrics['recientes_contratos'] ?? [] as $contrato)
                    <div class="flex items-center gap-3 py-2.5 border-b border-gray-100 last:border-0 dark:border-gray-700">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-xs font-bold dark:bg-blue-900/30 dark:text-blue-300">
                            {{ strtoupper(substr($contrato->collaborator->first_name ?? 'C', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-800 dark:text-white">
                                {{ $contrato->contract_number ?? '—' }}
                            </p>
                            <p class="truncate text-xs text-gray-400">
                                @if($contrato->collaborator)
                                    @if($contrato->collaborator->is_company)
                                        {{ $contrato->collaborator->company_name ?? '—' }}
                                    @else
                                        {{ trim(($contrato->collaborator->first_name ?? '') . ' ' . ($contrato->collaborator->first_surname ?? '')) ?: '—' }}
                                    @endif
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium
                            @if(($contrato->status ?? '') === 'Vigente') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                            @elseif(($contrato->status ?? '') === 'Por vencer') bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                            @else bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 @endif">
                            {{ $contrato->status ?? '—' }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">Sin registros en los últimos 30 días.</p>
                @endforelse
            </div>

        </div>

    @elseif($role === 'employee-manager')

        <livewire:rh.employee-dashboard />

    @endif

    {{-- ── Indicadores de estado RH (solo para roles que no tienen sus 4 cards en el grid anterior) ── --}}
    @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'rh-viewer']))
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- Contratos vigentes --}}
        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $metrics['vigentes'] }}</p>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    Contratos vigentes
                    <span class="rounded px-1.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Activos</span>
                </div>
            </div>
        </div>

        {{-- Por vencer --}}
        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $metrics['por_vencer'] ?? 0 }}</p>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    Por vencer
                    <span class="rounded px-1.5 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">30 días</span>
                </div>
            </div>
        </div>

        {{-- Terminados --}}
        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $metrics['terminados'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Contratos terminados</p>
            </div>
        </div>

    </div>
    @endif

    {{-- ── Accesos rápidos ──────────────────────────────────────────────────── --}}
    @php
        $modulosProximos = match($role) {
            'super-admin', 'admin' => ['Contabilidad', 'Inventario', 'Certificados'],
            'contractor-manager' => [],
            'employee-manager' => [],
            default => ['Contabilidad', 'Inventario', 'Certificados'],
        };
    @endphp
    <div>
        <h3 class="mb-3 text-base font-semibold text-gray-700 dark:text-gray-300">Accesos rápidos</h3>
        <div class="flex flex-wrap gap-3">

            {{-- Grupo de módulos activos --}}
            @php
                $tieneActivos = in_array($role, ['super-admin', 'admin', 'rh-manager', 'contractor-manager', 'employee-manager', 'rh-viewer']);
            @endphp
            @if($tieneActivos)
            {{-- Grupo de accesos rápidos --}}
            @if($role === 'contractor-manager')
            {{-- contractor-manager: Colaboradores · Contratos · Certificados --}}
            <div class="flex divide-x divide-gray-200 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 dark:divide-gray-700">
                <a href="{{ route('rh.colaboradores.index') }}"
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Colaboradores
                    <span class="text-xs text-gray-400 dark:text-gray-500">— Contratistas</span>
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </a>
                <a href="{{ route('rh.contratos.index') }}"
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Contratos
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </a>
                {{-- Firmas de certificados --}}
                <a href="#" {{-- TODO: route('certificados.firmas.index') --}}
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Firmas
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </a>
            </div>
            @else
            {{-- Grupo estándar para otros roles --}}
            <div class="flex divide-x divide-gray-200 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 dark:divide-gray-700">

                {{-- Colaboradores --}}
                @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'employee-manager', 'rh-viewer']))
                <a href="{{ route('rh.colaboradores.index') }}"
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Colaboradores
                    @if($role === 'employee-manager')
                        <span class="text-xs text-gray-400 dark:text-gray-500">— Empleados</span>
                    @endif
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </a>
                @endif

                {{-- Contratos --}}
                @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'employee-manager', 'rh-viewer']))
                <a href="{{ route('rh.contratos.index') }}"
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Contratos
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </a>
                @endif

                {{-- Departamentos --}}
                @if(in_array($role, ['super-admin', 'admin', 'rh-manager']))
                <a href="{{ route('rh.departamentos.index') }}"
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Departamentos
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </a>
                @endif

                {{-- Cargos --}}
                @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'employee-manager']))
                <a href="{{ route('rh.cargos.index') }}"
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Cargos
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                </a>
                @endif

                {{-- Firmas de certificados --}}
                @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'employee-manager']))
                <a href="#" {{-- TODO: route('certificados.firmas.index') --}}
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Firmas
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </a>
                @endif

                {{-- Usuarios --}}
                @if(in_array($role, ['super-admin', 'admin']))
                <a href="{{ route('admin.usuarios.index') }}"
                   class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Usuarios
                    <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </a>
                @endif

            </div>
            @endif {{-- fin @else (roles estándar) --}}
            @endif {{-- fin @if($tieneActivos) --}}


            {{-- Grupo de módulos próximos (dashed, deshabilitados) --}}
            @if(count($modulosProximos) > 0)
            <div class="flex divide-x divide-gray-200 overflow-hidden rounded-xl border border-dashed border-gray-200 opacity-50 dark:border-gray-700 dark:divide-gray-700">
                @foreach($modulosProximos as $modulo)
                <span class="flex items-center gap-2.5 bg-white px-4 py-2.5 text-sm font-medium text-gray-400 cursor-not-allowed select-none dark:bg-gray-800 dark:text-gray-500">
                    {{ $modulo }}
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                @endforeach
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
