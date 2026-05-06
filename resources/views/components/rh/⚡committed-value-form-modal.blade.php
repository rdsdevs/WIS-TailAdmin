<?php

use App\Models\Contabilidad\AccountingAccount;
use App\Models\Contabilidad\CostCenter;
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

    // Estado de cuenta contable
    public string $accountingSearch = '';

    public bool $showAccountSuggestions = false;

    public ?array $selectedAccount = null;

    // Estado de centro de costos
    public string $costCenterSearch = '';

    public bool $showCostCenterSuggestions = false;

    public ?array $selectedCostCenter = null;

    // Monto
    public string $amount = '';

    public function mount(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    // ── Apertura por evento ──────────────────────────────────────────────────

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
        $this->amount = (string) $cv->amount;

        // Precargar cuenta contable buscando coincidencia exacta por código
        $account = AccountingAccount::query()
            ->where('code', $cv->accounting_account)
            ->where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', $cv->institution_id))
            ->first();

        if ($account !== null) {
            $this->selectedAccount = ['id' => $account->id, 'code' => $account->code, 'name' => $account->name];
        } else {
            // Si la cuenta no existe en el catálogo (datos legacy), conservar como texto libre
            $this->selectedAccount = ['id' => null, 'code' => $cv->accounting_account, 'name' => '(catálogo no encontrado)'];
        }

        $costCenter = CostCenter::query()
            ->where('code', $cv->cost_center)
            ->where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', $cv->institution_id))
            ->first();

        if ($costCenter !== null) {
            $this->selectedCostCenter = ['id' => $costCenter->id, 'code' => $costCenter->code, 'name' => $costCenter->name];
        } else {
            $this->selectedCostCenter = ['id' => null, 'code' => $cv->cost_center, 'name' => '(catálogo no encontrado)'];
        }

        $this->open = true;
    }

    // ── Autocompletado: cuenta contable ──────────────────────────────────────

    public function updatedAccountingSearch(): void
    {
        $this->showAccountSuggestions = strlen($this->accountingSearch) >= 1;
        $this->selectedAccount = null;
    }

    public function selectAccount(string $id): void
    {
        $account = AccountingAccount::findOrFail($id);
        $this->selectedAccount = ['id' => $account->id, 'code' => $account->code, 'name' => $account->name];
        $this->accountingSearch = $account->code.' — '.$account->name;
        $this->showAccountSuggestions = false;
    }

    public function clearAccount(): void
    {
        $this->selectedAccount = null;
        $this->accountingSearch = '';
        $this->showAccountSuggestions = false;
    }

    public function getAccountSuggestionsProperty(): array
    {
        if (! $this->showAccountSuggestions || strlen($this->accountingSearch) < 1) {
            return [];
        }

        $institutionId = auth()->user()->institution_id;
        $term = $this->accountingSearch;

        return AccountingAccount::active()
            ->where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', $institutionId))
            ->where(fn ($q) => $q
                ->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
            )
            ->limit(8)
            ->get()
            ->map(fn ($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name])
            ->toArray();
    }

    // ── Autocompletado: centro de costos ─────────────────────────────────────

    public function updatedCostCenterSearch(): void
    {
        $this->showCostCenterSuggestions = strlen($this->costCenterSearch) >= 1;
        $this->selectedCostCenter = null;
    }

    public function selectCostCenter(string $id): void
    {
        $cc = CostCenter::findOrFail($id);
        $this->selectedCostCenter = ['id' => $cc->id, 'code' => $cc->code, 'name' => $cc->name];
        $this->costCenterSearch = $cc->code.' — '.$cc->name;
        $this->showCostCenterSuggestions = false;
    }

    public function clearCostCenter(): void
    {
        $this->selectedCostCenter = null;
        $this->costCenterSearch = '';
        $this->showCostCenterSuggestions = false;
    }

    public function getCostCenterSuggestionsProperty(): array
    {
        if (! $this->showCostCenterSuggestions || strlen($this->costCenterSearch) < 1) {
            return [];
        }

        $institutionId = auth()->user()->institution_id;
        $term = $this->costCenterSearch;

        return CostCenter::active()
            ->where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', $institutionId))
            ->where(fn ($q) => $q
                ->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
            )
            ->limit(8)
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name])
            ->toArray();
    }

    // ── Computed: contrato y derivados ───────────────────────────────────────

    public function getContractProperty(): ?Contract
    {
        if ($this->contractId === '') {
            return null;
        }

        return Contract::with('collaborator')->find($this->contractId);
    }

    public function getContractValueProperty(): float
    {
        $contract = $this->contract;

        if ($contract === null) {
            return 0.0;
        }

        $type = $contract->collaborator?->type ?? 'Empleado';

        return $type === 'Contratista'
            ? (float) ($contract->fees ?? 0)
            : (float) ($contract->salary ?? 0);
    }

    public function getTotalCommittedProperty(): float
    {
        if ($this->contractId === '') {
            return 0.0;
        }

        $existingTotal = (float) CommittedValue::query()
            ->where('contract_id', $this->contractId)
            ->when($this->committedValueId !== null, fn ($q) => $q->where('id', '!=', $this->committedValueId))
            ->sum('amount');

        $currentAmount = is_numeric($this->amount) ? (float) $this->amount : 0.0;

        return $existingTotal + $currentAmount;
    }

    public function getCommittedDifferenceProperty(): float
    {
        return $this->contractValue - $this->totalCommitted;
    }

    // ── Persistencia ─────────────────────────────────────────────────────────

    public function save(CommittedValueService $service): void
    {
        $this->validate([
            'selectedAccount' => ['required', 'array'],
            'selectedAccount.code' => ['required', 'string', 'max:200'],
            'selectedCostCenter' => ['required', 'array'],
            'selectedCostCenter.code' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ], [
            'selectedAccount.required' => 'Seleccione una cuenta contable.',
            'selectedAccount.code.required' => 'Seleccione una cuenta contable.',
            'selectedCostCenter.required' => 'Seleccione un centro de costos.',
            'selectedCostCenter.code.required' => 'Seleccione un centro de costos.',
            'amount.required' => 'El valor es obligatorio.',
            'amount.numeric' => 'El valor debe ser numérico.',
            'amount.min' => 'El valor no puede ser negativo.',
            'amount.max' => 'El valor excede el monto máximo permitido.',
        ]);

        $payload = [
            'accounting_account' => $this->selectedAccount['code'],
            'cost_center' => $this->selectedCostCenter['code'],
            'amount' => (float) $this->amount,
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
        $this->reset([
            'committedValueId',
            'accountingSearch',
            'showAccountSuggestions',
            'selectedAccount',
            'costCenterSearch',
            'showCostCenterSuggestions',
            'selectedCostCenter',
            'amount',
        ]);
        $this->resetErrorBag();
    }
}; ?>

