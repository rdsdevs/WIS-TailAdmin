@extends('layouts.app')

@section('content')
    @php
        $puedeGestionar = auth()->user()->can('create', [\App\Models\RH\CommittedValue::class, $contrato]);
        $totalComprometido = (float) $valores->getCollection()->sum('amount');
    @endphp

    {{-- Cabecera con migas y acción primaria --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                Valores comprometidos
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Contrato {{ $contrato->contract_code ?? $contrato->contract_number ?? '—' }}
                — {{ $contrato->collaborator?->full_name ?? 'Sin colaborador' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($puedeGestionar)
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-committed-value-create', { contractId: '{{ $contrato->id }}' })"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Agregar valor
                </button>
            @endif

            <nav aria-label="Migas de pan">
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Inicio
                        </a>
                    </li>
                    <li class="text-gray-400 dark:text-gray-500">/</li>
                    <li>
                        <a href="{{ route('rh.contratos.index') }}"
                           class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Contratos
                        </a>
                    </li>
                    <li class="text-gray-400 dark:text-gray-500">/</li>
                    <li>
                        <a href="{{ route('rh.contratos.show', $contrato) }}"
                           class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            {{ $contrato->contract_code ?? 'Contrato' }}
                        </a>
                    </li>
                    <li class="text-gray-400 dark:text-gray-500">/</li>
                    <li class="text-sm font-medium text-gray-700 dark:text-gray-200">Valores comprometidos</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Mensajes flash --}}
    @if(session('exito'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800/50 dark:bg-green-900/20 dark:text-green-300" role="status">
            {{ session('exito') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800/50 dark:bg-red-900/20 dark:text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Resumen --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Líneas registradas</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $valores->total() }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total página actual</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">$ {{ number_format($totalComprometido, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estado del contrato</p>
            <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                {{ $contrato->status }}
                @if($contrato->start_date?->year < now()->year)
                    <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                        Histórico — {{ $contrato->start_date->year }}
                    </span>
                @endif
            </p>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if($valores->isEmpty())
            <div class="p-10 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                </svg>
                <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">Aún no hay valores comprometidos.</p>
                @if($puedeGestionar)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use el botón "Agregar valor" para registrar la primera línea presupuestal.</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm" aria-label="Valores comprometidos">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cuenta contable</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Centro de costo</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Valor</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Registrado</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($valores as $cv)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-white">{{ $cv->accounting_account }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $cv->cost_center }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">$ {{ number_format((float) $cv->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $cv->created_at?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @can('update', $cv)
                                            <button
                                                type="button"
                                                x-data
                                                @click="$dispatch('open-committed-value-edit', { committedValueId: '{{ $cv->id }}' })"
                                                class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                                aria-label="Editar valor comprometido">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                </svg>
                                                Editar
                                            </button>
                                        @endcan
                                        @can('delete', $cv)
                                            <button
                                                type="button"
                                                x-data
                                                @click="$dispatch('open-committed-value-delete', { committedValueId: '{{ $cv->id }}', label: '{{ $cv->cost_center }} — $ {{ number_format((float) $cv->amount, 0, ',', '.') }}' })"
                                                class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 dark:border-red-800/50 dark:bg-gray-700 dark:text-red-300 dark:hover:bg-red-900/20"
                                                aria-label="Eliminar valor comprometido">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                {{ $valores->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- Modales Livewire --}}
    @if($puedeGestionar)
        <livewire:rh.committed-value-form-modal :contract-id="$contrato->id" />
        <livewire:rh.committed-value-delete-modal :contract-id="$contrato->id" />
    @endif
@endsection
