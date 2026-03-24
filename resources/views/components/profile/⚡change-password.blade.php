<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

new class extends Component {

    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public bool $saved = false;

    public function save(): void
    {
        $this->validate([
            'currentPassword'         => ['required'],
            'newPassword'             => ['required', Password::min(8)->letters()->numbers(), 'confirmed'],
            'newPasswordConfirmation' => ['required'],
        ], [
            'currentPassword.required'         => 'La contraseña actual es obligatoria.',
            'newPassword.required'             => 'La nueva contraseña es obligatoria.',
            'newPassword.min'                  => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'newPassword.confirmed'            => 'La confirmación de contraseña no coincide.',
            'newPasswordConfirmation.required' => 'Debe confirmar la nueva contraseña.',
        ]);

        if (! Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->addError('currentPassword', 'La contraseña actual es incorrecta.');
            return;
        }

        auth()->user()->update(['password' => Hash::make($this->newPassword)]);

        $this->currentPassword         = '';
        $this->newPassword             = '';
        $this->newPasswordConfirmation = '';
        $this->saved                   = true;
    }
}; ?>

<div class="p-5 mt-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
    <div>
        <h4 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">
            Cambiar contraseña
        </h4>

        {{-- Mensaje de éxito --}}
        @if ($saved)
            <div
                x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => show = false, 3000)"
                x-transition
                class="mb-5 flex items-center gap-2 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400"
                role="alert"
            >
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Contraseña actualizada correctamente.
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 lg:gap-6">

            {{-- Contraseña actual --}}
            <div class="lg:col-span-2">
                <label
                    for="currentPassword"
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                >
                    Contraseña actual
                </label>
                <input
                    type="password"
                    id="currentPassword"
                    wire:model="currentPassword"
                    autocomplete="current-password"
                    placeholder="Ingrese su contraseña actual"
                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30
                        @error('currentPassword') border-red-500 dark:border-red-500 @enderror"
                />
                @error('currentPassword')
                    <p class="mt-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nueva contraseña --}}
            <div>
                <label
                    for="newPassword"
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                >
                    Nueva contraseña
                </label>
                <input
                    type="password"
                    id="newPassword"
                    wire:model="newPassword"
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30
                        @error('newPassword') border-red-500 dark:border-red-500 @enderror"
                />
                @error('newPassword')
                    <p class="mt-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirmar nueva contraseña --}}
            <div>
                <label
                    for="newPasswordConfirmation"
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                >
                    Confirmar nueva contraseña
                </label>
                <input
                    type="password"
                    id="newPasswordConfirmation"
                    wire:model="newPasswordConfirmation"
                    autocomplete="new-password"
                    placeholder="Repita la nueva contraseña"
                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30
                        @error('newPasswordConfirmation') border-red-500 dark:border-red-500 @enderror"
                />
                @error('newPasswordConfirmation')
                    <p class="mt-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

        </div>

        {{-- Botón de acción --}}
        <div class="mt-6 flex justify-end">
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/20 disabled:opacity-60 dark:bg-brand-500 dark:hover:bg-brand-400"
            >
                <span wire:loading.remove wire:target="save">Actualizar contraseña</span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Guardando...
                </span>
            </button>
        </div>
    </div>
</div>
