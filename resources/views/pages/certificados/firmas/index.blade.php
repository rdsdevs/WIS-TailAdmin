@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Firmas Digitales</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Gestione las firmas de los funcionarios autorizados para emitir certificados.
            </p>
        </div>
        <nav aria-label="Migas de pan">
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Inicio
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <a href="{{ route('certificados.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Certificados</a>
                    <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none">
                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">Firmas digitales</li>
            </ol>
        </nav>
    </div>

    {{-- Flash --}}
    @if(session('exito'))
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 4000)"
             class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300" role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            {{ session('exito') }}
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 5000)"
             class="mb-4 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300" role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Subnav --}}
    <nav class="mb-6 overflow-x-auto">
        <div class="flex min-w-max gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800/50">
            <a href="{{ route('certificados.index') }}"
               class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors text-gray-600 hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                </svg>
                <span>Historial</span>
            </a>
            <a href="{{ route('certificados.firmas.index') }}"
               class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                <span>Firmas digitales</span>
            </a>
        </div>
    </nav>

    {{-- Cabecera tabla + Nuevo --}}
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $firmas->count() }} firma(s) registrada(s)</h3>
        @can('create', \App\Models\RH\CertificateSignature::class)
            <a href="{{ route('certificados.firmas.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva firma
            </a>
        @endcan
    </div>

    @if ($firmas->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center dark:border-gray-600 dark:bg-gray-800">
            <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
            <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">No hay firmas registradas</p>
            @can('create', \App\Models\RH\CertificateSignature::class)
                <a href="{{ route('certificados.firmas.create') }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                    Registrar primera firma
                </a>
            @endcan
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($firmas as $firma)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-3 flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $firma->signer_name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $firma->signer_position }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $firma->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                            {{ $firma->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>

                    @if ($firma->signature_image)
                        <div class="mb-3 rounded-lg bg-gray-50 p-2 dark:bg-gray-700/50">
                            <img src="{{ $firma->signature_image }}" alt="Firma de {{ $firma->signer_name }}" class="h-16 w-full object-contain">
                        </div>
                    @endif

                    @if ($firma->replacement_name)
                        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium">Reemplazo:</span> {{ $firma->replacement_name }}, {{ $firma->replacement_position }}
                        </p>
                    @endif

                    <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                        @can('update', $firma)
                            <a href="{{ route('certificados.firmas.edit', $firma) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" />
                                </svg>
                                Editar
                            </a>
                        @endcan
                        @can('delete', $firma)
                            <form method="POST" action="{{ route('certificados.firmas.destroy', $firma) }}"
                                  x-data="{ confirmar: false }" @mouseleave="confirmar = false">
                                @csrf @method('DELETE')
                                <button type="button"
                                        @click.prevent="confirmar ? $el.closest('form').submit() : confirmar = true"
                                        :class="confirmar
                                            ? 'border-red-300 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400'
                                            : 'border-gray-200 bg-white text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                                        :aria-label="confirmar ? '¿Confirmar eliminación?' : 'Eliminar firma'"
                                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors hover:border-red-300 hover:bg-red-50 hover:text-red-700">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    <span x-text="confirmar ? '¿Confirmar?' : 'Eliminar'"></span>
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
