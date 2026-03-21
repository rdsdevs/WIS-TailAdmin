@extends('layouts.app')

@section('content')
    {{-- Breadcrumb --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            Editar empleado
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

    {{-- Aviso si el empleado tiene contratos activos --}}
    @if($empleado->contracts()->where('is_active', true)->exists())
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20"
             role="alert">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                    Este empleado tiene contratos activos
                </p>
                <p class="mt-0.5 text-sm text-yellow-700 dark:text-yellow-400">
                    Al modificar la información del empleado verifique que los datos correspondan con los contratos vigentes.
                </p>
            </div>
        </div>
    @endif

    {{-- Formulario --}}
    <form method="POST" action="{{ route('rh.empleados.update', $empleado) }}" novalidate>
        @csrf
        @method('PUT')

        <div class="space-y-6">

            {{-- Sección: Información personal --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Información personal
                    </h3>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Datos de identificación y contacto del empleado.
                    </p>
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
                            @foreach(['CC' => 'Cédula de ciudadanía (CC)', 'CE' => 'Cédula de extranjería (CE)', 'PA' => 'Pasaporte (PA)', 'TI' => 'Tarjeta de identidad (TI)'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" {{ old('document_type', $empleado->document_type) === $valor ? 'selected' : '' }}>
                                    {{ $etiqueta }}
                                </option>
                            @endforeach
                        </select>
                        @error('document_type')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Número de cédula --}}
                    <div class="space-y-1.5">
                        <label for="document_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Número de cédula <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="document_number"
                            name="document_number"
                            value="{{ old('document_number', $empleado->document_number) }}"
                            placeholder="Ej: 1234567890"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('document_number') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500"
                        />
                        @error('document_number')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Primer nombre --}}
                    <div class="space-y-1.5">
                        <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Primer nombre <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="{{ old('first_name', $empleado->first_name) }}"
                            placeholder="Ej: María"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('first_name') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500"
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
                            value="{{ old('last_name', $empleado->last_name) }}"
                            placeholder="Ej: González"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('last_name') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500"
                        />
                        @error('last_name')
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
                            value="{{ old('email', $empleado->email) }}"
                            placeholder="correo@ejemplo.com"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('email') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500"
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
                            value="{{ old('phone', $empleado->phone) }}"
                            placeholder="Ej: 3001234567"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('phone') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500"
                        />
                        @error('phone')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Fecha de nacimiento --}}
                    <div class="space-y-1.5">
                        <label for="birth_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha de nacimiento
                        </label>
                        <input
                            type="date"
                            id="birth_date"
                            name="birth_date"
                            value="{{ old('birth_date', $empleado->birth_date?->format('Y-m-d')) }}"
                            max="{{ now()->subYears(18)->format('Y-m-d') }}"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('birth_date') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white"
                        />
                        @error('birth_date')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Dirección --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Dirección
                        </label>
                        <input
                            type="text"
                            id="address"
                            name="address"
                            value="{{ old('address', $empleado->address) }}"
                            placeholder="Ej: Calle 123 # 45-67, Bogotá"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('address') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500"
                        />
                        @error('address')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Sección: Información laboral --}}
            <div
                x-data="{
                    departamento_id: '{{ old('department_id', $empleado->department_id) }}',
                    posicionesPorDepartamento: @js($posicionesPorDepartamento ?? []),
                    get cargos() {
                        return this.posicionesPorDepartamento[this.departamento_id] ?? [];
                    }
                }"
                class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Información laboral
                    </h3>
                </div>
                <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">

                    {{-- Departamento --}}
                    <div class="space-y-1.5">
                        <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Departamento <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select
                            id="department_id"
                            name="department_id"
                            x-model="departamento_id"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('department_id') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white"
                        >
                            <option value="">Seleccione el departamento</option>
                            @foreach($departamentos ?? [] as $departamento)
                                <option value="{{ $departamento->id }}" {{ old('department_id', $empleado->department_id) === $departamento->id ? 'selected' : '' }}>
                                    {{ $departamento->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Cargo --}}
                    <div class="space-y-1.5">
                        <label for="position_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Cargo <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select
                            id="position_id"
                            name="position_id"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('position_id') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                   text-gray-900 dark:text-white"
                        >
                            <option value="">Seleccione el cargo</option>
                            <template x-for="cargo in cargos" :key="cargo.id">
                                <option :value="cargo.id"
                                        :selected="cargo.id === '{{ old('position_id', $empleado->position_id) }}'"
                                        x-text="cargo.name"></option>
                            </template>
                        </select>
                        @error('position_id')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Salario --}}
                    <div class="space-y-1.5">
                        <label for="salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Salario (COP)
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-500 dark:text-gray-400">$</span>
                            <input
                                type="number"
                                id="salary"
                                name="salary"
                                value="{{ old('salary', $empleado->salary) }}"
                                placeholder="0"
                                min="0"
                                step="1000"
                                class="w-full rounded-lg border py-2.5 pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                       {{ $errors->has('salary') ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700' }}
                                       text-gray-900 dark:text-white"
                            />
                        </div>
                        @error('salary')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Estado --}}
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Estado
                        </label>
                        <div x-data="{ activo: {{ $empleado->is_active ? 'true' : 'false' }} }" class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="activo = !activo"
                                :class="activo ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                role="switch"
                                :aria-checked="activo.toString()"
                                aria-label="Estado del empleado">
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

            {{-- Botones de acción --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('rh.empleados.show', $empleado) }}"
                   class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    Guardar cambios
                </button>
            </div>

        </div>
    </form>
@endsection
