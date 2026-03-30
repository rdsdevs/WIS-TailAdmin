@extends('layouts.app')

@section('content')
    {{-- Breadcrumb --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Perfil del colaborador</h2>
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
                    <a href="{{ route('rh.colaboradores.index') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Colaboradores
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ Str::limit($collaborator->full_name, 25) }}
                </li>
            </ol>
        </nav>
    </div>

    @include('layouts.partials.rh-subnav')

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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ── Columna izquierda: datos básicos ── --}}
        <div class="space-y-5 lg:col-span-1">

            {{-- Card: Header del colaborador --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col items-center text-center">
                    {{-- Avatar --}}
                    <div class="flex h-20 w-20 items-center justify-center rounded-full
                        {{ $collaborator->type === 'Empleado' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}
                        text-2xl font-bold" aria-hidden="true">
                        @if($collaborator->is_company)
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        @else
                            {{ strtoupper(substr($collaborator->first_name ?? '', 0, 1) . substr($collaborator->first_surname ?? '', 0, 1)) }}
                        @endif
                    </div>

                    <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $collaborator->full_name }}
                    </h3>

                    {{-- Tipo --}}
                    <div class="mt-2 flex flex-wrap items-center justify-center gap-2">
                        @if($collaborator->type === 'Empleado')
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                Empleado
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                Contratista
                            </span>
                        @endif

                        {{-- Estado --}}
                        @if($collaborator->status)
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <i class="{{ $collaborator->status->icon_class }}" aria-hidden="true"></i>
                                {{ $collaborator->status->name }}
                            </span>
                        @endif
                    </div>

                    {{-- Cargo actual --}}
                    @if($activeContract?->position)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ $activeContract->position->name }}
                            @if($activeContract->position->department)
                                <span class="text-gray-400 dark:text-gray-500">&bull; {{ $activeContract->position->department->name }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                {{-- Acciones rápidas: Button Group Icon --}}
                <div class="mt-5 flex flex-col items-center gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">

                    {{-- Grupo principal --}}
                    <div class="flex divide-x divide-gray-200 overflow-hidden rounded-xl border border-gray-200 dark:divide-gray-700 dark:border-gray-700">

                        {{-- Editar (Warning) --}}
                        @can('update', $collaborator)
                            <div class="relative group">
                                <a href="{{ route('rh.colaboradores.edit', $collaborator) }}"
                                   class="flex items-center justify-center p-2.5 text-amber-500 transition-colors hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                   aria-label="Editar colaborador">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                </a>
                                <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                                    Editar colaborador
                                    <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                </div>
                            </div>
                        @endcan

                        {{-- Nuevo contrato (Primary) --}}
                        @can('create', \App\Models\RH\Contract::class)
                            <div class="relative group">
                                <a href="{{ route('rh.contratos.create', ['collaborator_id' => $collaborator->id]) }}"
                                   class="flex items-center justify-center p-2.5 text-blue-600 transition-colors hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                   aria-label="Nuevo contrato">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </a>
                                <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                                    Nuevo contrato
                                    <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                </div>
                            </div>
                        @endcan

                        {{-- Eliminar (Danger) --}}
                        @can('delete', $collaborator)
                            <div class="relative group" x-data="{ confirmar: false }" @mouseleave="confirmar = false">
                                <form method="POST" action="{{ route('rh.colaboradores.destroy', $collaborator) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            @click.prevent="confirmar ? $el.closest('form').submit() : confirmar = true"
                                            class="flex items-center justify-center p-2.5 transition-colors"
                                            :class="confirmar
                                                ? 'text-red-700 bg-red-100 dark:bg-red-900/30 dark:text-red-300'
                                                : 'text-red-500 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20'"
                                            :aria-label="confirmar ? '¿Confirmar eliminación?' : 'Eliminar colaborador'">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                                <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                                    <span x-text="confirmar ? '¿Confirmar eliminación?' : 'Eliminar colaborador'">Eliminar colaborador</span>
                                    <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                </div>
                            </div>
                        @endcan

                    </div>

                    {{-- Cambiar tipo (fuera del grupo, maneja overflow de confirmación) --}}
                    @can('changeType', $collaborator)
                        @php
                            $tieneContratoActivo = $collaborator->contracts()->where('status', 'Vigente')->exists();
                            $labelCambio = $collaborator->type === 'employee' ? 'Cambiar a contratista' : 'Cambiar a empleado';
                        @endphp
                        @if($tieneContratoActivo)
                            <div class="relative group">
                                <button type="button" disabled aria-disabled="true"
                                        class="flex cursor-not-allowed items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-amber-400 opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-amber-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 3M21 7.5H7.5" />
                                    </svg>
                                    {{ $labelCambio }}
                                </button>
                                <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 w-52 rounded-lg bg-gray-900 px-2.5 py-1.5 text-center text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                                    No disponible con contrato activo
                                    <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                </div>
                            </div>
                        @else
                            <div class="relative" x-data="{ pendiente: false }">
                                <form id="{{ 'form-change-type-' . $collaborator->id }}" method="POST"
                                      action="{{ route('rh.colaboradores.change-type', $collaborator) }}" class="hidden">
                                    @csrf
                                </form>
                                <button type="button"
                                        @click="pendiente = !pendiente"
                                        class="flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-colors"
                                        :class="pendiente
                                            ? 'border-amber-400 bg-amber-50 text-amber-700 dark:border-amber-600 dark:bg-amber-900/20 dark:text-amber-300'
                                            : 'border-gray-200 bg-white text-amber-500 hover:bg-amber-50 dark:border-gray-700 dark:bg-gray-800 dark:text-amber-400 dark:hover:bg-amber-900/10'">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 3M21 7.5H7.5" />
                                    </svg>
                                    <span x-text="pendiente ? '¿Confirmar cambio?' : '{{ $labelCambio }}'">{{ $labelCambio }}</span>
                                </button>
                                <div x-show="pendiente" x-transition
                                     class="absolute left-1/2 top-full z-20 mt-1.5 flex -translate-x-1/2 gap-1.5 whitespace-nowrap">
                                    <button type="button" @click="pendiente = false"
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        Cancelar
                                    </button>
                                    <button type="submit" form="{{ 'form-change-type-' . $collaborator->id }}"
                                            class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700">
                                        Confirmar
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endcan

                </div>
            </div>

            {{-- Card: Datos personales --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Información del documento</h4>
                <dl class="mt-3 space-y-2.5">
                    @if(!$collaborator->is_company)
                        <div class="flex justify-between gap-2">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Tipo de doc.</dt>
                            <dd class="text-xs font-medium text-gray-900 dark:text-white">
                                {{ $collaborator->documentType?->code }} — {{ $collaborator->documentType?->name }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Número</dt>
                            <dd class="text-xs font-medium text-gray-900 dark:text-white font-mono">{{ $collaborator->document_number }}</dd>
                        </div>
                        @if($collaborator->document_issued_at)
                            <div class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Fecha expedición</dt>
                                <dd class="text-xs font-medium text-gray-900 dark:text-white">{{ $collaborator->document_issued_at->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                        @if($collaborator->birth_date)
                            <div class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Fecha nacimiento</dt>
                                <dd class="text-xs font-medium text-gray-900 dark:text-white">{{ $collaborator->birth_date->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                        @if($collaborator->gender)
                            <div class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Género</dt>
                                <dd class="text-xs font-medium text-gray-900 dark:text-white">
                                    {{ match($collaborator->gender) { 'M' => 'Masculino', 'F' => 'Femenino', default => $collaborator->gender } }}
                                </dd>
                            </div>
                        @endif
                    @else
                        {{-- Empresa --}}
                        @if($collaborator->legal_representative)
                            <div class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Representante</dt>
                                <dd class="text-xs font-medium text-gray-900 dark:text-white">{{ $collaborator->legal_representative }}</dd>
                            </div>
                        @endif
                        @if($collaborator->document_number)
                            <div class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">NIT</dt>
                                <dd class="text-xs font-medium text-gray-900 dark:text-white font-mono">{{ $collaborator->document_number }}</dd>
                            </div>
                        @endif
                    @endif
                </dl>
            </div>

            {{-- Card: Contacto --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Contacto</h4>
                <dl class="mt-3 space-y-2.5">
                    @if($collaborator->email)
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Correo electrónico</dt>
                            <dd class="mt-0.5">
                                <a href="mailto:{{ $collaborator->email }}"
                                   class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $collaborator->email }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if($collaborator->phone)
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Teléfono</dt>
                            <dd class="mt-0.5">
                                <a href="tel:{{ $collaborator->phone }}"
                                   class="text-xs font-medium text-gray-900 dark:text-white">
                                    {{ $collaborator->phone }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if($collaborator->address)
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Dirección</dt>
                            <dd class="mt-0.5 text-xs font-medium text-gray-900 dark:text-white">{{ $collaborator->address }}</dd>
                        </div>
                    @endif
                    @if(!$collaborator->email && !$collaborator->phone && !$collaborator->address)
                        <p class="text-xs text-gray-400 dark:text-gray-500 italic">Sin datos de contacto registrados.</p>
                    @endif
                </dl>
            </div>
        </div>

        {{-- ── Columna derecha: contratos ── --}}
        <div class="space-y-5 lg:col-span-2">

            {{-- Contrato vigente --}}
            @if($activeContract)
                <div class="rounded-xl border border-green-200 bg-white p-5 dark:border-green-800 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Contrato vigente</h4>
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            Vigente
                        </span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                        @if($activeContract->position)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Cargo</p>
                                <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">{{ $activeContract->position->name }}</p>
                            </div>
                        @endif
                        @if($activeContract->position?->department)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Departamento</p>
                                <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">{{ $activeContract->position->department->name }}</p>
                            </div>
                        @endif
                        @if($activeContract->contractType)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Tipo</p>
                                <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">{{ $activeContract->contractType->name }}</p>
                            </div>
                        @endif
                        @if((float) $activeContract->salary > 0)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Salario</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">
                                    $ {{ number_format((float) $activeContract->salary, 0, ',', '.') }}
                                </p>
                            </div>
                        @elseif((float) $activeContract->fees > 0)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Honorarios</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">
                                    $ {{ number_format((float) $activeContract->fees, 0, ',', '.') }}
                                </p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Inicio</p>
                            <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $activeContract->start_date?->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Fin</p>
                            <p class="mt-0.5 text-sm font-medium {{ $activeContract->end_date && $activeContract->end_date->isFuture() && now()->diffInDays($activeContract->end_date) <= 30 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $activeContract->end_date ? $activeContract->end_date->format('d/m/Y') : 'Indefinido' }}
                            </p>
                        </div>
                    </div>

                    {{-- Alerta si vence pronto --}}
                    @if($activeContract->end_date && $activeContract->end_date->isFuture() && now()->diffInDays($activeContract->end_date) <= 30)
                        <div class="mt-3 flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            Este contrato vence en {{ (int) now()->diffInDays($activeContract->end_date) }} días.
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                        @can('update', $activeContract)
                            <a href="{{ route('rh.contratos.edit', $activeContract) }}"
                               class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                Editar contrato
                            </a>
                            <button type="button" 
                                    onclick="Livewire.dispatch('open-position-change-modal', { contractId: '{{ $activeContract->id }}' })"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 3M21 7.5H7.5" />
                                </svg>
                                Cambio de cargo
                            </button>
                        @endcan
                        @canany(['generateEmployee', 'generateContractor'], \App\Models\Certificados\Certificate::class)
                            <button onclick="Livewire.dispatch('open-for-collaborator', { collaboratorId: '{{ $collaborator->id }}' })"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Generar certificado
                            </button>
                        @endcanany
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center dark:border-gray-600 dark:bg-gray-800">
                    <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Este colaborador no tiene contratos vigentes.</p>
                    @can('create', \App\Models\RH\Contract::class)
                        <a href="{{ route('rh.contratos.create', ['collaborator_id' => $collaborator->id]) }}"
                           class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                            Registrar primer contrato
                        </a>
                    @endcan
                </div>
            @endif

            {{-- Historial de contratos --}}
            <livewire:rh.timeline-contratos
                :collaborator-id="$collaborator->id"
                :collaborator-type="$collaborator->type" />

        </div>
    </div>

    {{-- Modal generar certificado --}}
    @canany(['generateEmployee', 'generateContractor'], \App\Models\Certificados\Certificate::class)
        <livewire:certificados.generate-certificate-modal />
    @endcanany

    {{-- Modal cambio de cargo --}}
    @can('create', \App\Models\RH\Contract::class)
        <livewire:rh.position-change-modal />
    @endcan
@endsection
