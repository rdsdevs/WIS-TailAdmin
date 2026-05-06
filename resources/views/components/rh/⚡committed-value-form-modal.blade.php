<?php

use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use App\Services\RH\CommittedValueService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public string $contractId = '';

    public bool $open = false;

    public ?string $committedValueId = null;

    public string $accountingAccount = '';

    public string $costCenter = '';

    public string $amount = '';

    public function mount(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    #[On('open-committed-value-create')]
    public function openForCreate(string $contractId): void
    {
        if ($contractId !== $this->contractId) {
            return;
        }

        $contract = Contract::findOrFail($contractId);
        $this->authorize('create', [CommittedValue::class, $contract]);

        $this->resetForm();
        $this->open = true;
    }

    #[On('open-committed-value-edit')]
    public function openForEdit(string $committedValueId): void
    {
        $cv = CommittedValue::findOrFail($committedValueId);

        if ($cv->contract_id !== $this->contractId) {
            return;
        }

        $this->authorize('update', $cv);

        $this->committedValueId = $cv->id;
        $this->accountingAccount = $cv->accounting_account;
        $this->costCenter = $cv->cost_center;
        $this->amount = (string) $cv->amount;
        $this->open = true;
    }

    public function save(CommittedValueService $service): void
    {
        $data = $this->validate([
            'accountingAccount' => ['required', 'string', 'max:200'],
            'costCenter' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ], [
            'accountingAccount.required' => 'La cuenta contable es obligatoria.',
            'accountingAccount.max' => 'La cuenta contable no puede tener más de :max caracteres.',
            'costCenter.required' => 'El centro de costo es obligatorio.',
            'costCenter.max' => 'El centro de costo no puede tener más de :max caracteres.',
            'amount.required' => 'El valor es obligatorio.',
            'amount.numeric' => 'El valor debe ser numérico.',
            'amount.min' => 'El valor no puede ser negativo.',
            'amount.max' => 'El valor excede el monto máximo permitido.',
        ]);

        $payload = [
            'accounting_account' => $data['accountingAccount'],
            'cost_center' => $data['costCenter'],
            'amount' => (float) $data['amount'],
        ];

        if ($this->committedValueId === null) {
            $contract = Contract::findOrFail($this->contractId);
            $this->authorize('create', [CommittedValue::class, $contract]);
            $service->create($contract, $payload);
            session()->flash('exito', 'Valor comprometido registrado correctamente.');
        } else {
            $cv = CommittedValue::findOrFail($this->committedValueId);
            $this->authorize('update', $cv);
            $service->update($cv, $payload);
            session()->flash('exito', 'Valor comprometido actualizado correctamente.');
        }

        $this->close();
        $this->dispatch('committed-value-saved');
        $this->redirectRoute('rh.contratos.valores-comprometidos.index', ['contrato' => $this->contractId], navigate: true);
    }

    public function close(): void
    {
        $this->resetForm();
        $this->open = false;
    }

    private function resetForm(): void
    {
        $this->reset(['committedValueId', 'accountingAccount', 'costCenter', 'amount']);
        $this->resetErrorBag();
    }
}; ?>

<div>
    @if($open)
        <div
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="committed-value-modal-title"
            wire:click.self="close">
            <div class="relative my-4 w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800 sm:my-0">
                {{-- Cabecera --}}
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 id="committed-value-modal-title" class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $committedValueId ? 'Editar valor comprometido' : 'Registrar valor comprometido' }}
                    </h3>
                    <button
                        type="button"
                        wire:click="close"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        aria-label="Cerrar modal">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Cuerpo --}}
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="cv_accounting_account" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Cuenta contable <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="cv_accounting_account"
                            type="text"
                            wire:model="accountingAccount"
                            maxlength="200"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('accountingAccount') border-red-500 @enderror"
                        />
                        @error('accountingAccount')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cv_cost_center" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Centro de costo <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="cv_cost_center"
                            type="text"
                            wire:model="costCenter"
                            maxlength="200"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('costCenter') border-red-500 @enderror"
                        />
                        @error('costCenter')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cv_amount" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Valor (COP) <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-400 dark:text-gray-500" aria-hidden="true">$</span>
                            <input
                                id="cv_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model="amount"
                                placeholder="0"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-7 pr-3 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('amount') border-red-500 @enderror"
                            />
                        </div>
                        @error('amount')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
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
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-800">
                        <span wire:loading wire:target="save">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
