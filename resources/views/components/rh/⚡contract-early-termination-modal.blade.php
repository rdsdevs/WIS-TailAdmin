<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\RH\Contract;
use App\Services\RH\ContractService;

new class extends Component {

    public bool $open = false;
    public ?string $contractId = null;
    public ?Contract $contract = null;

    public string $earlyTerminationDate = '';
    public string $earlyTerminationReason = '';

    #[On('open-early-termination-modal')]
    public function openFor(string $contractId): void
    {
        $this->contract = Contract::findOrFail($contractId);
        $this->contractId = $contractId;
        $this->earlyTerminationDate = now()->format('Y-m-d');
        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize('earlyTerminate', $this->contract);

        $this->validate([
            'earlyTerminationDate'   => ['required', 'date'],
            'earlyTerminationReason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        app(ContractService::class)->earlyTerminate($this->contract, [
            'early_termination_date'   => $this->earlyTerminationDate,
            'early_termination_reason' => $this->earlyTerminationReason,
        ]);

        $this->reset(['open', 'contractId', 'contract', 'earlyTerminationDate', 'earlyTerminationReason']);
        $this->dispatch('contract-updated');
        session()->flash('success', 'Contrato terminado anticipadamente.');
    }

    public function cancel(): void
    {
        $this->reset(['open', 'contractId', 'contract', 'earlyTerminationDate', 'earlyTerminationReason']);
    }
};
?>

<div>
    @if($open)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-early-termination-title"
            wire:click.self="cancel"
        >
            <div class="w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800">

                {{-- Cabecera --}}
                <div class="flex items-start gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 id="modal-early-termination-title" class="text-base font-semibold text-gray-900 dark:text-white">
                            Terminar Contrato Anticipadamente
                        </h3>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ $this->contract?->contract_code ?? 'Contrato' }}
                        </p>
                    </div>
                    <button
                        wire:click="cancel"
                        type="button"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        aria-label="Cerrar modal">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Cuerpo --}}
                <div class="space-y-4 px-6 py-5">

                    {{-- Errores de validación --}}
                    @if($errors->any())
                        <div class="rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700 dark:text-red-300">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Alerta informativa --}}
                    <div class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 dark:border-yellow-800/50 dark:bg-yellow-900/20">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">
                            La fecha de finalización original del contrato <strong>no será modificada</strong>. Solo se registrará la terminación anticipada.
                        </p>
                    </div>

                    {{-- Fecha de terminación anticipada --}}
                    <div>
                        <label for="earlyTerminationDate" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha de terminación anticipada <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="earlyTerminationDate"
                            type="date"
                            wire:model="earlyTerminationDate"
                            max="{{ now()->format('Y-m-d') }}"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('earlyTerminationDate') border-red-500 dark:border-red-500 @enderror"
                        />
                        @error('earlyTerminationDate')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Motivo --}}
                    <div>
                        <label for="earlyTerminationReason" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Motivo de la terminación anticipada <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="earlyTerminationReason"
                            wire:model="earlyTerminationReason"
                            rows="4"
                            minlength="10"
                            maxlength="2000"
                            required
                            placeholder="Describa el motivo de la terminación anticipada (mínimo 10 caracteres)..."
                            class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 @error('earlyTerminationReason') border-red-500 dark:border-red-500 @enderror"
                        ></textarea>
                        @error('earlyTerminationReason')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-right text-xs text-gray-400 dark:text-gray-500">{{ mb_strlen($earlyTerminationReason) }}/2000</p>
                    </div>

                </div>

                {{-- Pie del modal --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button
                        wire:click="cancel"
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800">
                        Cancelar
                    </button>
                    <button
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-800">
                        <span wire:loading wire:target="save">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                        Terminar Contrato
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
