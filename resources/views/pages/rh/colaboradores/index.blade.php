@extends('layouts.app')

@section('content')
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Colaboradores</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Gestione los empleados y contratistas de ASCUN desde un solo lugar.
            </p>
        </div>
        <nav aria-label="Migas de pan">
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Inicio
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">Colaboradores</li>
            </ol>
        </nav>
    </div>

    {{-- Flash de sesión --}}
    @if(session('success'))
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 4000)"
             class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
             role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Sub-navegación RH --}}
    @include('layouts.partials.rh-subnav')

    {{-- Tarjetas de resumen --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        {{-- Total colaboradores --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total colaboradores</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>

        {{-- Empleados activos --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
            <p class="text-xs font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">Empleados</p>
            <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['empleados'] }}</p>
        </div>

        {{-- Contratistas vigentes --}}
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
            <p class="text-xs font-medium uppercase tracking-wide text-purple-600 dark:text-purple-400">Contratistas</p>
            <p class="mt-1 text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $stats['contratistas'] }}</p>
        </div>

        {{-- Contratos por vencer --}}
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <p class="text-xs font-medium uppercase tracking-wide text-red-600 dark:text-red-400">Contratos por vencer</p>
            <p class="mt-1 text-2xl font-bold text-red-700 dark:text-red-300">{{ $stats['porVencer'] }}</p>
        </div>
    </div>

    {{-- Lista de colaboradores (Livewire) --}}
    <livewire:rh.collaborator-list />
@endsection
