@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Contratos</h2>
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
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">Contratos</li>
            </ol>
        </nav>
    </div>

    @if(session('exito'))
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 4000)"
             class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
             role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            {{ session('exito') }}
        </div>
    @endif

    @include('layouts.partials.rh-subnav')

    {{-- Tabs --}}
    <div
        x-data="{ tabActivo: '{{ request('tab', 'activos') }}' }"
        class="space-y-4">

        {{-- Barra de tabs --}}
        <div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-700">
            <button
                @click="tabActivo = 'activos'"
                :class="tabActivo === 'activos'
                    ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="px-4 py-3 text-sm font-medium focus:outline-none">
                Activos
                @php $totalActivos = $contratos->where('is_active', true)->count(); @endphp
                @if($totalActivos > 0)
                    <span class="ml-1.5 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        {{ $totalActivos }}
                    </span>
                @endif
            </button>
            <button
                @click="tabActivo = 'por_vencer'"
                :class="tabActivo === 'por_vencer'
                    ? 'border-b-2 border-red-500 text-red-600 dark:text-red-400 dark:border-red-400'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="px-4 py-3 text-sm font-medium focus:outline-none">
                Por vencer (30 días)
                @php $totalPorVencer = $contratos->filter(fn($c) => $c->is_active && $c->end_date && $c->end_date->diffInDays(now()) <= 30 && $c->end_date->isFuture())->count(); @endphp
                @if($totalPorVencer > 0)
                    <span class="ml-1.5 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        {{ $totalPorVencer }}
                    </span>
                @endif
            </button>
            <button
                @click="tabActivo = 'todos'"
                :class="tabActivo === 'todos'
                    ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="px-4 py-3 text-sm font-medium focus:outline-none">
                Todos
            </button>

            <div class="ml-auto pb-2">
                @can('create', \App\Models\RH\Contract::class)
                    <a href="{{ route('rh.contratos.create') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Nuevo contrato
                    </a>
                @endcan
            </div>
        </div>

        @php
            $tiposContrato = [
                'indefinite' => 'Término indefinido',
                'fixed_term' => 'Término fijo',
                'contractor' => 'Contratista',
                'intern'     => 'Practicante',
            ];
        @endphp

        {{-- Tabla: Activos --}}
        <div x-show="tabActivo === 'activos'" x-cloak>
            @include('pages.rh.contratos._tabla', [
                'filas' => $contratos->where('is_active', true),
                'tiposContrato' => $tiposContrato,
            ])
        </div>

        {{-- Tabla: Por vencer --}}
        <div x-show="tabActivo === 'por_vencer'" x-cloak>
            @include('pages.rh.contratos._tabla', [
                'filas' => $contratos->filter(fn($c) => $c->is_active && $c->end_date && $c->end_date->diffInDays(now()) <= 30 && $c->end_date->isFuture()),
                'tiposContrato' => $tiposContrato,
            ])
        </div>

        {{-- Tabla: Todos --}}
        <div x-show="tabActivo === 'todos'">
            @include('pages.rh.contratos._tabla', [
                'filas' => $contratos,
                'tiposContrato' => $tiposContrato,
            ])
        </div>

    </div>
@endsection
