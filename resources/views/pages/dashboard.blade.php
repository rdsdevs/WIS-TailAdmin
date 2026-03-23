@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ── Encabezado ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                {{ $isSuperAdmin ? 'Panel de control — Super Administrador' : 'Panel de control' }}
            </h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Bienvenido, <span class="font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->name }}</span>.
                {{ $isSuperAdmin ? 'Vista global del sistema WIS ASCUN.' : 'Resumen de tu institución.' }}
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium
            {{ $isSuperAdmin ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $isSuperAdmin ? 'bg-purple-500' : 'bg-blue-500' }}"></span>
            {{ $isSuperAdmin ? 'Sistema global' : 'Mi institución' }}
        </span>
    </div>

    {{-- ── Métricas principales ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        @if($isSuperAdmin)
        {{-- Card: Instituciones --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['institutions']) }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Instituciones</p>
        </div>
        @endif

        {{-- Card: Usuarios --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['users']) }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Usuarios del sistema</p>
        </div>

        {{-- Card: Colaboradores --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
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
            <div class="flex items-center justify-between">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                    <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($metrics['contracts']) }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Contratos totales</p>
        </div>

    </div>

    {{-- ── Estado módulo RH ─────────────────────────────────────────────────── --}}
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

    {{-- ── Accesos rápidos ──────────────────────────────────────────────────── --}}
    <div>
        <h3 class="mb-4 text-base font-semibold text-gray-700 dark:text-gray-300">Accesos rápidos</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-{{ $isSuperAdmin ? '5' : '4' }}">

            {{-- RH: Colaboradores --}}
            <a href="{{ route('rh.colaboradores.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-900/30 dark:text-blue-400 dark:group-hover:bg-blue-600 dark:group-hover:text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <span class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">Colaboradores</span>
            </a>

            {{-- RH: Contratos --}}
            <a href="{{ route('rh.contratos.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 text-orange-600 transition-colors group-hover:bg-orange-500 group-hover:text-white dark:bg-orange-900/30 dark:text-orange-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <span class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">Contratos</span>
            </a>

            {{-- RH: Departamentos --}}
            <a href="{{ route('rh.departamentos.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-900/30 dark:text-indigo-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <span class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">Departamentos</span>
            </a>

            {{-- RH: Cargos --}}
            <a href="{{ route('rh.cargos.index') }}"
               class="group flex flex-col items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 transition-colors group-hover:bg-teal-600 group-hover:text-white dark:bg-teal-900/30 dark:text-teal-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <span class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">Cargos</span>
            </a>

            {{-- Módulos próximos (deshabilitados) --}}
            @foreach([
                ['label' => 'Contabilidad', 'color' => 'gray'],
                ['label' => 'Inventario',   'color' => 'gray'],
                ['label' => 'Certificados', 'color' => 'gray'],
            ] as $module)
            <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-200 bg-gray-50/50 p-5 dark:border-gray-700 dark:bg-gray-800/40">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-center">
                    <span class="block text-sm font-medium text-gray-400 dark:text-gray-500">{{ $module['label'] }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-600">Próximamente</span>
                </div>
            </div>
            @endforeach

        </div>
    </div>

</div>
@endsection
