<?php

use App\Http\Requests\RH\ApplyPositionChangeRequest;
use App\Models\RH\Contract;
use App\Models\RH\Position;
use App\Services\RH\PositionChangeService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $open = false;

    public ?string $contractId = null;

    public ?Contract $contract = null;

    // Autocomplete del nuevo cargo
    public string $positionSearch = '';

    public bool $showPositionSuggestions = false;

    public ?array $selectedPosition = null;

    // Compensación
    public bool $adjustCompensation = false;

    public string $newAmount = '';

    public string $changeDate = '';

    public string $observations = '';

    #[On('open-position-change-modal')]
    public function openFor(string $contractId): void
    {
        $contract = Contract::with(['position', 'collaborator'])->findOrFail($contractId);

        $this->authorize('applyPositionChange', $contract);

        $this->resetForm();

        $this->contract     = $contract;
        $this->contractId   = $contract->id;
        $this->changeDate   = now()->format('Y-m-d');
        $this->open         = true;
    }

    public function updatedPositionSearch(): void
    {
        $this->showPositionSuggestions = mb_strlen($this->positionSearch) >= 1;
        $this->selectedPosition        = null;
    }

    public function selectPosition(string $id): void
    {
        if ($this->contract === null) {
            return;
        }

        $position = Position::query()
            ->where('institution_id', $this->contract->institution_id)
            ->find($id);

        if ($position === null) {
            // El id pertenece a otra institución o no existe: ignorar silenciosamente.
            // El guard real está en la regla `exists` con scope de institution_id en save().
            return;
        }

        $this->selectedPosition        = ['id' => $position->id, 'name' => $position->name];
        $this->positionSearch          = $position->name;
        $this->showPositionSuggestions = false;
    }

    public function clearPosition(): void
    {
        $this->selectedPosition        = null;
        $this->positionSearch          = '';
        $this->showPositionSuggestions = false;
    }

    public function getPositionSuggestionsProperty(): array
    {
        if (! $this->showPositionSuggestions || $this->contract === null) {
            return [];
        }

        $term           = $this->positionSearch;
        $institutionId  = $this->contract->institution_id;
        $excludeId      = (string) $this->contract->position_id;

        return Position::query()
            ->where('institution_id', $institutionId)
            ->where('id', '!=', $excludeId)
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'code'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code])
            ->toArray();
    }

    public function getIsFeesContractProperty(): bool
    {
        return $this->contract !== null && (float) ($this->contract->fees ?? 0) > 0;
    }

    public function getCurrentAmountProperty(): float
    {
        if ($this->contract === null) {
            return 0.0;
        }

        return $this->isFeesContract
            ? (float) ($this->contract->fees ?? 0)
            : (float) ($this->contract->salary ?? 0);
    }

    public function save(PositionChangeService $service): void
    {
        if ($this->contract === null) {
            return;
        }

        $this->authorize('applyPositionChange', $this->contract);

        $payload = [
            'new_position_id'     => $this->selectedPosition['id'] ?? null,
            'change_date'         => $this->changeDate,
            'adjust_compensation' => $this->adjustCompensation,
            'new_amount'          => $this->adjustCompensation && $this->newAmount !== ''
                ? (float) $this->newAmount
                : null,
            'observations'        => $this->observations !== '' ? $this->observations : null,
        ];

        $validator = validator(
            $payload,
            ApplyPositionChangeRequest::rulesFor($this->contract),
        );

        $validator->after(function ($v) use ($payload): void {
            if (($payload['adjust_compensation'] ?? false) && ($payload['new_amount'] === null || ! is_numeric($payload['new_amount']))) {
                $v->errors()->add('new_amount', 'Debe indicar el nuevo monto cuando ajusta la compensación.');
            }
        });

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $msg) {
                    $this->addError($this->mapErrorField($field), $msg);
                }
            }

            return;
        }

        $service->apply($this->contract, [
            'new_position_id'     => $payload['new_position_id'],
            'change_date'         => $payload['change_date'],
            'adjust_compensation' => $payload['adjust_compensation'],
            'new_amount'          => $payload['new_amount'],
            'observations'        => $payload['observations'],
        ]);

        session()->flash('success', 'Cambio de cargo registrado correctamente.');

        $this->close();
        $this->dispatch('position-change-applied');
        $this->dispatch('contract-updated');
    }

    public function close(): void
    {
        $this->resetForm();
        $this->open = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'contractId',
            'contract',
            'positionSearch',
            'showPositionSuggestions',
            'selectedPosition',
            'adjustCompensation',
            'newAmount',
            'changeDate',
            'observations',
        ]);
        $this->resetErrorBag();
    }

    private function mapErrorField(string $field): string
    {
        return match ($field) {
            'new_position_id'     => 'selectedPosition',
            'change_date'         => 'changeDate',
            'new_amount'          => 'newAmount',
            'adjust_compensation' => 'adjustCompensation',
            'observations'        => 'observations',
            default               => $field,
        };
    }
}; ?>

