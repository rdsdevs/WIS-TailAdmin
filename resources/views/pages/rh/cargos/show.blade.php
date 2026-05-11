@extends('layouts.app')

@php
    $contratosVigentes = $cargo->contracts()->where('status', 'Vigente')->count();
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Detalle del cargo</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Información completa del cargo, sus correos y funciones asociadas.
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
                    <a href="{{ route('rh.cargos.index') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Cargos
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $cargo->name }}</li>
            </ol>
        </nav>
    </div>

    @include('layouts.partials.rh-subnav')

    @if(session('exito'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300" role="status">
            {{ session('exito') }}
        </div>
    @endif

    <div class="mx-auto mt-6 max-w-3xl space-y-6">

        {{-- Encabezado del cargo --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $cargo->name }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Creado el {{ $cargo->created_at->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                        @if($cargo->updated_at && $cargo->updated_at->ne($cargo->created_at))
                            · Actualizado el {{ $cargo->updated_at->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($cargo->is_active)
                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            Activo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                            Inactivo
                        </span>
                    @endif
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-700/40">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Correos</p>
                    <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $cargo->emails->count() }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-700/40">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Funciones</p>
                    <p class="mt-1 text-xl font-bold text-violet-600 dark:text-violet-400">{{ $cargo->functions->count() }}</p>
                </div>
                <div class="col-span-2 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 sm:col-span-1 dark:border-gray-700 dark:bg-gray-700/40">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Contratos vigentes</p>
                    @if($contratosVigentes > 0)
                        <a href="{{ route('rh.contratos.index', ['cargo' => $cargo->id]) }}"
                           class="mt-1 inline-flex items-center gap-1 text-xl font-bold text-blue-600 hover:underline dark:text-blue-400">
                            {{ $contratosVigentes }}
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    @else
                        <p class="mt-1 text-xl font-bold text-gray-400 dark:text-gray-500">0</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- Correos --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Correos asociados</h3>
            @if($cargo->emails->isEmpty())
                <p class="text-sm italic text-gray-500 dark:text-gray-400">Este cargo no tiene correos asociados.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($cargo->emails as $emailRow)
                        <li class="flex items-center gap-2 py-2.5">
                            <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <a href="mailto:{{ $emailRow->email }}"
                               class="text-sm text-gray-800 hover:text-blue-600 dark:text-gray-200 dark:hover:text-blue-400">
                                {{ $emailRow->email }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Funciones --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Funciones del cargo</h3>
            @if($cargo->functions->isEmpty())
                <p class="text-sm italic text-gray-500 dark:text-gray-400">Este cargo no tiene funciones registradas.</p>
            @else
                <ol class="list-decimal space-y-2 pl-5 text-sm text-gray-700 dark:text-gray-300">
                    @foreach($cargo->functions as $functionRow)
                        <li class="leading-relaxed">{{ $functionRow->description }}</li>
                    @endforeach
                </ol>
            @endif
        </section>

        {{-- Acciones --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <a href="{{ route('rh.cargos.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>

            @can('update', $cargo)
                <a href="{{ route('rh.cargos.edit', $cargo->id) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Editar cargo
                </a>
            @endcan
        </div>
    </div>
@endsection
