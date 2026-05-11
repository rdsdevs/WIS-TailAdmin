<?php

declare(strict_types=1);

use App\Models\RH\Position;
use App\Services\RH\PositionService;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {

    // ── Props ─────────────────────────────────────────────────────────────────
    public ?string $cargoId = null;

    // ── Estado del formulario ─────────────────────────────────────────────────
    public string $name = '';

    public bool $isActive = true;

    /** @var array<int,string> */
    public array $emails = [''];

    /** @var array<int,string> */
    public array $functions = [''];

    public function mount(?string $cargoId = null): void
    {
        $this->cargoId = $cargoId;

        if ($cargoId === null) {
            return;
        }

        $position = Position::with(['emails', 'functions'])->findOrFail($cargoId);
        $this->authorize('update', $position);

        $this->name = $position->name;
        $this->isActive = (bool) $position->is_active;

        $emails = $position->emails->pluck('email')->all();
        $this->emails = $emails === [] ? [''] : $emails;

        $functions = $position->functions->pluck('description')->all();
        $this->functions = $functions === [] ? [''] : $functions;
    }

    protected function rules(): array
    {
        $institutionId = auth()->user()->institution_id;

        $uniqueRule = Rule::unique('positions', 'name')
            ->where(fn ($q) => $q->where('institution_id', $institutionId))
            ->whereNull('deleted_at');

        if ($this->cargoId !== null) {
            $uniqueRule->ignore($this->cargoId);
        }

        return [
            'name' => ['required', 'string', 'max:150', $uniqueRule],
            'isActive' => ['boolean'],
            'emails' => ['array'],
            'emails.*' => ['nullable', 'email', 'max:150', 'distinct:ignore_case'],
            'functions' => ['array'],
            'functions.*' => ['nullable', 'string', 'max:500', 'distinct:ignore_case'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre del cargo es obligatorio.',
            'name.max' => 'El nombre del cargo no puede tener más de :max caracteres.',
            'name.unique' => 'Ya existe un cargo con este nombre en su institución.',
            'emails.*.email' => 'Ingrese un correo electrónico válido.',
            'emails.*.distinct' => 'Este correo ya fue agregado en otra fila.',
            'functions.*.distinct' => 'Esta función ya fue agregada en otra fila.',
        ];
    }

    public function addEmail(): void
    {
        $this->emails[] = '';
    }

    public function removeEmail(int $index): void
    {
        unset($this->emails[$index]);
        $this->emails = array_values($this->emails);

        if ($this->emails === []) {
            $this->emails = [''];
        }
    }

    public function addFunction(): void
    {
        $this->functions[] = '';
    }

    public function removeFunction(int $index): void
    {
        unset($this->functions[$index]);
        $this->functions = array_values($this->functions);

        if ($this->functions === []) {
            $this->functions = [''];
        }
    }

    public function save(PositionService $service): void
    {
        $validated = $this->validate();

        $emails = array_values(array_filter(
            array_map('trim', $validated['emails'] ?? []),
            fn (string $v): bool => $v !== '',
        ));

        $functions = array_values(array_filter(
            array_map('trim', $validated['functions'] ?? []),
            fn (string $v): bool => $v !== '',
        ));

        $data = [
            'institution_id' => auth()->user()->institution_id,
            'name' => $validated['name'],
            'is_active' => (bool) $validated['isActive'],
        ];

        try {
            if ($this->cargoId === null) {
                $this->authorize('create', Position::class);
                $position = $service->createFromArray($data, $emails, $functions);
                session()->flash('exito', 'Cargo registrado correctamente.');
                $this->redirect(route('rh.cargos.show', $position->id), navigate: false);

                return;
            }

            $position = Position::findOrFail($this->cargoId);
            $this->authorize('update', $position);
            $service->updateFromArray($position, $data, $emails, $functions);
            session()->flash('exito', 'Cargo actualizado correctamente.');
            $this->redirect(route('rh.cargos.show', $position->id), navigate: false);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'No se pudo guardar el cargo. Intente nuevamente.');
        }
    }
};
?>

<div class="mx-auto max-w-3xl">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

        {{-- Sección 1: Información general --}}
        <section class="mb-8">
            <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Información general</h2>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Datos básicos del cargo en la organización.</p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Nombre --}}
                <div class="sm:col-span-2">
                    <label for="position-name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre del cargo <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="position-name"
                        type="text"
                        wire:model.blur="name"
                        maxlength="150"
                        autocomplete="off"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500"
                        placeholder="Ej: Coordinador de Recursos Humanos"
                        @error('name') aria-invalid="true" @enderror
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Estado activo --}}
                <div class="sm:col-span-2">
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model.live="isActive"
                            class="peer sr-only"
                        >
                        <span class="relative inline-block h-6 w-11 rounded-full bg-gray-300 transition-colors peer-checked:bg-blue-600 dark:bg-gray-600">
                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                        </span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $isActive ? 'Cargo activo' : 'Cargo inactivo' }}
                        </span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Los cargos inactivos no aparecen como opción al crear contratos nuevos.
                    </p>
                </div>
            </div>
        </section>

        {{-- Sección 2: Correos asociados --}}
        <section class="mb-8 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Correos asociados</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Direcciones de correo vinculadas al cargo (opcional).</p>
                </div>
                <button
                    type="button"
                    wire:click="addEmail"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Agregar correo
                </button>
            </div>

            <div class="space-y-2">
                @foreach($emails as $i => $email)
                    <div wire:key="email-row-{{ $i }}" class="flex items-start gap-2">
                        <div class="flex-1">
                            <input
                                type="email"
                                wire:model.blur="emails.{{ $i }}"
                                maxlength="150"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500"
                                placeholder="correo@ejemplo.com"
                                aria-label="Correo {{ $i + 1 }}"
                            >
                            @error('emails.'.$i)
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        @if(count($emails) > 1 || ! empty($email))
                            <button
                                type="button"
                                wire:click="removeEmail({{ $i }})"
                                class="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                                aria-label="Eliminar correo {{ $i + 1 }}"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Sección 3: Funciones del cargo --}}
        <section class="mb-8 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Funciones del cargo</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Responsabilidades y tareas asociadas al cargo (opcional).</p>
                </div>
                <button
                    type="button"
                    wire:click="addFunction"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Agregar función
                </button>
            </div>

            <div class="space-y-2">
                @foreach($functions as $i => $function)
                    <div wire:key="function-row-{{ $i }}" class="flex items-start gap-2">
                        <div class="flex-1">
                            <textarea
                                wire:model.blur="functions.{{ $i }}"
                                rows="2"
                                maxlength="500"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500"
                                placeholder="Ej: Coordinar el proceso de selección y contratación de personal."
                                aria-label="Función {{ $i + 1 }}"
                            ></textarea>
                            @error('functions.'.$i)
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        @if(count($functions) > 1 || ! empty($function))
                            <button
                                type="button"
                                wire:click="removeFunction({{ $i }})"
                                class="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                                aria-label="Eliminar función {{ $i + 1 }}"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Acciones --}}
        <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-between dark:border-gray-700">
            <a
                href="{{ route('rh.cargos.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Cancelar
            </a>

            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-900"
            >
                <span wire:loading wire:target="save">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </span>
                <span wire:loading.remove wire:target="save">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
                {{ $cargoId ? 'Actualizar cargo' : 'Registrar cargo' }}
            </button>
        </div>
    </div>
</div>
