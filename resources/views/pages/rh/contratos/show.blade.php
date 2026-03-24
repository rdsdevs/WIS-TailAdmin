@extends('layouts.app')

@section('title', 'Contrato ' . ($contrato->contract_code ?? 'Sin código'))

@section('content')
    {{-- ── Breadcrumb ─────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                Detalle del contrato
            </h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                {{ $contrato->contract_code ?? $contrato->contract_number ?? 'Sin código' }}
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
                <li>
                    <a href="{{ route('rh.contratos.index') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Contratos
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $contrato->contract_code ?? $contrato->contract_number ?? 'Sin código' }}
                </li>
            </ol>
        </nav>
    </div>

    @include('layouts.partials.rh-subnav')

    {{-- ── Flash messages ──────────────────────────────────────────────────────── --}}
    @if(session('success') || session('exito'))
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 4000)"
             class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
             role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            {{ session('success') ?? session('exito') }}
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 6000)"
             class="mb-4 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
             role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Banner: terminación anticipada ─────────────────────────────────────── --}}
    @if($contrato->isEarlyTerminated())
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800/50 dark:bg-red-900/20">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-red-800 dark:text-red-300">
                    Contrato terminado anticipadamente
                </p>
                <p class="mt-0.5 text-xs text-red-700 dark:text-red-400">
                    Este contrato fue terminado de forma anticipada el
                    <strong>{{ $contrato->early_termination_date?->format('d/m/Y') ?? '—' }}</strong>.
                    Consulte la sección de terminación anticipada para más detalles.
                </p>
            </div>
        </div>
    @endif

    {{-- ── Card: encabezado del contrato ──────────────────────────────────────── --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

        {{-- Fila superior: avatar + nombre + badges --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-4">
                {{-- Avatar --}}
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-xl font-bold
                    {{ $contrato->collaborator?->type === 'Empleado'
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                        : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}"
                     aria-hidden="true">
                    @if($contrato->collaborator?->is_company)
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    @else
                        {{ strtoupper(
                            substr($contrato->collaborator?->first_name ?? '', 0, 1) .
                            substr($contrato->collaborator?->first_surname ?? '', 0, 1)
                        ) }}
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $contrato->collaborator?->full_name ?? '—' }}
                    </h3>

                    {{-- Cargo --}}
                    @if($contrato->position)
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            {{ $contrato->position->name }}
                        </p>
                    @endif

                    {{-- Badges: tipo + status --}}
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        {{-- Tipo de colaborador --}}
                        @if($contrato->collaborator?->type === 'Empleado')
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                Empleado
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                Contratista
                            </span>
                        @endif

                        {{-- Status del contrato --}}
                        @php
                            $statusClasses = match($contrato->status) {
                                'Vigente'   => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'Terminado' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                'Liquidado' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                'Borrador'  => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'Vencido'   => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                default     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                            {{ $contrato->status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fila de fechas --}}
        <div class="mt-5 flex flex-wrap items-center gap-5 border-t border-gray-100 pt-4 dark:border-gray-700">
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                </svg>
                <span>Inicio:
                    <strong class="font-medium text-gray-900 dark:text-white">
                        {{ $contrato->start_date?->format('d/m/Y') ?? '—' }}
                    </strong>
                </span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                </svg>
                <span>Fin:
                    @if($contrato->end_date)
                        <strong class="font-medium {{ $contrato->end_date->isPast() && $contrato->status === 'Vigente' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $contrato->end_date->format('d/m/Y') }}
                        </strong>
                    @else
                        <strong class="font-medium text-gray-900 dark:text-white">Sin fecha de fin</strong>
                    @endif
                </span>
            </div>
        </div>

        {{-- Fila de botones de acción --}}
        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">

            {{-- Volver al listado --}}
            <a href="{{ route('rh.contratos.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Contratos
            </a>

            {{-- Editar --}}
            @can('update', $contrato)
                <a href="{{ route('rh.contratos.edit', $contrato) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                    </svg>
                    Editar
                </a>
            @endcan

            {{-- Prorrogar --}}
            @if($contrato->status === 'Vigente')
                @can('applyProroga', $contrato)
                    <button type="button"
                            x-on:click="Livewire.dispatch('open-proroga-modal', { contractId: '{{ $contrato->id }}' })"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Prorrogar
                    </button>
                @endcan
            @endif

            {{-- Terminar anticipadamente --}}
            @if($contrato->status === 'Vigente')
                @can('earlyTerminate', $contrato)
                    <button type="button"
                            x-on:click="Livewire.dispatch('open-early-termination-modal', { contractId: '{{ $contrato->id }}' })"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 dark:bg-orange-600 dark:hover:bg-orange-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        Terminar anticipadamente
                    </button>
                @endcan
            @endif

            {{-- Generar certificación (placeholder) --}}
            @can('view', $contrato)
                <button type="button"
                        disabled
                        aria-disabled="true"
                        title="Próximamente disponible"
                        class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-400 opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    PDF Certificación
                </button>
            @endcan
        </div>
    </div>

    {{-- ── Card: información general del contrato ──────────────────────────────── --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Información del contrato</h3>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

                {{-- Tipo de contrato --}}
                @if($contrato->contractType)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Tipo de contrato</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $contrato->contractType->name }}
                        </p>
                    </div>
                @endif

                {{-- Número de contrato --}}
                @if($contrato->contract_number)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Número de contrato</p>
                        <p class="mt-1 text-sm font-medium font-mono text-gray-900 dark:text-white">
                            {{ $contrato->contract_number }}
                        </p>
                    </div>
                @endif

                {{-- Código de contrato --}}
                @if($contrato->contract_code)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Código de contrato</p>
                        <p class="mt-1 text-sm font-medium font-mono text-gray-900 dark:text-white">
                            {{ $contrato->contract_code }}
                        </p>
                    </div>
                @endif

                {{-- Correo del cargo --}}
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Correo del cargo</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                        @if($contrato->position_email)
                            <a href="mailto:{{ $contrato->position_email }}"
                               class="text-blue-600 hover:underline dark:text-blue-400">
                                {{ $contrato->position_email }}
                            </a>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">No especificado</span>
                        @endif
                    </p>
                </div>

                {{-- Salario --}}
                @if($contrato->salary && (float) $contrato->salary > 0)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Salario mensual</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            $ {{ number_format((float) $contrato->salary, 0, ',', '.') }}
                        </p>
                    </div>
                @endif

                {{-- Honorarios --}}
                @if($contrato->fees && (float) $contrato->fees > 0)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Honorarios</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            $ {{ number_format((float) $contrato->fees, 0, ',', '.') }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Objeto del contrato --}}
            @if($contrato->object)
                <div class="mt-5 border-t border-gray-100 pt-5 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Objeto del contrato</p>
                    <div class="mt-1 text-sm leading-relaxed text-gray-900 dark:text-white [&_blockquote]:border-l-4 [&_blockquote]:border-gray-300 [&_blockquote]:pl-4 [&_blockquote]:italic [&_h2]:text-base [&_h2]:font-bold [&_h3]:text-sm [&_h3]:font-semibold [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5">
                        {!! $contrato->object !!}
                    </div>
                </div>
            @endif

            {{-- Obligaciones --}}
            <div class="mt-5 border-t border-gray-100 pt-5 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Obligaciones</p>
                @if($contrato->obligations)
                    <div class="mt-1 text-sm leading-relaxed text-gray-900 dark:text-white [&_blockquote]:border-l-4 [&_blockquote]:border-gray-300 [&_blockquote]:pl-4 [&_blockquote]:italic [&_h2]:text-base [&_h2]:font-bold [&_h3]:text-sm [&_h3]:font-semibold [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5">
                        {!! $contrato->obligations !!}
                    </div>
                @else
                    <p class="mt-1 text-sm leading-relaxed text-gray-900 dark:text-white">No especificadas</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Card: valores comprometidos ─────────────────────────────────────────── --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30">
                <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Valores comprometidos</h3>
        </div>

        <div class="p-6">
            @if($contrato->committedValues->isEmpty())
                <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800/50 dark:bg-blue-900/20">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                            Este contrato no tiene valores comprometidos registrados.
                        </p>
                        <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                            Los contratos anteriores a 2019 generalmente no requieren valores comprometidos.
                        </p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" aria-label="Valores comprometidos del contrato">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Cuenta contable
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Centro de costo
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Valor
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($contrato->committedValues as $cv)
                                <tr class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-white">
                                        {{ $cv->accounting_account ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $cv->cost_center ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                        $ {{ number_format((float) $cv->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/50">
                                <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    Total comprometido
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    $ {{ number_format((float) $contrato->committedValues->sum('amount'), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Card: historial de prórrogas ────────────────────────────────────────── --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/30">
                    <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5m-9-6h.008v.008H12V9.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Historial de prórrogas</h3>
            </div>
            @if($contrato->extensions->isNotEmpty())
                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                    {{ $contrato->extensions->count() }} {{ $contrato->extensions->count() === 1 ? 'prórroga' : 'prórrogas' }}
                </span>
            @endif
        </div>

        <div class="p-6">
            @if($contrato->extensions->isEmpty())
                <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No se han registrado prórrogas para este contrato.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" aria-label="Historial de prórrogas del contrato">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Tipo
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Meses
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Días
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Valor
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Nueva fecha fin
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Aprobación
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Observaciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($contrato->extensions as $ext)
                                @php
                                    $tipoLabel = match($ext->extension_type ?? 'tiempo') {
                                        'tiempo'         => 'En tiempo',
                                        'valor'          => 'En valor',
                                        'tiempo_y_valor' => 'En tiempo y valor',
                                        default          => $ext->extension_type ?? '—',
                                    };
                                    $tipoBadgeClasses = match($ext->extension_type ?? 'tiempo') {
                                        'tiempo'         => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'valor'          => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'tiempo_y_valor' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                        default          => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <tr class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tipoBadgeClasses }}">
                                            {{ $tipoLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ $ext->extension_months ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ $ext->extension_days ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                        @if($ext->extension_value)
                                            $ {{ number_format((float) $ext->extension_value, 0, ',', '.') }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $ext->new_end_date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $ext->approval_date?->format('d/m/Y') ?? $ext->extension_date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $ext->reason ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Card: terminación anticipada (condicional) ───────────────────────────── --}}
    @if($contrato->isEarlyTerminated())
        <div class="mb-6 rounded-xl border border-red-200 bg-white shadow-sm dark:border-red-800/50 dark:bg-gray-800">
            <div class="flex items-center gap-3 border-b border-red-200 px-6 py-4 dark:border-red-800/50">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/30">
                    <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Terminación anticipada</h3>
            </div>

            <div class="p-6">
                <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Fecha de terminación anticipada</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $contrato->early_termination_date?->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Registrado por</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $contrato->earlyTerminatedBy?->name ?? 'Sistema' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Motivo</dt>
                        <dd class="mt-1 text-sm leading-relaxed text-gray-900 dark:text-white">
                            {{ $contrato->early_termination_reason ?? 'No especificado' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Fecha de registro</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $contrato->early_terminated_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    @endif

    {{-- ── Modales Livewire ────────────────────────────────────────────────────── --}}
    <livewire:rh.contract-proroga-modal />
    <livewire:rh.contract-early-termination-modal />

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('contract-updated', () => {
                window.location.reload();
            });
        });
    </script>
@endsection
