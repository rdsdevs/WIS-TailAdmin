@props([
    'id'           => 'password-' . uniqid(),
    'name'         => 'password',
    'label'        => 'Contraseña',
    'autocomplete' => 'new-password',
    'required'     => false,
    'error'        => false,
    'hint'         => null,
])

<div x-data="{ show: false }">
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ $label }}@if($required)<span class="ml-0.5 text-red-500" aria-hidden="true">*</span>@endif
    </label>

    <div class="relative">
        <input
            {{ $attributes->except(['class']) }}
            :type="show ? 'text' : 'password'"
            id="{{ $id }}"
            name="{{ $name }}"
            autocomplete="{{ $autocomplete }}"
            class="h-11 w-full rounded-lg border px-4 py-2.5 pr-11 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 bg-transparent text-gray-800 dark:text-white/90 dark:placeholder:text-white/30 dark:bg-gray-900
                {{ $error
                    ? 'border-red-500 dark:border-red-500 focus:border-red-400 focus:ring-red-500/20'
                    : 'border-gray-300 dark:border-gray-700 focus:border-brand-300 focus:ring-brand-500/20 dark:focus:border-brand-800'
                }}"
        />

        <button
            type="button"
            @click="show = !show"
            :title="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors focus:outline-none"
            tabindex="-1"
        >
            {{-- Ojo abierto — visible cuando show = false --}}
            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            {{-- Ojo cerrado — visible cuando show = true --}}
            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
        </button>
    </div>

    @if($hint)
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $hint }}</p>
    @endif
</div>
