@extends('layouts.app')

@section('content')
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Registrar cargo</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Complete los datos para registrar un nuevo cargo en la estructura organizacional.
            </p>
        </div>
        <nav aria-label="Migas de pan">
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Inicio
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('rh.cargos.index') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Cargos
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">Nuevo</li>
            </ol>
        </nav>
    </div>

    @include('layouts.partials.rh-subnav')

    <div class="mt-6">
        <form method="POST" action="{{ route('rh.cargos.store') }}"
              x-data="{
                  emails: @json(old('emails', [])),
                  functions: @json(old('functions', [])),
                  addEmail()    { this.emails.push(''); },
                  removeEmail(i){ this.emails.splice(i, 1); },
                  addFunction() { this.functions.push(''); },
                  removeFunction(i){ this.functions.splice(i, 1); }
              }">
            @csrf
            <input type="hidden" name="institution_id" value="{{ auth()->user()->institution_id }}">
            <input type="hidden" name="is_active" value="1">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Columna principal --}}
                <div class="lg:col-span-2 space-y-5">
                    {{-- Card: Datos básicos --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">Datos del cargo</h3>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nombre del cargo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name"
                                       value="{{ old('name') }}"
                                       required maxlength="150"
                                       placeholder="Ej: Coordinador de Programas"
                                       class="wis-input w-full {{ $errors->has('name') ? 'border-red-400' : '' }}">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Código
                                </label>
                                <input type="text" id="code" name="code"
                                       value="{{ old('code') }}"
                                       maxlength="50"
                                       placeholder="Ej: COO-001"
                                       class="wis-input w-full {{ $errors->has('code') ? 'border-red-400' : '' }}">
                                @error('code')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Descripción
                                </label>
                                <textarea id="description" name="description" rows="3"
                                          placeholder="Descripción general del cargo..."
                                          class="wis-input w-full {{ $errors->has('description') ? 'border-red-400' : '' }}">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Card: Funciones del cargo --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white">Funciones del cargo</h3>
                            <button type="button" @click="addFunction()"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Agregar función
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-if="functions.length === 0">
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No se han agregado funciones. Haga clic en "Agregar función".</p>
                            </template>
                            <template x-for="(func, i) in functions" :key="i">
                                <div class="flex items-start gap-2">
                                    <textarea :name="`functions[${i}]`" x-model="functions[i]" rows="2"
                                              placeholder="Describa la función del cargo..."
                                              class="wis-input flex-1 text-sm"
                                              required></textarea>
                                    <button type="button" @click="removeFunction(i)"
                                            class="mt-1 rounded-md p-1.5 text-red-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        @error('functions.*')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Columna lateral --}}
                <div class="space-y-5">
                    {{-- Card: Correos informativos --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white">Correos informativos</h3>
                            <button type="button" @click="addEmail()"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Agregar
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-if="emails.length === 0">
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">Sin correos asociados.</p>
                            </template>
                            <template x-for="(email, i) in emails" :key="i">
                                <div class="flex items-center gap-2">
                                    <input type="email" :name="`emails[${i}]`" x-model="emails[i]"
                                           placeholder="correo@ejemplo.com"
                                           class="wis-input flex-1 text-sm"
                                           required>
                                    <button type="button" @click="removeEmail(i)"
                                            class="rounded-md p-1.5 text-red-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        @error('emails.*')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Card: Acciones --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-col gap-3">
                            <button type="submit"
                                    class="btn-primary w-full justify-center">
                                Registrar cargo
                            </button>
                            <a href="{{ route('rh.cargos.index') }}"
                               class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
