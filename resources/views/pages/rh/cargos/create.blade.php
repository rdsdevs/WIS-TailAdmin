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

    @include('pages.rh.cargos._cargo-form-script')

    <div class="mt-6">

        <form method="POST" action="{{ route('rh.cargos.store') }}"
              x-data="cargoFormData({
                  positionName:    @js(old('name', '')),
                  editingName:     @js(old('name') ? false : true),
                  emails:          @js(old('emails', [])),
                  functions:       @js(old('functions', [])),
                  responsibilities: @js(old('responsibilities', [])),
                  authorities:     @js(old('authorities', []))
              })">
            @csrf
            <input type="hidden" name="institution_id" value="{{ auth()->user()->institution_id }}">
            <input type="hidden" name="is_active" value="1">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- ============================================================ --}}
                {{-- Columna principal --}}
                {{-- ============================================================ --}}
                <div class="lg:col-span-2 space-y-5">

                    {{-- Encabezado Datos del cargo (FUERA del card) --}}
                    <div class="flex items-center gap-3 mb-3">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white">Datos del cargo</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Información principal de identificación</p>
                        </div>
                    </div>

                    {{-- Card: Datos básicos --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                            {{-- Campo: Nombre del cargo con toggle vista/edición --}}
                            <div class="sm:col-span-2">

                                {{-- Input hidden que siempre se envía con el form --}}
                                <input type="hidden" name="name" :value="positionName">

                                {{-- MODO VISTA --}}
                                <div x-show="!editingName"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     @click="editingName = true; $nextTick(() => $refs.nameInput.focus())"
                                     class="group/view relative flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60 dark:hover:bg-gray-700/60">
                                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">1</span>
                                    <span class="flex-1 truncate text-sm font-medium text-gray-800 dark:text-gray-200" x-text="positionName"></span>
                                    <span class="opacity-60 group-hover/view:opacity-100 transition-opacity">
                                        <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </span>
                                    <button type="button"
                                            @click.stop="positionName = ''; editingName = true; $nextTick(() => $refs.nameInput.focus())"
                                            aria-label="Limpiar nombre del cargo"
                                            class="rounded-full p-0.5 text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- MODO EDICIÓN --}}
                                <div x-show="editingName"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100">
                                    <input type="text"
                                           x-ref="nameInput"
                                           x-model="positionName"
                                           @blur="if (positionName.trim() !== '') editingName = false"
                                           required
                                           maxlength="150"
                                           placeholder="Nombre del cargo * · Ej: Coordinador de Programas"
                                           class="block w-full rounded-xl border-2 bg-transparent px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-colors duration-200 focus:outline-none focus:ring-2
                                                  {{ $errors->has('name')
                                                      ? 'border-red-400 focus:ring-red-400/30 dark:border-red-500'
                                                      : 'border-violet-300 focus:ring-violet-400/30 dark:border-violet-600' }}
                                                  dark:text-white dark:placeholder-gray-500">
                                    @error('name')
                                        <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                            <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- Tabs: Funciones / Responsabilidades / Autoridades --}}
                    {{-- ============================================================ --}}
                    <div x-data="{ tab: 'funciones' }">

                        {{-- Cabecera de tabs --}}
                        <div class="flex items-center gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow dark:border-gray-700 dark:bg-gray-800">

                            {{-- Tab Funciones --}}
                            <button type="button"
                                    @click="tab = 'funciones'"
                                    :class="tab === 'funciones'
                                        ? 'bg-violet-600 text-white shadow-sm'
                                        : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                                    class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span class="hidden sm:inline">Funciones</span>
                                <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold"
                                      :class="tab === 'funciones' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                                      x-text="functions.length"></span>
                            </button>

                            {{-- Tab Responsabilidades --}}
                            <button type="button"
                                    @click="tab = 'responsabilidades'"
                                    :class="tab === 'responsabilidades'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                                    class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="hidden sm:inline">Responsabilidades</span>
                                <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold"
                                      :class="tab === 'responsabilidades' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                                      x-text="responsibilities.length"></span>
                            </button>

                            {{-- Tab Autoridades --}}
                            <button type="button"
                                    @click="tab = 'autoridades'"
                                    :class="tab === 'autoridades'
                                        ? 'bg-amber-500 text-white shadow-sm'
                                        : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                                    class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span class="hidden sm:inline">Autoridades</span>
                                <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold"
                                      :class="tab === 'autoridades' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                                      x-text="authorities.length"></span>
                            </button>

                        </div>

                        {{-- ── Panel: Funciones ────────────────────────────── --}}
                        <div x-show="tab === 'funciones'"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="mt-4">

                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-900/30">
                                        <svg class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-800 dark:text-white">Funciones del cargo</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Actividades principales del cargo</p>
                                    </div>
                                </div>
                                <button type="button" @click="addFunction()"
                                        aria-label="Agregar función"
                                        title="Agregar función"
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-violet-600 text-white shadow-sm hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 transition-colors dark:focus:ring-offset-gray-800">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                                {{-- Inputs hidden para enviar el array --}}
                                <template x-for="(item, i) in functions" :key="'fh' + i">
                                    <input type="hidden" :name="`functions[${i}]`" :value="item">
                                </template>

                                <div class="space-y-2">
                                    <template x-if="functions.length === 0">
                                        <div class="flex flex-col items-center justify-center py-10">
                                            <svg class="mb-3 h-10 w-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-400 dark:text-gray-500">Sin funciones agregadas</p>
                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-600">Haga clic en el botón <strong class="text-violet-500">+</strong> para comenzar</p>
                                        </div>
                                    </template>
                                    <template x-for="(func, i) in functions" :key="'f' + i">
                                        <div x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             class="group flex items-start gap-3">
                                            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700 mt-2 dark:bg-violet-900/30 dark:text-violet-400"
                                                 x-text="i + 1"></div>
                                            <div class="flex-1 min-w-0">
                                                <div x-show="editingFunc !== i"
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     @click="editingFunc = i; $nextTick(() => { let ta = $el.closest('.group').querySelector('textarea'); if(ta){ ta.focus(); ta.style.height='auto'; ta.style.height=ta.scrollHeight+'px'; } })"
                                                     class="group/view relative cursor-pointer rounded-xl px-3 py-2 transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words pr-7"
                                                       x-text="functions[i] || ''"></p>
                                                    <span class="absolute right-2 top-2 opacity-0 group-hover/view:opacity-100 transition-opacity duration-150">
                                                        <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div x-show="editingFunc === i"
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100">
                                                    <textarea x-model="functions[i]"
                                                              @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                                              x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight || 40) + 'px' })"
                                                              @blur="if ((functions[i] || '').trim() !== '') editingFunc = null"
                                                              rows="1"
                                                              maxlength="500"
                                                              placeholder="Describa la función del cargo..."
                                                              style="min-height: 2.5rem; overflow: hidden; resize: none;"
                                                              :class="(functions[i] || '').trim() === ''
                                                                  ? 'border-violet-300 dark:border-violet-600 focus:ring-violet-400/30'
                                                                  : 'border-green-400 dark:border-green-500 focus:ring-green-400/30'"
                                                              class="block w-full rounded-xl border-2 bg-transparent px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition-colors duration-200 focus:outline-none focus:ring-2 dark:text-white dark:placeholder-gray-500"></textarea>
                                                    <div class="mt-1 flex items-center justify-between px-1">
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">Pulse Tab o haga clic afuera para confirmar</span>
                                                        <span class="text-xs text-gray-400 dark:text-gray-500" x-text="(functions[i] || '').length + ' / 500'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-1.5 flex-shrink-0">
                                                <button type="button" @click="confirmFunc(i)"
                                                        x-show="confirmingFuncDelete !== i"
                                                        aria-label="Eliminar función"
                                                        class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 hover:border-red-300 hover:bg-red-50 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500 dark:hover:border-red-700 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                                <button type="button" @click="doRemoveFunction(i)"
                                                        x-show="confirmingFuncDelete === i"
                                                        x-transition
                                                        class="text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-full px-2 py-1 focus:outline-none transition-colors">
                                                    ¿Eliminar?
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                @error('functions.*')
                                    <p class="mt-2 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        {{-- ── Panel: Responsabilidades ─────────────────────── --}}
                        <div x-show="tab === 'responsabilidades'"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="mt-4">

                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
                                        <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-800 dark:text-white">Responsabilidades del cargo</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Compromisos y obligaciones del cargo</p>
                                    </div>
                                </div>
                                <button type="button" @click="addResponsibility()"
                                        aria-label="Agregar responsabilidad"
                                        title="Agregar responsabilidad"
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors dark:focus:ring-offset-gray-800">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                                {{-- Inputs hidden para enviar el array --}}
                                <template x-for="(item, i) in responsibilities" :key="'rh' + i">
                                    <input type="hidden" :name="`responsibilities[${i}]`" :value="item">
                                </template>

                                <div class="space-y-2">
                                    <template x-if="responsibilities.length === 0">
                                        <div class="flex flex-col items-center justify-center py-10">
                                            <svg class="mb-3 h-10 w-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-400 dark:text-gray-500">Sin responsabilidades agregadas</p>
                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-600">Haga clic en el botón <strong class="text-indigo-500">+</strong> para comenzar</p>
                                        </div>
                                    </template>
                                    <template x-for="(resp, i) in responsibilities" :key="'r' + i">
                                        <div x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             class="group flex items-start gap-3">
                                            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 mt-2 dark:bg-indigo-900/30 dark:text-indigo-400"
                                                 x-text="i + 1"></div>
                                            <div class="flex-1 min-w-0">
                                                <div x-show="editingResp !== i"
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     @click="editingResp = i; $nextTick(() => { let ta = $el.closest('.group').querySelector('textarea'); if(ta){ ta.focus(); ta.style.height='auto'; ta.style.height=ta.scrollHeight+'px'; } })"
                                                     class="group/view relative cursor-pointer rounded-xl px-3 py-2 transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words pr-7"
                                                       x-text="responsibilities[i] || ''"></p>
                                                    <span class="absolute right-2 top-2 opacity-0 group-hover/view:opacity-100 transition-opacity duration-150">
                                                        <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div x-show="editingResp === i"
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100">
                                                    <textarea x-model="responsibilities[i]"
                                                              @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                                              x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight || 40) + 'px' })"
                                                              @blur="if ((responsibilities[i] || '').trim() !== '') editingResp = null"
                                                              rows="1"
                                                              maxlength="1000"
                                                              placeholder="Describa la responsabilidad del cargo..."
                                                              style="min-height: 2.5rem; overflow: hidden; resize: none;"
                                                              :class="(responsibilities[i] || '').trim() === ''
                                                                  ? 'border-indigo-300 dark:border-indigo-600 focus:ring-indigo-400/30'
                                                                  : 'border-green-400 dark:border-green-500 focus:ring-green-400/30'"
                                                              class="block w-full rounded-xl border-2 bg-transparent px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition-colors duration-200 focus:outline-none focus:ring-2 dark:text-white dark:placeholder-gray-500"></textarea>
                                                    <div class="mt-1 flex items-center justify-between px-1">
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">Pulse Tab o haga clic afuera para confirmar</span>
                                                        <span class="text-xs text-gray-400 dark:text-gray-500" x-text="(responsibilities[i] || '').length + ' / 1000'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-1.5 flex-shrink-0">
                                                <button type="button" @click="confirmResp(i)"
                                                        x-show="confirmingRespDelete !== i"
                                                        aria-label="Eliminar responsabilidad"
                                                        class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 hover:border-red-300 hover:bg-red-50 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500 dark:hover:border-red-700 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                                <button type="button" @click="doRemoveResponsibility(i)"
                                                        x-show="confirmingRespDelete === i"
                                                        x-transition
                                                        class="text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-full px-2 py-1 focus:outline-none transition-colors">
                                                    ¿Eliminar?
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                @error('responsibilities.*')
                                    <p class="mt-2 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        {{-- ── Panel: Autoridades ───────────────────────────── --}}
                        <div x-show="tab === 'autoridades'"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="mt-4">

                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/30">
                                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-800 dark:text-white">Autoridades del cargo</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Facultades y poderes de decisión</p>
                                    </div>
                                </div>
                                <button type="button" @click="addAuthority()"
                                        aria-label="Agregar autoridad"
                                        title="Agregar autoridad"
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-500 text-white shadow-sm hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 transition-colors dark:focus:ring-offset-gray-800">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                                {{-- Inputs hidden para enviar el array --}}
                                <template x-for="(item, i) in authorities" :key="'ah' + i">
                                    <input type="hidden" :name="`authorities[${i}]`" :value="item">
                                </template>

                                <div class="space-y-2">
                                    <template x-if="authorities.length === 0">
                                        <div class="flex flex-col items-center justify-center py-10">
                                            <svg class="mb-3 h-10 w-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-400 dark:text-gray-500">Sin autoridades agregadas</p>
                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-600">Haga clic en el botón <strong class="text-amber-500">+</strong> para comenzar</p>
                                        </div>
                                    </template>
                                    <template x-for="(auth, i) in authorities" :key="'a' + i">
                                        <div x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             class="group flex items-start gap-3">
                                            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-700 mt-2 dark:bg-amber-900/30 dark:text-amber-400"
                                                 x-text="i + 1"></div>
                                            <div class="flex-1 min-w-0">
                                                <div x-show="editingAuth !== i"
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     @click="editingAuth = i; $nextTick(() => { let ta = $el.closest('.group').querySelector('textarea'); if(ta){ ta.focus(); ta.style.height='auto'; ta.style.height=ta.scrollHeight+'px'; } })"
                                                     class="group/view relative cursor-pointer rounded-xl px-3 py-2 transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words pr-7"
                                                       x-text="authorities[i] || ''"></p>
                                                    <span class="absolute right-2 top-2 opacity-0 group-hover/view:opacity-100 transition-opacity duration-150">
                                                        <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div x-show="editingAuth === i"
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100">
                                                    <textarea x-model="authorities[i]"
                                                              @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                                              x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight || 40) + 'px' })"
                                                              @blur="if ((authorities[i] || '').trim() !== '') editingAuth = null"
                                                              rows="1"
                                                              maxlength="1000"
                                                              placeholder="Describa la autoridad o facultad del cargo..."
                                                              style="min-height: 2.5rem; overflow: hidden; resize: none;"
                                                              :class="(authorities[i] || '').trim() === ''
                                                                  ? 'border-amber-300 dark:border-amber-600 focus:ring-amber-400/30'
                                                                  : 'border-green-400 dark:border-green-500 focus:ring-green-400/30'"
                                                              class="block w-full rounded-xl border-2 bg-transparent px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition-colors duration-200 focus:outline-none focus:ring-2 dark:text-white dark:placeholder-gray-500"></textarea>
                                                    <div class="mt-1 flex items-center justify-between px-1">
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">Pulse Tab o haga clic afuera para confirmar</span>
                                                        <span class="text-xs text-gray-400 dark:text-gray-500" x-text="(authorities[i] || '').length + ' / 1000'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-1.5 flex-shrink-0">
                                                <button type="button" @click="confirmAuth(i)"
                                                        x-show="confirmingAuthDelete !== i"
                                                        aria-label="Eliminar autoridad"
                                                        class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 hover:border-red-300 hover:bg-red-50 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500 dark:hover:border-red-700 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                                <button type="button" @click="doRemoveAuthority(i)"
                                                        x-show="confirmingAuthDelete === i"
                                                        x-transition
                                                        class="text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-full px-2 py-1 focus:outline-none transition-colors">
                                                    ¿Eliminar?
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                @error('authorities.*')
                                    <p class="mt-2 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                    </div>{{-- /x-data tabs --}}

                </div>

                {{-- ============================================================ --}}
                {{-- Columna lateral --}}
                {{-- ============================================================ --}}
                <div class="space-y-5">

                    {{-- Card: Información del formulario --}}
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 dark:border-blue-900/40 dark:bg-blue-900/10">
                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-blue-600 dark:bg-blue-500">
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <div>
                                <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300">Nuevo cargo</h4>
                                <p class="mt-1 text-xs text-blue-700 dark:text-blue-400 leading-relaxed">
                                    Los cargos definen la estructura organizacional. Puede agregar funciones, responsabilidades, autoridades y correos de notificación asociados.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Encabezado Correos fuera del card --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Correos informativos</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Notificaciones del cargo</p>
                            </div>
                        </div>
                        <button type="button" @click="addEmail()"
                                aria-label="Agregar correo"
                                title="Agregar correo"
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors dark:focus:ring-offset-gray-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Card solo con la lista de correos --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                        <div class="space-y-2">
                            <template x-if="emails.length === 0">
                                <div class="flex flex-col items-center justify-center py-8">
                                    <svg class="mb-2 h-8 w-8 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Sin correos asociados</p>
                                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-600">Haga clic en <strong class="text-emerald-500">+</strong> para agregar</p>
                                </div>
                            </template>
                            <template x-for="(email, i) in emails" :key="'e' + i">
                                <div x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0">

                                    {{-- MODO VISTA --}}
                                    <div x-show="editingEmail !== i"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         @click="editingEmail = i; $nextTick(() => { let inp = $el.closest('div').querySelector('input[type=email]'); if(inp) inp.focus(); })"
                                         class="group/view relative flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <svg class="h-3.5 w-3.5 flex-shrink-0 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-300" x-text="emails[i]"></span>
                                        <span class="opacity-0 group-hover/view:opacity-100 transition-opacity duration-150">
                                            <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </span>
                                    </div>

                                    {{-- MODO EDICIÓN --}}
                                    <div x-show="editingEmail === i"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100">
                                        <div class="flex items-center gap-2">
                                            <div class="relative flex-1">
                                                <input type="email" :name="`emails[${i}]`" x-model="emails[i]"
                                                       placeholder="correo@ejemplo.com"
                                                       @blur="if (isValidEmail(emails[i]) && !isDuplicateEmail(emails[i], i)) editingEmail = null"
                                                       :class="{
                                                           'border-red-400 dark:border-red-600': isDuplicateEmail(emails[i], i),
                                                           'border-green-400 dark:border-green-500': isValidEmail(emails[i]) && !isDuplicateEmail(emails[i], i),
                                                           'border-emerald-300 dark:border-emerald-600': !(emails[i])
                                                       }"
                                                       class="block w-full rounded-xl border-2 bg-transparent px-3 py-2 pr-9 text-sm text-gray-900 placeholder-gray-400 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:text-white dark:placeholder-gray-500"
                                                       required>
                                                <span x-show="isValidEmail(emails[i]) && !isDuplicateEmail(emails[i], i)"
                                                      class="absolute inset-y-0 right-3 flex items-center text-green-500 pointer-events-none">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </span>
                                                <span x-show="isDuplicateEmail(emails[i], i)"
                                                      class="absolute inset-y-0 right-3 flex items-center text-amber-500 pointer-events-none">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <button type="button" @click="confirmEmail(i)"
                                                        x-show="confirmingEmailDelete !== i"
                                                        aria-label="Eliminar correo"
                                                        class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 hover:border-red-300 hover:bg-red-50 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500 dark:hover:border-red-700 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                                <button type="button" @click="doRemoveEmail(i)"
                                                        x-show="confirmingEmailDelete === i"
                                                        x-transition
                                                        class="text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-full px-2 py-1 focus:outline-none transition-colors">
                                                    ¿Eliminar?
                                                </button>
                                            </div>
                                        </div>
                                        <p x-show="isDuplicateEmail(emails[i], i)" x-transition
                                           class="mt-1 px-1 text-xs text-amber-600 dark:text-amber-400">
                                            Este correo ya está en la lista.
                                        </p>
                                    </div>

                                </div>
                            </template>
                        </div>

                        @error('emails.*')
                            <p class="mt-2 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Acciones --}}
                    <div class="space-y-3">

                        {{-- Botón primario --}}
                        <button type="submit"
                                class="group relative w-full overflow-hidden rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-500/30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-[0.98] dark:focus:ring-offset-gray-900">
                            <span class="relative flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 transition-transform duration-200 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Registrar cargo
                            </span>
                        </button>

                        {{-- Botón cancelar --}}
                        <a href="{{ route('rh.cargos.index') }}"
                           class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 active:scale-[0.98] dark:border-gray-700 dark:bg-transparent dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300 dark:focus:ring-offset-gray-900">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Volver a Cargos
                        </a>

                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection
