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

    /* Animaciones círculos decorativos */
    @keyframes deco-pulse {
        0%, 100% { transform: scale(1);   opacity: 0.9; }
        50%       { transform: scale(1.08); opacity: 1; }
    }
    .deco-circle      { animation: deco-pulse 8s ease-in-out infinite; }
    .deco-circle-slow { animation: deco-pulse 12s ease-in-out infinite reverse; }
</style>

<div class="flex min-h-screen flex-col font-sans antialiased">

    {{-- Contenido principal: split-screen con fondo único --}}
    <div class="branding-gradient relative flex flex-1 flex-col overflow-hidden lg:flex-row">

        {{-- Círculos decorativos globales --}}
        <div class="deco-circle pointer-events-none absolute -left-20 -top-20
                    h-80 w-80 rounded-full" style="background:rgba(255,255,255,0.18)"></div>
        <div class="deco-circle-slow pointer-events-none absolute left-1/4 top-10
                    h-56 w-56 rounded-full" style="background:rgba(255,255,255,0.08)"></div>
        <div class="deco-circle pointer-events-none absolute -left-16 top-1/2 -translate-y-1/2
                    h-40 w-40 rounded-full" style="background:rgba(255,255,255,0.07)"></div>
        <div class="deco-circle-slow pointer-events-none absolute -bottom-16 left-10
                    h-72 w-72 rounded-full" style="background:rgba(255,255,255,0.15)"></div>
        <div class="deco-circle pointer-events-none absolute -right-16 -top-16
                    h-64 w-64 rounded-full" style="background:rgba(255,255,255,0.12)"></div>
        <div class="deco-circle-slow pointer-events-none absolute -bottom-20 right-8
                    h-60 w-60 rounded-full" style="background:rgba(255,255,255,0.10)"></div>

        {{-- ================================================
             PANEL IZQUIERDO — Formulario
        ================================================ --}}
        <div class="relative flex w-full flex-1 items-center justify-center p-6
                    lg:w-1/2 xl:w-7/12">

            {{-- Card blanca del formulario --}}
            <div class="card-shadow w-full max-w-md rounded-2xl bg-white px-8 py-10 sm:px-10 sm:py-12">

                {{-- Encabezado de la card --}}
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 flex justify-center">
                        <img src="{{ asset('images/brand/w.svg') }}" alt="WIS" class="h-14 w-14 object-contain" />
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#161950]">
                        ¡Bienvenid@!
                    </h1>
                    <p class="mt-1.5 text-sm text-gray-500">
                        Ingresa tus credenciales para acceder al sistema
                    </p>
                </div>

                {{-- Alerta de error general --}}
                @if ($errors->any() && !$errors->has('document_number') && !$errors->has('document_issued_at') && !$errors->has('password'))
                    <div class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif

                {{-- Formulario --}}
                <form
                    method="POST"
                    action="{{ route('login') }}"
                    class="space-y-5"
                    x-data="{ showPass: false }"
                >
                    @csrf

                    {{-- Campo: Número de Documento --}}
                    <div>
                        <label for="document_number"
                               class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Usuario (Número de Documento)
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#2a31d8]/60">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5a2.25 2.25 0 002.25 2.25zm.001-12h.001v.001h-.001V7.5zm0 3h.001v.001h-.001v-.001zm0 3h.001v.001h-.001v-.001z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="document_number"
                                name="document_number"
                                value="{{ old('document_number') }}"
                                placeholder="72295836"
                                autocomplete="username"
                                required
                                class="wis-input block w-full rounded-xl border border-transparent bg-[#EEF2FF]
                                       py-3 pl-11 pr-4 text-sm text-gray-800 placeholder-gray-400
                                       transition focus:border-[#2a31d8]/30 focus:bg-white"
                            />
                        </div>
                        @error('document_number')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Campo: Fecha de Expedición --}}
                    <div class="login-datepicker">
                        <x-form.date-picker
                            name="document_issued_at"
                            label="Fecha de expedición del documento"
                            value="{{ old('document_issued_at') }}"
                            placeholder="Seleccione la fecha"
                            :error="$errors->has('document_issued_at')"
                            inputClasses="!bg-wis-field !border-transparent !rounded-xl !py-3 !pl-11"
                        />
                        @error('document_issued_at')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Campo: Contraseña --}}
                    <div>
                        <label for="password"
                               class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Contraseña
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#2a31d8]/60">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <input
                                :type="showPass ? 'text' : 'password'"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                                class="wis-input block w-full rounded-xl border border-transparent bg-[#EEF2FF]
                                       py-3 pl-11 pr-12 text-sm text-gray-800 placeholder-gray-400
                                       transition focus:border-[#2a31d8]/30 focus:bg-white"
                            />
                            <button
                                type="button"
                                @click="showPass = !showPass"
                                aria-label="Mostrar u ocultar contraseña"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5
                                       text-gray-400 transition hover:text-[#2a31d8]"
                            >
                                {{-- Ojo abierto --}}
                                <svg x-show="!showPass" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{-- Ojo tachado --}}
                                <svg x-show="showPass" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:none">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Fila: Mantenerme conectado + Olvidaste contraseña --}}
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex cursor-pointer select-none items-center gap-2">
                            <input
                                type="checkbox"
                                name="remember"
                                class="h-4 w-4 rounded border-gray-300 text-[#2a31d8]
                                       focus:ring-2 focus:ring-[#2a31d8]/30"
                            />
                            <span class="text-sm text-gray-600">Mantenerme conectado</span>
                        </label>
                        <a href="#"
                           class="text-sm font-semibold text-[#2a31d8] transition hover:text-[#161950] hover:underline">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    {{-- Botón Iniciar Sesión --}}
                    <button
                        type="submit"
                        class="btn-primary mt-2 flex w-full items-center justify-center gap-3
                               rounded-xl py-3.5 text-sm font-bold tracking-wide text-white shadow-md"
                    >
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                        Iniciar Sesión
                    </button>

                </form>{{-- fin form --}}

                {{-- Enlace soporte --}}
                <p class="mt-8 text-center text-xs text-gray-400">
                    ¿Necesitas ayuda?
                    <a href="#" class="font-semibold text-[#2a31d8] hover:underline">
                        Contacta soporte técnico
                    </a>
                </p>

            </div>{{-- fin card --}}

        </div>{{-- fin panel izquierdo --}}


        {{-- ================================================
             PANEL DERECHO — Branding
             (oculto en móvil, visible desde lg)
        ================================================ --}}
        <div class="relative hidden w-full flex-col items-center justify-center px-12 py-16 lg:flex lg:w-1/2 xl:w-5/12">

            <div class="relative z-10 flex max-w-xs flex-col items-center gap-8 text-center xl:max-w-sm">

                <h2 class="text-4xl font-extrabold leading-tight text-white xl:text-5xl">
                    Bienvenido a
                </h2>

                <img src="{{ asset('images/brand/wis.svg') }}" alt="Logo WIS" class="h-28 w-auto drop-shadow-xl xl:h-32" />

                <p class="text-justify text-sm leading-relaxed text-blue-100/75">
                    Sistema integral de gestión empresarial para la Asociación Colombiana de Universidades.
                    Administra recursos, inventarios y talento humano de manera centralizada.
                </p>

                <div class="h-px w-full bg-white/10"></div>

                <div class="flex w-full items-center justify-center">
                    <img src="{{ asset('images/brand/logo.png') }}" alt="Logo ASCUN"
                         class="h-24 w-auto object-contain drop-shadow-lg xl:h-28" />
                </div>

            </div>

        </div>{{-- fin panel derecho --}}

    </div>{{-- fin split-screen --}}


    {{-- ================================================
         FOOTER GLOBAL
    ================================================ --}}
    <footer class="flex flex-col items-center justify-between gap-2 border-t border-gray-100
                    bg-white px-6 py-3 sm:h-14 sm:flex-row sm:gap-0">

        {{-- Izquierda: Conexión Segura --}}
        <div class="flex items-center gap-1.5 text-xs text-gray-500">
            <svg class="h-3.5 w-3.5 flex-shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <span class="font-medium text-green-600">Conexión Segura</span>
        </div>

        {{-- Centro: Copyright --}}
        <div class="text-xs text-gray-500">
            &copy; {{ date('Y') }} - <span class="font-bold text-gray-700">WIS</span> - Web Information System
        </div>

        {{-- Derecha: Desarrollado por RDS --}}
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <span class="font-medium uppercase tracking-tighter">Desarrollado por</span>
            <img src="{{ asset('images/brand/rds.svg') }}" alt="RDS" class="h-4 w-auto opacity-60 transition-opacity hover:opacity-100" />
        </div>

    </footer>{{-- fin footer --}}

</div>{{-- fin layout principal --}}


{{-- ================================================
     BANNER DE ANUNCIO — Login (fijo, vigencia 30 días)
     Lógica: localStorage key "wis_announcement_v2_closed"
     Expira: 2026-04-28
================================================ --}}
<div
    x-data="{
        visible: false,
        EXPIRY: '2026-04-28',
        STORAGE_KEY: 'wis_announcement_v2_closed',
        init() {
            const today = new Date().toISOString().slice(0, 10);
            const closed = localStorage.getItem(this.STORAGE_KEY);
            this.visible = (today <= this.EXPIRY) && !closed;
        },
        close() {
            localStorage.setItem(this.STORAGE_KEY, '1');
            this.visible = false;
        }
    }"
    x-show="visible"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-5 right-5 z-50 w-full max-w-lg"
>
    <div class="relative flex flex-col gap-3 rounded-2xl border border-blue-100 bg-white p-5 shadow-2xl sm:flex-row sm:items-start sm:gap-5"
         style="box-shadow: 0 20px 60px -10px rgba(13,27,94,0.25), 0 4px 16px -4px rgba(13,27,94,0.12);">

        {{-- Icono --}}
        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl"
             style="background: linear-gradient(135deg, #0d1b5e 0%, #1e44a0 100%);">
            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
            </svg>
        </div>

        {{-- Contenido --}}
        <div class="min-w-0 flex-1">
            <div class="mb-1 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest text-white"
                      style="background: linear-gradient(135deg, #0d1b5e, #1e44a0);">
                    Nueva versión
                </span>
                <span class="text-[10px] font-medium uppercase tracking-wider text-gray-400">WIS ASCUN 2.0</span>
            </div>
            <h3 class="text-sm font-bold leading-snug text-gray-900">
                Plataforma renovada para mayor productividad y rendimiento
            </h3>
            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                Hemos actualizado integralmente WIS ASCUN con una nueva interfaz optimizada, tiempos de respuesta mejorados y flujos de trabajo más ágiles. Esta versión ha sido diseñada para potenciar la eficiencia de su gestión institucional diaria. Agradecemos su confianza en nuestra plataforma.
            </p>
            <p class="mt-2 text-[10px] font-medium text-blue-600">
                — Equipo de Desarrollo · RDS · Marzo {{ date('Y') }}
            </p>
        </div>

        {{-- Botón cerrar --}}
        <button
            @click="close()"
            aria-label="Cerrar anuncio"
            class="absolute right-3 top-3 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

    </div>
</div>


{{-- ================================================
     TOAST DE BIENVENIDA — Una sola vez al ingresar
     Cierre automático en 8 segundos
================================================ --}}
<div
    x-data="{
        visible: false,
        progress: 100,
        EXPIRY: '2026-04-28',
        STORAGE_KEY: 'wis_announcement_v2_seen',
        timer: null,
        interval: null,
        init() {
            const today = new Date().toISOString().slice(0, 10);
            const seen  = localStorage.getItem(this.STORAGE_KEY);
            if ((today <= this.EXPIRY) && !seen) {
                setTimeout(() => {
                    this.visible = true;
                    this.startCountdown();
                }, 1800);
            }
        },
        startCountdown() {
            const duration = 8000;
            const steps    = 80;
            const stepMs   = duration / steps;
            this.interval  = setInterval(() => {
                this.progress -= (100 / steps);
                if (this.progress <= 0) this.dismiss();
            }, stepMs);
        },
        dismiss() {
            clearInterval(this.interval);
            localStorage.setItem(this.STORAGE_KEY, '1');
            this.visible = false;
        }
    }"
    x-show="visible"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-5 right-5 z-[60] w-80 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl"
    style="box-shadow: 0 20px 60px -10px rgba(13,27,94,0.22), 0 4px 16px -4px rgba(13,27,94,0.10);"
>
    {{-- Barra de progreso --}}
    <div class="h-1 w-full" style="background: linear-gradient(135deg, #0d1b5e, #1e44a0);">
        <div class="h-full transition-all ease-linear"
             style="background: rgba(255,255,255,0.4);"
             :style="'width:' + progress + '%'"></div>
    </div>

    <div class="p-4">
        <div class="flex items-start gap-3">
            {{-- Icono --}}
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg"
                 style="background: linear-gradient(135deg, #0d1b5e 0%, #1e44a0 100%);">
                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            {{-- Texto --}}
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-gray-900">¡Bienvenido a WIS ASCUN 2.0!</p>
                <p class="mt-0.5 text-xs leading-relaxed text-gray-500">
                    Disfrute la nueva interfaz optimizada para mayor productividad y rendimiento institucional.
                </p>
            </div>
            {{-- Cerrar --}}
            <button @click="dismiss()" aria-label="Cerrar notificación" class="flex-shrink-0 text-gray-400 transition hover:text-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>

@endsection
