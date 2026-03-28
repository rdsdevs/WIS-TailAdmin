@extends('layouts.fullscreen-layout')

@section('content')
    <style>
        /* Ajustes específicos para el datepicker en el login para que el icono quede a la izquierda */
        .login-datepicker .relative span[x-ref="calIcon"] {
            left: 1rem !important;
            right: auto !important;
        }
        .login-datepicker input {
            padding-left: 2.75rem !important;
        }
    </style>
    <div class="relative flex min-h-screen flex-col bg-white font-ubuntu dark:bg-gray-900">
        
        <div class="flex flex-1 flex-col lg:flex-row">
            {{-- Panel Izquierdo: Formulario --}}
            <div class="flex w-full flex-1 flex-col items-center justify-center p-8 lg:w-1/2">
                <div class="w-full max-w-sm">
                    {{-- Encabezado del Formulario --}}
                    <div class="mb-10 text-center">
                        <h1 class="text-2xl font-bold tracking-tight text-[#161950] dark:text-white uppercase">
                            Bienvenido al sistema
                        </h1>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Identifíquese para continuar
                        </p>
                    </div>

                    {{-- Alerta de error general --}}
                    @if ($errors->any() && ! $errors->has('document_number') && ! $errors->has('document_issued_at') && ! $errors->has('password'))
                        <div class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        {{-- Número de Documento --}}
                        <div>
                            <label for="document_number" class="mb-2 block text-xs font-bold text-gray-600 uppercase dark:text-gray-400">
                                Documento de identidad
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5a2.25 2.25 0 002.25 2.25zm.001-12h.001v.001h-.001V7.5zm0 3h.001v.001h-.001v-.001zm0 3h.001v.001h-.001v-.001z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    id="document_number"
                                    name="document_number"
                                    value="{{ old('document_number') }}"
                                    placeholder="Ingrese su número"
                                    class="block w-full rounded-xl border-none bg-gray-100 py-3 pl-11 pr-4 text-sm text-gray-900 focus:ring-2 focus:ring-[#3641f5] dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                                    required
                                />
                            </div>
                            @error('document_number')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Fecha de Expedición --}}
                        <div class="login-datepicker">
                            <x-form.date-picker
                                name="document_issued_at"
                                label="Fecha de expedición"
                                value="{{ old('document_issued_at') }}"
                                placeholder="Seleccione la fecha"
                                :error="$errors->has('document_issued_at')"
                                inputClasses="!bg-gray-100 !border-none !rounded-xl !py-3 !pl-11"
                            />
                            @error('document_issued_at')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Contraseña --}}
                        <div>
                            <label for="password" class="mb-2 block text-xs font-bold text-gray-600 uppercase dark:text-gray-400">
                                Contraseña de acceso
                            </label>
                            <div class="relative" x-data="{ show: false }">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </div>
                                <input
                                    :type="show ? 'text' : 'password'"
                                    id="password"
                                    name="password"
                                    placeholder="••••••••"
                                    class="block w-full rounded-xl border-none bg-gray-100 py-3 pl-11 pr-12 text-sm text-gray-900 focus:ring-2 focus:ring-[#3641f5] dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="show = !show"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                >
                                    <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.477 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Recordarme y Olvido --}}
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-[#3641f5] focus:ring-[#3641f5]">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Recordarme</span>
                            </label>
                            <a href="#" class="text-xs font-semibold text-[#3641f5] hover:underline dark:text-blue-400">¿Olvidó su contraseña?</a>
                        </div>

                        {{-- Botón Iniciar Sesión --}}
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-3 rounded-full bg-[#2a31d8] py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-500/30 transition hover:bg-[#161950] focus:ring-4 focus:ring-blue-300"
                        >
                            INICIAR SESIÓN
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>

                    {{-- Footer del Formulario --}}
                    <div class="mt-12 text-center">
                        <p class="text-[14px] font-medium tracking-widest text-gray-400 uppercase">
                            Web Information System {{ config('app.version') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Panel Derecho: Branding --}}
            <div class="relative hidden w-full flex-1 flex-col items-center justify-center overflow-hidden bg-[#161950] lg:flex lg:w-1/2">
                {{-- Fondo con patrón sutil --}}
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;"></div>
                
                <div class="relative z-10 flex flex-col items-center gap-12">
                   {{-- Logo WIS --}}
                    <img src="{{ asset('images/brand/wis.svg') }}" alt="Logo WIS" class="h-40 w-auto drop-shadow-xl">
                  {{-- Logo ASCUN --}}
                    <img src="{{ asset('images/brand/logo.png') }}" alt="Logo ASCUN" class="h-40 w-auto drop-shadow-2xl">
                </div>

                {{-- Decoración --}}
                <div class="absolute -bottom-20 -right-20 h-64 w-64 rounded-full bg-blue-600/20 blur-3xl"></div>
                <div class="absolute -top-20 -left-20 h-64 w-64 rounded-full bg-purple-600/20 blur-3xl"></div>
            </div>
        </div>

        {{-- Footer Global de la Página --}}
        <footer class="flex h-16 flex-col items-center justify-between border-t border-gray-100 px-8 py-4 sm:flex-row dark:border-gray-800 dark:bg-gray-900/50">
            <div class="text-m text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} - <span class="font-bold">WIS</span> - Web Information System
            </div>
            
            <div class="mt-2 flex items-center gap-2 sm:mt-0">
                <span class="text-[14px] font-medium text-gray-400 uppercase tracking-tighter">Desarrollado por</span>
                <img src="{{ asset('images/brand/rds.svg') }}" alt="RDS" class="h-4 w-auto opacity-60 hover:opacity-100 transition-opacity">
            </div>
        </footer>
    </div>
@endsection
