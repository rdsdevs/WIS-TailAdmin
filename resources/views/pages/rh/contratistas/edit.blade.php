@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Editar contratista</h2>
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
                    <a href="{{ route('rh.contratistas.index') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Contratistas
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $contratista->full_name }}</li>
            </ol>
        </nav>
    </div>

    @include('layouts.partials.rh-subnav')

    <form method="POST" action="{{ route('rh.contratistas.update', $contratista) }}" novalidate>
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Información del contratista</h3>
            </div>
            <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">

                {{-- Tipo de documento --}}
                <div class="space-y-1.5">
                    <label for="document_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tipo de documento <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select
                        id="document_type"
                        name="document_type"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               {{ $errors->has('document_type') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                               text-gray-900 dark:text-white"
                    >
                        <option value="">Seleccione el tipo</option>
                        @foreach(['CC' => 'Cédula de ciudadanía (CC)', 'CE' => 'Cédula de extranjería (CE)', 'NIT' => 'NIT', 'PA' => 'Pasaporte (PA)'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('document_type', $contratista->document_type) === $valor ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    @error('document_type')
                        <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Número de documento --}}
                <div class="space-y-1.5">
                    <label for="document_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Número de documento <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        type="text"
                        id="document_number"
                        name="document_number"
                        value="{{ old('document_number', $contratista->document_number) }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               {{ $errors->has('document_number') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                               text-gray-900 dark:text-white"
                    />
                    @error('document_number')
                        <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nombre --}}
                <div class="space-y-1.5">
                    <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="{{ old('first_name', $contratista->first_name) }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               {{ $errors->has('first_name') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                               text-gray-900 dark:text-white"
                    />
                    @error('first_name')
                        <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Apellido --}}
                <div class="space-y-1.5">
                    <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Apellido <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="{{ old('last_name', $contratista->last_name) }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               {{ $errors->has('last_name') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                               text-gray-900 dark:text-white"
                    />
                    @error('last_name')
                        <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Empresa --}}
                <div class="space-y-1.5 md:col-span-2">
                    <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre de empresa
                        <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(opcional)</span>
                    </label>
                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        value="{{ old('company_name', $contratista->company_name) }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               {{ $errors->has('company_name') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                               text-gray-900 dark:text-white"
                    />
                    @error('company_name')
                        <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Correo electrónico --}}
                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Correo electrónico
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $contratista->email) }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               {{ $errors->has('email') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                               text-gray-900 dark:text-white"
                    />
                    @error('email')
                        <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Teléfono --}}
                <div class="space-y-1.5">
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Teléfono
                    </label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ old('phone', $contratista->phone) }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               {{ $errors->has('phone') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                               text-gray-900 dark:text-white"
                    />
                    @error('phone')
                        <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Estado --}}
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                    <div x-data="{ activo: {{ $contratista->is_active ? 'true' : 'false' }} }" class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="activo = !activo"
                            :class="activo ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            role="switch"
                            :aria-checked="activo.toString()"
                            aria-label="Estado del contratista">
                            <span
                                :class="activo ? 'translate-x-5' : 'translate-x-0'"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                            </span>
                        </button>
                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="activo ? 'Activo' : 'Inactivo'"></span>
                        <input type="hidden" name="is_active" :value="activo ? '1' : '0'" />
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('rh.contratistas.show', $contratista) }}"
               class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Cancelar
            </a>
            <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Guardar cambios
            </button>
        </div>
    </form>
@endsection