<div>
    @if($open)
        @php
            $contract = $this->contract;
            $contractValue = $this->contractValue;
            $totalCommitted = $this->totalCommitted;
            $diff = $this->committedDifference;

            if ($totalCommitted == 0) {
                $summaryColor = 'blue';
            } elseif (abs($diff) < 0.01) {
                $summaryColor = 'green';
            } elseif ($diff > 0) {
                $summaryColor = 'yellow';
            } else {
                $summaryColor = 'red';
            }
        @endphp

        <div
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="committed-value-modal-title"
            wire:click.self="close">
            <div class="relative my-4 w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-gray-800 sm:my-0">
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
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Información contable</h4>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Seleccione la cuenta contable y el centro de costos del catálogo institucional.
                        </p>
                    </div>

                    {{-- Valor del contrato (display) --}}
                    @if($contract)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Valor del contrato</p>
                            <p class="mt-0.5 text-xl font-semibold text-gray-900 dark:text-white">
                                $ {{ number_format($contractValue, 0, ',', '.') }}
                            </p>
                            @if($contract->collaborator)
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    {{ ($contract->collaborator->type ?? 'Empleado') === 'Contratista' ? 'Honorarios' : 'Salario' }}
                                    del colaborador {{ $contract->collaborator->full_name }}
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Cuenta contable con autocomplete --}}
                    <div>
                        <label for="cv_accounting_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Cuenta contable <span class="text-red-500" aria-hidden="true">*</span>
                        </label>

                        @if($selectedAccount)
                            <div class="mt-1.5 flex items-center gap-2.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-2.5 dark:border-blue-700 dark:bg-blue-900/20">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <span class="shrink-0 font-mono text-sm font-semibold text-blue-700 dark:text-blue-300">{{ $selectedAccount['code'] }}</span>
                                    <span class="truncate text-sm text-gray-600 dark:text-gray-400">{{ $selectedAccount['name'] }}</span>
                                </div>
                                <button
                                    type="button"
                                    wire:click="clearAccount"
                                    class="shrink-0 rounded-md p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    aria-label="Cambiar cuenta contable">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <div class="relative mt-1.5">
                                <input
                                    wire:model.live.debounce.300ms="accountingSearch"
                                    id="cv_accounting_search"
                                    type="text"
                                    placeholder="Buscar por código o nombre de cuenta..."
                                    autocomplete="off"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                                />

                                @if($showAccountSuggestions && count($this->accountSuggestions) > 0)
                                    <ul class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                        role="listbox"
                                        aria-label="Resultados de búsqueda de cuentas contables">
                                        @foreach($this->accountSuggestions as $acc)
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="selectAccount('{{ $acc['id'] }}')"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    role="option">
                                                    <span class="shrink-0 font-mono font-semibold text-gray-900 dark:text-white">{{ $acc['code'] }}</span>
                                                    <span class="truncate text-gray-500 dark:text-gray-400">{{ $acc['name'] }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif($showAccountSuggestions && strlen($accountingSearch) >= 1)
                                    <div class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-500 shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                        No se encontraron cuentas contables.
                                    </div>
                                @endif
                            </div>
                        @endif

                        @error('selectedAccount')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                        @error('selectedAccount.code')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Centro de costos con autocomplete --}}
                    <div>
                        <label for="cv_cost_center_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Centro de costos <span class="text-red-500" aria-hidden="true">*</span>
                        </label>

                        @if($selectedCostCenter)
                            <div class="mt-1.5 flex items-center gap-2.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-2.5 dark:border-blue-700 dark:bg-blue-900/20">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <span class="shrink-0 font-mono text-sm font-semibold text-blue-700 dark:text-blue-300">{{ $selectedCostCenter['code'] }}</span>
                                    <span class="truncate text-sm text-gray-600 dark:text-gray-400">{{ $selectedCostCenter['name'] }}</span>
                                </div>
                                <button
                                    type="button"
                                    wire:click="clearCostCenter"
                                    class="shrink-0 rounded-md p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    aria-label="Cambiar centro de costos">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <div class="relative mt-1.5">
                                <input
                                    wire:model.live.debounce.300ms="costCenterSearch"
                                    id="cv_cost_center_search"
                                    type="text"
                                    placeholder="Buscar centro de costos por código o nombre..."
                                    autocomplete="off"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                                />

                                @if($showCostCenterSuggestions && count($this->costCenterSuggestions) > 0)
                                    <ul class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                        role="listbox"
                                        aria-label="Resultados de búsqueda de centros de costos">
                                        @foreach($this->costCenterSuggestions as $cc)
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="selectCostCenter('{{ $cc['id'] }}')"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    role="option">
                                                    <span class="shrink-0 font-mono font-semibold text-gray-900 dark:text-white">{{ $cc['code'] }}</span>
                                                    <span class="truncate text-gray-500 dark:text-gray-400">{{ $cc['name'] }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif($showCostCenterSuggestions && strlen($costCenterSearch) >= 1)
                                    <div class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-500 shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                        No se encontraron centros de costos.
                                    </div>
                                @endif
                            </div>
                        @endif

                        @error('selectedCostCenter')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                        @error('selectedCostCenter.code')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Monto --}}
                    <div>
                        <label for="cv_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Valor (COP) <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-400 dark:text-gray-500" aria-hidden="true">$</span>
                            <input
                                id="cv_amount"
                                type="number"
                                step="1000"
                                min="0"
                                wire:model.live="amount"
                                placeholder="0"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-7 pr-3 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('amount') border-red-500 @enderror"
                            />
                        </div>
                        @error('amount')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Resumen valor vs comprometido --}}
                    @if($contract && $contractValue > 0)
                        <div class="rounded-lg border px-4 py-3
                            {{ $summaryColor === 'green'  ? 'border-green-200 bg-green-50 dark:border-green-800/50 dark:bg-green-900/20' : '' }}
                            {{ $summaryColor === 'red'    ? 'border-red-200 bg-red-50 dark:border-red-800/50 dark:bg-red-900/20' : '' }}
                            {{ $summaryColor === 'yellow' ? 'border-yellow-200 bg-yellow-50 dark:border-yellow-800/50 dark:bg-yellow-900/20' : '' }}
                            {{ $summaryColor === 'blue'   ? 'border-blue-200 bg-blue-50 dark:border-blue-800/50 dark:bg-blue-900/20' : '' }}">

                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <div class="text-center">
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Valor contrato</p>
                                    <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">$ {{ number_format($contractValue, 0, ',', '.') }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total comprometido</p>
                                    <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">$ {{ number_format($totalCommitted, 0, ',', '.') }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $diff > 0 ? 'Por comprometer' : ($diff < 0 ? 'Excedente' : 'Diferencia') }}
                                    </p>
                                    <p class="mt-0.5 font-semibold
                                        {{ $summaryColor === 'green'  ? 'text-green-700 dark:text-green-400'   : '' }}
                                        {{ $summaryColor === 'red'    ? 'text-red-700 dark:text-red-400'       : '' }}
                                        {{ $summaryColor === 'yellow' ? 'text-yellow-700 dark:text-yellow-400' : '' }}
                                        {{ $summaryColor === 'blue'   ? 'text-blue-700 dark:text-blue-400'     : '' }}">
                                        $ {{ number_format(abs($diff), 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>

                            @if($summaryColor === 'red')
                                <p class="mt-2 text-center text-xs text-red-700 dark:text-red-400">
                                    El total comprometido excede el valor del contrato.
                                </p>
                            @elseif($summaryColor === 'yellow')
                                <p class="mt-2 text-center text-xs text-yellow-700 dark:text-yellow-400">
                                    Aún quedan $ {{ number_format($diff, 0, ',', '.') }} por comprometer.
                                </p>
                            @elseif($summaryColor === 'green')
                                <p class="mt-2 text-center text-xs text-green-700 dark:text-green-400">
                                    El total comprometido cuadra con el valor del contrato.
                                </p>
                            @endif
                        </div>
                    @endif
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
