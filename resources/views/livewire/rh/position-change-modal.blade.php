<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use App\Models\RH\Contract;
use App\Models\RH\Position;
use App\Models\RH\PositionChangeHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

new class extends Component {
    public ?string $contractId = null;
    public bool $isOpen = false;

    public string $newPositionId = '';
    public string $newSalary = '';
    public string $changeDate = '';
    public string $reason = '';

    public function mount(): void
    {
        $this->changeDate = now()->format('Y-m-d');
    }

    #[Livewire\Attributes\On('open-position-change-modal')]
    public function open(string $contractId): void
    {
        $this->contractId = $contractId;
        $contract = Contract::findOrFail($contractId);
        
        $this->newPositionId = (string) $contract->position_id;
        $this->newSalary = (string) $contract->salary;
        $this->isOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'newPositionId' => 'required|uuid|exists:positions,id',
            'newSalary' => 'required|numeric|min:0',
            'changeDate' => 'required|date',
            'reason' => 'required|string|max:500',
        ]);

        $contract = Contract::findOrFail($this->contractId);

        Gate::authorize('update', $contract);

        DB::transaction(function () use ($contract) {
            // Registrar en el historial
            PositionChangeHistory::create([
                'contract_id'         => $contract->id,
                'previous_position_id' => $contract->position_id,
                'new_position_id'     => $this->newPositionId,
                'old_salary'          => $contract->salary,
                'new_salary'          => $this->newSalary,
                'change_date'         => $this->changeDate,
                'observations'        => $this->reason,
            ]);

            // Actualizar el contrato vigente
            $contract->update([
                'position_id' => $this->newPositionId,
                'salary' => $this->newSalary,
            ]);
        });

        $this->isOpen = false;
        $this->dispatch('notify', type: 'success', message: 'Cambio de cargo registrado correctamente.');
        $this->dispatch('contract-updated'); // Para que otros componentes se enteren
        $this->redirect(route('rh.colaboradores.show', $contract->collaborator_id), navigate: false);
    }
};
?>

<div>
    @if($isOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('isOpen', false)"></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                        Registrar Cambio de Cargo / Ascenso
                    </h3>
                    <button wire:click="$set('isOpen', false)" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nuevo Cargo *</label>
                        <select wire:model="newPositionId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Seleccione el nuevo cargo...</option>
                            @foreach(App\Models\RH\Position::active()->orderBy('name')->get() as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                            @endforeach
                        </select>
                        @error('newPositionId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nuevo Salario / Honorarios *</label>
                        <input wire:model="newSalary" type="number" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('newSalary') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha del Cambio *</label>
                        <input wire:model="changeDate" type="date" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('changeDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo del Cambio *</label>
                        <textarea wire:model="reason" rows="3" placeholder="Ej: Ascenso por desempeño, Reestructuración..."
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        @error('reason') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                    <button wire:click="$set('isOpen', false)" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button wire:click="save" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Confirmar Cambio
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
