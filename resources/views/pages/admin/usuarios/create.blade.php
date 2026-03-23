@extends('layouts.app')

@section('content')
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Nuevo usuario</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Complete los datos para registrar un nuevo usuario del sistema.
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
                    <a href="{{ route('admin.usuarios.index') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Usuarios
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">Nuevo</li>
            </ol>
        </nav>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <form method="POST" action="{{ route('admin.usuarios.store') }}" novalidate>
            @csrf

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- Institución (solo super-admin) --}}
                @if ($isSuperAdmin)
                    <div class="md:col-span-2">
                        <label for="institution_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Institución <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select
                            id="institution_id"
                            name="institution_id"
                            class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 dark:bg-gray-800 dark:text-white
                                {{ $errors->has('institution_id') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600' }}">
                            <option value="">Seleccione una institución</option>
                            @foreach ($instituciones as $inst)
                                <option value="{{ $inst->id }}" @selected(old('institution_id') === $inst->id)>
                                    {{ $inst->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('institution_id')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Nombre completo --}}
                <div class="md:col-span-2">
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre completo <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 dark:bg-gray-800 dark:text-white
                            {{ $errors->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600' }}"
                    />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tipo de documento --}}
                <div>
                    <label for="document_type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tipo de documento <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select
                        id="document_type"
                        name="document_type"
                        class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 dark:bg-gray-800 dark:text-white
                            {{ $errors->has('document_type') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600' }}">
                        <option value="">Seleccione</option>
                        @foreach (['CC' => 'Cédula de Ciudadanía', 'CE' => 'Cédula de Extranjería', 'NIT' => 'NIT', 'PP' => 'Pasaporte', 'TI' => 'Tarjeta de Identidad'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('document_type') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('document_type')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Número de documento --}}
                <div>
                    <label for="document_number" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Número de documento <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="document_number"
                        type="text"
                        name="document_number"
                        value="{{ old('document_number') }}"
                        inputmode="numeric"
                        class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 dark:bg-gray-800 dark:text-white
                            {{ $errors->has('document_number') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600' }}"
                    />
                    @error('document_number')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Fecha de expedición del documento --}}
                <div>
                    <label for="document_issued_at" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Fecha de expedición <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="document_issued_at"
                        type="date"
                        name="document_issued_at"
                        value="{{ old('document_issued_at') }}"
                        class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 dark:bg-gray-800 dark:text-white
                            {{ $errors->has('document_issued_at') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600' }}"
                    />
                    @error('document_issued_at')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Correo electrónico --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Correo electrónico
                        <span class="text-xs font-normal text-gray-400">(opcional)</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 dark:bg-gray-800 dark:text-white
                            {{ $errors->has('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600' }}"
                    />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contraseña --}}
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Contraseña <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 dark:bg-gray-800 dark:text-white
                            {{ $errors->has('password') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600' }}"
                    />
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirmar contraseña --}}
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Confirmar contraseña <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                {{-- Roles --}}
                <div class="md:col-span-2">
                    <fieldset>
                        <legend class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Roles <span class="text-red-500" aria-hidden="true">*</span>
                        </legend>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($roles as $rol)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 transition-colors hover:border-blue-300 hover:bg-blue-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-600 dark:has-[:checked]:border-blue-500 dark:has-[:checked]:bg-blue-900/20 dark:has-[:checked]:text-blue-300">
                                    <input
                                        type="checkbox"
                                        name="roles[]"
                                        value="{{ $rol->name }}"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                                        @checked(in_array($rol->name, old('roles', [])))
                                    />
                                    {{ $rol->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('roles')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>

            </div>

            {{-- Botones --}}
            <div class="mt-6 flex items-center gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Registrar usuario
                </button>
                <a href="{{ route('admin.usuarios.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
