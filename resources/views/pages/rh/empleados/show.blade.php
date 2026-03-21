@extends('layouts.app')

@section('content')
    {{-- Breadcrumb --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            {{ $empleado->full_name }}
        </h2>
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
                    <a href="{{ route('rh.empleados.index') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Empleados
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $empleado->full_name }}
                </li>
            </ol>
        </nav>
    </div>

    {{-- Subnav --}}
    @include('layouts.partials.rh-subnav')

    <div class="space-y-6">

        {{-- Card principal del empleado --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="p-6">
                <div class="flex flex-col items-start gap-6 sm:flex-row sm:items-center">

                    {{-- Avatar con iniciales --}}
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30"
                         aria-hidden="true">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ mb_strtoupper(mb_substr($empleado->first_name, 0, 1)) }}{{ mb_strtoupper(mb_substr($empleado->last_name, 0, 1)) }}
                        </span>
                    </div>

                    {{-- Info principal --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ $empleado->full_name }}
                            </h3>
                            @if($empleado->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    Inactivo
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $empleado->position?->name ?? 'Sin cargo asignado' }}
                            @if($empleado->department)
                                &middot; {{ $empleado->department->name }}
                            @endif
                        </p>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-500">
                            CC {{ $empleado->document_number }}
                        </p>
                    </div>

                    {{-- Botones de acción --}}
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Generar certificado --}}
                        <a href="#"
                           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                           title="Generar certificado laboral (próximamente)">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            Generar certificado
                        </a>

                        @can('update', $empleado)
                            <a href="{{ route('rh.empleados.edit', $empleado) }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                </svg>
                                Editar empleado
                            </a>
                        @endcan

                        @can('delete', $empleado)
                            <div x-data="{ abierto: false }">
                                <button
                                    @click="abierto = true"
                                    class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    Eliminar
                                </button>

                                <div
                                    x-show="abierto"
                                    x-cloak
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                                    @keydown.escape.window="abierto = false"
                                    role="dialog"
                                    aria-modal="true"
                                    aria-labelledby="modal-eliminar-titulo">
                                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                                        <h3 id="modal-eliminar-titulo"
                                            class="text-lg font-semibold text-gray-900 dark:text-white">
                                            Confirmar eliminación
                                        </h3>
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            ¿Está seguro de que desea eliminar a
                                            <strong class="text-gray-900 dark:text-white">{{ $empleado->full_name }}</strong>?
                                            Esta acción no se puede deshacer.
                                        </p>
                                        <div class="mt-5 flex justify-end gap-3">
                                            <button
                                                @click="abierto = false"
                                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                Cancelar
                                            </button>
                                            <form method="POST" action="{{ route('rh.empleados.destroy', $empleado) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                                    Sí, eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>

                {{-- Datos adicionales en grid --}}
                <div class="mt-6 grid grid-cols-2 gap-4 border-t border-gray-200 pt-6 dark:border-gray-700 sm:grid-cols-3 lg:grid-cols-4">
                    @if($empleado->email)
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Correo</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $empleado->email }}</p>
                        </div>
                    @endif
                    @if($empleado->phone)
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Teléfono</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $empleado->phone }}</p>
                        </div>
                    @endif
                    @if($empleado->birth_date)
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Fecha de nacimiento</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $empleado->birth_date->format('d/m/Y') }}</p>
                        </div>
                    @endif
                    @if($empleado->salary)
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Salario</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                $ {{ number_format($empleado->salary, 0, ',', '.') }}
                            </p>
                        </div>
                    @endif
                    @if($empleado->address)
                        <div class="col-span-2 sm:col-span-3 lg:col-span-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Dirección</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $empleado->address }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sección: Contratos --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    Contratos
                </h3>
                @can('create', \App\Models\RH\Contract::class)
                    <a href="{{ route('rh.contratos.create') }}?empleado={{ $empleado->id }}"
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Nuevo contrato
                    </a>
                @endcan
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tipo</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cargo</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Inicio</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Fin</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Salario</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse($empleado->contracts()->orderByDesc('start_date')->get() as $contrato)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    @php
                                        $tiposContrato = [
                                            'indefinite' => 'Término indefinido',
                                            'fixed_term' => 'Término fijo',
                                            'contractor' => 'Contratista',
                                            'intern' => 'Practicante',
                                        ];
                                    @endphp
                                    {{ $tiposContrato[$contrato->contract_type] ?? $contrato->contract_type }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $contrato->position ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $contrato->start_date?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $contrato->end_date?->format('d/m/Y') ?? 'Indefinido' }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                    @if($contrato->salary)
                                        $ {{ number_format($contrato->salary, 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($contrato->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            Vigente
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            Finalizado
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Este empleado no tiene contratos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
