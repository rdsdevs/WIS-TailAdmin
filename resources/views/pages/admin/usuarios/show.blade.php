@extends('layouts.app')

@section('content')
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $usuario->name }}</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Detalle del usuario del sistema</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
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
                        <a href="{{ route('admin.usuarios.index') }}"
                           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Usuarios
                            <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $usuario->name }}</li>
                </ol>
            </nav>
            @can('update', $usuario)
                <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                    </svg>
                    Editar
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash de sesión --}}
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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Datos del usuario --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Perfil --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="mb-5 flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 text-xl font-bold" aria-hidden="true">
                        {{ strtoupper(substr($usuario->name, 0, 2)) }}
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $usuario->name }}</h3>
                        @if($usuario->email)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $usuario->email }}</p>
                        @endif
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach($usuario->roles as $rol)
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $rol->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <h4 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Información personal</h4>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Tipo de documento</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $usuario->document_type }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Número de documento</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $usuario->document_number }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Fecha de expedición</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">
                            {{ $usuario->document_issued_at?->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Institución</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $usuario->institution?->name ?? '—' }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Estado</dt>
                        <dd class="mt-1">
                            @if ($usuario->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Inactivo
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Último acceso</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">
                            {{ $usuario->last_login_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Registrado el</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">
                            {{ $usuario->created_at->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Última actualización</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">
                            {{ $usuario->updated_at->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </div>

        </div>

        {{-- Historial de auditoría --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Historial de cambios</h3>
                <span class="ml-auto inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    {{ $auditorias->count() }}
                </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700/50 overflow-y-auto" style="max-height: 520px;">
                @forelse ($auditorias as $auditoria)
                    <div class="px-5 py-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                @php
                                    $eventoClases = match($auditoria->event) {
                                        'created' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                        'updated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                        'deleted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        default   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $eventoClases }}">
                                    {{ match($auditoria->event) {
                                        'created' => 'Creado',
                                        'updated' => 'Actualizado',
                                        'deleted' => 'Eliminado',
                                        default   => $auditoria->event,
                                    } }}
                                </span>
                            </div>
                            <time class="shrink-0 text-xs text-gray-400 dark:text-gray-500" datetime="{{ $auditoria->created_at->toIso8601String() }}">
                                {{ $auditoria->created_at->format('d/m/Y H:i') }}
                            </time>
                        </div>

                        @if($auditoria->user)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Por: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $auditoria->user->name }}</span>
                            </p>
                        @endif

                        {{-- Campos modificados --}}
                        @if($auditoria->event === 'updated' && count($auditoria->modified ?? []))
                            <div class="mt-2 space-y-1">
                                @foreach(array_slice($auditoria->modified ?? [], 0, 3) as $campo => $valores)
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span class="font-medium text-gray-600 dark:text-gray-400">{{ $campo }}:</span>
                                        @if(isset($valores['old']))
                                            <span class="rounded bg-red-50 px-1 py-0.5 text-red-600 line-through dark:bg-red-900/20 dark:text-red-400">
                                                {{ is_array($valores['old']) ? json_encode($valores['old']) : (string) $valores['old'] }}
                                            </span>
                                            <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                            </svg>
                                        @endif
                                        @if(isset($valores['new']))
                                            <span class="rounded bg-green-50 px-1 py-0.5 text-green-600 dark:bg-green-900/20 dark:text-green-400">
                                                {{ is_array($valores['new']) ? json_encode($valores['new']) : (string) $valores['new'] }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                                @if(count($auditoria->modified ?? []) > 3)
                                    <p class="text-xs text-gray-400">+{{ count($auditoria->modified) - 3 }} campo(s) más</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 px-5 py-10 text-center">
                        <svg class="h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin registros de auditoría.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
@endsection
