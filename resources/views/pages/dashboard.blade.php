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
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['institutions']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Instituciones</p>
            </div>
            @endif

            {{-- Card: Usuarios --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['users']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Usuarios del sistema</p>
            </div>

            {{-- Card: Colaboradores --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['collaborators']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Colaboradores</p>
                <div class="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-blue-500"></span>{{ $metrics['empleados'] }} empleados</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-purple-500"></span>{{ $metrics['contratistas'] }} contratistas</span>
                </div>
            </div>

            {{-- Card: Contratos totales --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                    <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['contracts']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Contratos totales</p>
            </div>

        </div>

    @elseif(in_array($role, ['rh-manager', 'rh-viewer']))

        {{-- Grid 3 columnas para rh-manager / rh-viewer --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            {{-- Card: Colaboradores --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['collaborators']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Colaboradores</p>
                @if($role === 'rh-manager')
                <div class="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-blue-500"></span>{{ $metrics['empleados'] }} empleados</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-purple-500"></span>{{ $metrics['contratistas'] }} contratistas</span>
                </div>
                @endif
            </div>

            {{-- Card: Contratos totales --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                    <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['contracts']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Contratos totales</p>
            </div>

            {{-- Card: Contratos vigentes --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['vigentes']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Contratos vigentes</p>
            </div>

        </div>

    @elseif($role === 'contractor-manager')

        {{-- Grid 4 cards especializadas para contractor-manager --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

            {{-- Card principal: Contratistas activos --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-800/50 dark:bg-amber-900/10">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-amber-700 dark:text-amber-400">{{ number_format($metrics['contratistas']) }}</p>
                <p class="mt-1 text-sm text-amber-600 dark:text-amber-500">Contratistas activos</p>
            </div>

            {{-- Card: Contratos vigentes --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-800/50 dark:bg-emerald-900/10">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-emerald-700 dark:text-emerald-400">{{ number_format($metrics['vigentes']) }}</p>
                <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-500">Contratos vigentes</p>
            </div>

            {{-- Card: Por vencer --}}
            <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-800/30 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-amber-700 dark:text-amber-400">{{ number_format($metrics['por_vencer']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Por vencer (30 días)</p>
            </div>

            {{-- Card: Terminados --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-700 dark:text-gray-300">{{ number_format($metrics['terminados']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Contratos terminados</p>
            </div>

        </div>

    @elseif($role === 'employee-manager')

        {{-- Grid 4 cards especializadas para employee-manager --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

            {{-- Card principal: Empleados activos --}}
            <div class="rounded-xl border border-teal-200 bg-teal-50 p-5 shadow-sm dark:border-teal-800/50 dark:bg-teal-900/10">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-900/40">
                    <svg class="h-5 w-5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-teal-700 dark:text-teal-400">{{ number_format($metrics['empleados']) }}</p>
                <p class="mt-1 text-sm text-teal-600 dark:text-teal-500">Empleados activos</p>
            </div>

            {{-- Card: Contratos vigentes --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-800/50 dark:bg-emerald-900/10">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-emerald-700 dark:text-emerald-400">{{ number_format($metrics['vigentes']) }}</p>
                <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-500">Contratos vigentes</p>
            </div>

            {{-- Card: Por vencer --}}
            <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-800/30 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-amber-700 dark:text-amber-400">{{ number_format($metrics['por_vencer']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Por vencer (30 días)</p>
            </div>

            {{-- Card: Terminados --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-700 dark:text-gray-300">{{ number_format($metrics['terminados']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Contratos terminados</p>
            </div>

        </div>

    @endif

    {{-- ── Indicadores de estado RH (roles que NO tienen sus 4 cards en el grid anterior) ── --}}
    @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'rh-viewer']))
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- Contratos vigentes --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800/50 dark:bg-emerald-900/10">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $metrics['vigentes'] }}</p>
                    <p class="text-sm font-medium text-emerald-600 dark:text-emerald-500">Contratos vigentes</p>
                </div>
            </div>
        </div>

        {{-- Por vencer --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800/50 dark:bg-amber-900/10">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $metrics['por_vencer'] }}</p>
                    <p class="text-sm font-medium text-amber-600 dark:text-amber-500">Por vencer (30 días)</p>
                </div>
            </div>
        </div>

        {{-- Terminados --}}
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $metrics['terminados'] }}</p>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Contratos terminados</p>
                </div>
            </div>
        </div>

    </div>
    @elseif($role === 'employee-manager')
    {{-- Mini-cards de estado con contexto especializado para employee-manager --}}
    @php
        $tipoLabel = 'empleados';
    @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800/50 dark:bg-emerald-900/10">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $metrics['vigentes'] }}</p>
                    <p class="text-sm font-medium text-emerald-600 dark:text-emerald-500">Contratos vigentes ({{ $tipoLabel }})</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800/50 dark:bg-amber-900/10">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $metrics['por_vencer'] }}</p>
                    <p class="text-sm font-medium text-amber-600 dark:text-amber-500">Por vencer ({{ $tipoLabel }})</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $metrics['terminados'] }}</p>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Terminados ({{ $tipoLabel }})</p>
                </div>
            </div>
        </div>

    </div>
    @endif

    {{-- ── Accesos rápidos ──────────────────────────────────────────────────── --}}
    <div>
        <h3 class="mb-4 text-base font-semibold text-gray-700 dark:text-gray-300">Accesos rápidos</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">

            {{-- Colaboradores — visible para todos los roles RH --}}
            @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'contractor-manager', 'employee-manager', 'rh-viewer']))
            <a href="{{ route('rh.colaboradores.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-900/30 dark:text-blue-400 dark:group-hover:bg-blue-600 dark:group-hover:text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <div class="text-center">
                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Colaboradores</span>
                    @if($role === 'contractor-manager')
                        <span class="text-xs text-gray-400 dark:text-gray-500">Contratistas</span>
                    @elseif($role === 'employee-manager')
                        <span class="text-xs text-gray-400 dark:text-gray-500">Empleados</span>
                    @endif
                </div>
            </a>
            @endif

            {{-- Contratos — visible para todos los roles RH --}}
            @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'contractor-manager', 'employee-manager', 'rh-viewer']))
            <a href="{{ route('rh.contratos.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 text-orange-600 transition-colors group-hover:bg-orange-500 group-hover:text-white dark:bg-orange-900/30 dark:text-orange-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <span class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">Contratos</span>
            </a>
            @endif

            {{-- Departamentos — solo super-admin, admin, rh-manager --}}
            @if(in_array($role, ['super-admin', 'admin', 'rh-manager']))
            <a href="{{ route('rh.departamentos.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-900/30 dark:text-indigo-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <span class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">Departamentos</span>
            </a>
            @endif

            {{-- Cargos — super-admin, admin, rh-manager, employee-manager --}}
            @if(in_array($role, ['super-admin', 'admin', 'rh-manager', 'employee-manager']))
            <a href="{{ route('rh.cargos.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 transition-colors group-hover:bg-teal-600 group-hover:text-white dark:bg-teal-900/30 dark:text-teal-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <span class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">Cargos</span>
            </a>
            @endif

            {{-- Usuarios — solo super-admin y admin --}}
            @if(in_array($role, ['super-admin', 'admin']))
            <a href="{{ route('admin.usuarios.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 text-purple-600 transition-colors group-hover:bg-purple-600 group-hover:text-white dark:bg-purple-900/30 dark:text-purple-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <span class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">Usuarios</span>
            </a>
            @endif

        </div>
    </div>

    {{-- ── Módulos próximos ──────────────────────────────────────────────────── --}}
    @php
        $modulosProximos = match($role) {
            'super-admin', 'admin' => [
                ['label' => 'Contabilidad'],
                ['label' => 'Inventario'],
                ['label' => 'Certificados'],
            ],
            'contractor-manager', 'employee-manager' => [
                ['label' => 'Certificados'],
            ],
            default => [
                ['label' => 'Contabilidad'],
                ['label' => 'Inventario'],
                ['label' => 'Certificados'],
            ],
        };
    @endphp

    @if(count($modulosProximos) > 0)
    <div>
        <h3 class="mb-4 text-base font-semibold text-gray-700 dark:text-gray-300">Próximamente</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($modulosProximos as $modulo)
            <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-200 bg-gray-50/50 p-5 dark:border-gray-700 dark:bg-gray-800/40">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-center">
                    <span class="block text-sm font-medium text-gray-400 dark:text-gray-500">{{ $modulo['label'] }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-600">Próximamente</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