<div>
    @if($open && $contract !== null)
        <div
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-position-change-title"
            wire:click.self="close"
        >
            <div class="relative my-4 w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-800 sm:my-0">

                {{-- Cabecera --}}
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h3 id="modal-position-change-title" class="text-base font-semibold text-gray-900 dark:text-white">
                            Registrar cambio de cargo
                        </h3>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            {{ $contract->contract_code ?? 'Contrato' }} — {{ $contract->collaborator?->full_name }}
                        </p>
                    </div>
                    <button
                        wire:click="close"
                        type="button"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        aria-label="Cerrar modal">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Cuerpo --}}
                <div class="space-y-5 px-6 py-5">

                    {{-- Cargo actual --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-700/40">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Cargo actual</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $contract->position?->name ?? 'Sin cargo asignado' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->isFeesContract ? 'Honorarios' : 'Salario' }} actual:
                            <strong class="text-gray-700 dark:text-gray-200">$ {{ number_format($this->currentAmount, 0, ',', '.') }}</strong>
                        </p>
                    </div>

                    {{-- Errores generales --}}
                    @if($errors->any())
                        <div class="rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700 dark:text-red-300">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Autocomplete nuevo cargo --}}
                    <div>
                        <label for="positionSearch" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nuevo cargo <span class="text-red-500" aria-hidden="true">*</span>
                        </label>

                        @if($selectedPosition !== null)
                            <div class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-800/50 dark:bg-blue-900/20">
                                <div class="text-sm">
                                    <p class="font-medium text-blue-800 dark:text-blue-300">
                                        {{ $selectedPosition['name'] }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="clearPosition"
                                    class="text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-100"
                                    aria-label="Quitar cargo seleccionado">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <div class="relative">
                                <input
                                    id="positionSearch"
                                    type="text"
                                    wire:model.live.debounce.250ms="positionSearch"
                                    placeholder="Buscar cargo por nombre o código…"
                                    autocomplete="off"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 @error('selectedPosition') border-red-500 dark:border-red-500 @enderror"
                                />
                                @if(count($this->positionSuggestions) > 0)
                                    <ul class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        @foreach($this->positionSuggestions as $sug)
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="selectPosition('{{ $sug['id'] }}')"
                                                    class="flex w-full flex-col items-start px-3 py-2 text-left text-sm hover:bg-blue-50 dark:hover:bg-gray-700">
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ $sug['name'] }}</span>
                                                    @if(! empty($sug['code']))
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $sug['code'] }}</span>
                                                    @endif
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                        @error('selectedPosition') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fecha del cambio --}}
                    <div>
                        <label for="changeDate" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha del cambio <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="changeDate"
                            type="date"
                            wire:model="changeDate"
                            min="{{ $contract->start_date?->format('Y-m-d') }}"
                            max="{{ now()->format('Y-m-d') }}"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('changeDate') border-red-500 dark:border-red-500 @enderror"
                        />
                        @error('changeDate') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Checkbox ajuste de compensación --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                wire:model.live="adjustCompensation"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                            />
                            <span>
                                <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                                    Ajustar también {{ $this->isFeesContract ? 'honorarios' : 'salario' }}
                                </span>
                                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                                    Si lo activas, se actualizará también el contrato con el nuevo monto.
                                </span>
                            </span>
                        </label>

                        @if($adjustCompensation)
                            <div class="mt-3">
                                <label for="newAmount" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nuevo {{ $this->isFeesContract ? 'monto de honorarios' : 'salario' }} (COP)
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-gray-500 text-sm" aria-hidden="true">$</span>
                                    <input
                                        id="newAmount"
                                        type="number"
                                        wire:model="newAmount"
                                        min="0"
                                        step="1"
                                        placeholder="0"
                                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-7 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 @error('newAmount') border-red-500 dark:border-red-500 @enderror"
                                    />
                                </div>
                                @error('newAmount') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    {{-- Observaciones --}}
                    <div>
                        <label for="observations" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Motivo / observaciones
                        </label>
                        <textarea
                            id="observations"
                            wire:model="observations"
                            rows="3"
                            maxlength="1000"
                            placeholder="Ej: Ascenso por desempeño, reestructuración…"
                            class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 @error('observations') border-red-500 dark:border-red-500 @enderror"
                        ></textarea>
                        @error('observations') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        <p class="mt-1 text-right text-xs text-gray-400 dark:text-gray-500">{{ mb_strlen($observations) }}/1000</p>
                    </div>

                </div>

                {{-- Pie --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button
                        wire:click="close"
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800">
                        Cancelar
                    </button>
                    <button
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-800">
                        <span wire:loading wire:target="save">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                        Confirmar cambio
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
