<?php

use App\Models\RH\CommittedValue;
use App\Services\RH\CommittedValueService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public string $contractId = '';

    public bool $open = false;

    public ?string $committedValueId = null;

    public string $label = '';

    public function mount(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    #[On('open-committed-value-delete')]
    public function openFor(string $committedValueId, string $label = ''): void
    {
        $cv = CommittedValue::findOrFail($committedValueId);

        if ($cv->contract_id !== $this->contractId) {
            return;
        }

        $this->authorize('delete', $cv);

        $this->committedValueId = $cv->id;
        $this->label = $label !== '' ? $label : $cv->cost_center;
        $this->open = true;
    }

    public function confirm(CommittedValueService $service): void
    {
        if ($this->committedValueId === null) {
            return;
        }

        $cv = CommittedValue::findOrFail($this->committedValueId);
        $this->authorize('delete', $cv);

        try {
            $service->delete($cv);
            session()->flash('exito', 'Valor comprometido eliminado correctamente.');
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->close();
        $this->dispatch('committed-value-deleted');
        $this->redirectRoute('rh.contratos.valores-comprometidos.index', ['contrato' => $this->contractId], navigate: true);
    }

    public function close(): void
    {
        $this->reset(['committedValueId', 'label', 'open']);
    }
}; ?>

<div>
    @if($open)
        <div
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="committed-value-delete-title"
            wire:click.self="close">
            <div class="relative my-4 w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800 sm:my-0">
                {{-- Cabecera --}}
                <div class="flex items-start gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 id="committed-value-delete-title" class="text-base font-semibold text-gray-900 dark:text-white">
                            Eliminar valor comprometido
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Esta acción no se puede deshacer. La línea queda archivada en el historial de auditoría.
                        </p>
                    </div>
                </div>

                {{-- Cuerpo --}}
                <div class="px-6 py-5">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        ¿Está seguro que desea eliminar el valor:
                    </p>
                    <p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900 dark:bg-gray-700 dark:text-white">
                        {{ $label ?: '—' }}
                    </p>
                </div>

                {{-- Pie --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="close"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="confirm"
                        wire:loading.attr="disabled"
                        wire:target="confirm"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-800">
                        <span wire:loading wire:target="confirm">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
