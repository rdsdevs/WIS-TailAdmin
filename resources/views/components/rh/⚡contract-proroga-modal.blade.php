<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\RH\Contract;
use App\Models\RH\ContractExtension;
use App\Services\RH\ContractProrogaService;
use Illuminate\Support\Collection;

new class extends Component {

    public bool $open = false;
    public ?string $contractId = null;
    public ?Contract $contract = null;

    // Formulario
    public string $extensionType = 'tiempo';
    public string $extensionMonths = '';
    public string $extensionDays = '';
    public string $extensionValue = '';
    public ?string $committedValueId = null;
    public string $approvalDate = '';
    public string $reason = '';

    // Estado derivado
    public bool $needsCostCenterSelection = false;
    public array $committedValueOptions = [];

    #[On('open-proroga-modal')]
    public function openFor(string $contractId): void
    {
        $this->contract = Contract::with(['committedValues'])->findOrFail($contractId);
        $this->contractId = $contractId;
        $service = app(ContractProrogaService::class);
        $this->needsCostCenterSelection = $service->needsCostCenterSelection($this->contract);
        if ($this->needsCostCenterSelection) {
            $this->committedValueOptions = $service->getCommittedValueOptions($this->contract)
                ->map(fn($cv) => ['id' => $cv->id, 'label' => $cv->cost_center . ' — $' . number_format($cv->amount, 0, ',', '.')])
                ->toArray();
        }
        $this->approvalDate = now()->format('Y-m-d');
        $this->open = true;
    }

    public function updatedExtensionType(): void
    {
        if ($this->extensionType === 'tiempo') {
            $this->extensionValue = '';
            $this->committedValueId = null;
        } elseif ($this->extensionType === 'valor') {
            $this->extensionMonths = '';
            $this->extensionDays = '';
        }
    }

    public function save(): void
    {
        $this->authorize('applyProroga', $this->contract);

        $this->validate([
            'extensionType'    => ['required', 'in:tiempo,valor,tiempo_y_valor'],
            'extensionMonths'  => ['nullable', 'integer', 'min:0', 'max:120'],
            'extensionDays'    => ['nullable', 'integer', 'min:0', 'max:365'],
            'extensionValue'   => ['nullable', 'numeric', 'min:0'],
            'committedValueId' => ['nullable', 'uuid'],
            'approvalDate'     => ['required', 'date'],
            'reason'           => ['nullable', 'string', 'max:1000'],
        ]);

        $data = [
            'extension_type'     => $this->extensionType,
            'extension_months'   => $this->extensionMonths !== '' ? (int) $this->extensionMonths : null,
            'extension_days'     => $this->extensionDays !== '' ? (int) $this->extensionDays : null,
            'extension_value'    => $this->extensionValue !== '' ? (float) $this->extensionValue : null,
            'committed_value_id' => $this->committedValueId,
            'approval_date'      => $this->approvalDate,
            'reason'             => $this->reason ?: null,
            'institution_id'     => $this->contract->institution_id,
        ];

        app(ContractProrogaService::class)->apply($this->contract, $data);

        $this->reset(['open', 'contractId', 'contract', 'extensionType', 'extensionMonths', 'extensionDays', 'extensionValue', 'committedValueId', 'approvalDate', 'reason', 'needsCostCenterSelection', 'committedValueOptions']);
        $this->extensionType = 'tiempo';

        $this->dispatch('contract-updated');
        session()->flash('success', 'Prórroga aplicada correctamente al contrato.');
    }

    public function cancel(): void
    {
        $this->reset(['open', 'contractId', 'contract', 'extensionType', 'extensionMonths', 'extensionDays', 'extensionValue', 'committedValueId', 'approvalDate', 'reason', 'needsCostCenterSelection', 'committedValueOptions']);
        $this->extensionType = 'tiempo';
    }
};
?>

<div>
    @if($open)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-proroga-title"
            wire:click.self="cancel"
        >
            <div class="relative my-4 w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-800 sm:my-0">

                {{-- Cabecera --}}
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h3 id="modal-proroga-title" class="text-base font-semibold text-gray-900 dark:text-white">
                            Aplicar Prórroga al Contrato
                        </h3>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
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
                <div class="space-y-5 px-6 py-5">

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

                    {{-- Tipo de prórroga --}}
                    <fieldset>
                        <legend class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tipo de prórroga <span class="text-red-500" aria-hidden="true">*</span>
                        </legend>
                        <div class="flex flex-wrap gap-3">
                            @foreach([
                                'tiempo'        => 'En tiempo',
                                'valor'         => 'En valor',
                                'tiempo_y_valor'=> 'En tiempo y valor',
                            ] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors
                                    {{ $extensionType === $value
                                        ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-500 dark:bg-blue-900/20 dark:text-blue-300'
                                        : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-gray-500'
                                    }}">
                                    <input
                                        type="radio"
                                        wire:model.live="extensionType"
                                        value="{{ $value }}"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                                    />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Campos de tiempo --}}
                    @if(in_array($extensionType, ['tiempo', 'tiempo_y_valor']))
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="extensionMonths" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Meses de prórroga
                                </label>
                                <input
                                    id="extensionMonths"
                                    type="number"
                                    wire:model="extensionMonths"
                                    min="0"
                                    max="120"
                                    placeholder="0"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 @error('extensionMonths') border-red-500 dark:border-red-500 @enderror"
                                />
                                @error('extensionMonths')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="extensionDays" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Días adicionales
                                </label>
                                <input
                                    id="extensionDays"
                                    type="number"
                                    wire:model="extensionDays"
                                    min="0"
                                    max="365"
                                    placeholder="0"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 @error('extensionDays') border-red-500 dark:border-red-500 @enderror"
                                />
                                @error('extensionDays')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Preview nueva fecha --}}
                        @if($this->contract?->end_date && ($extensionMonths !== '' || $extensionDays !== ''))
                            @php
                                $previewDate = $this->contract->end_date
                                    ->copy()
                                    ->addMonths((int)($extensionMonths ?: 0))
                                    ->addDays((int)($extensionDays ?: 0));
                            @endphp
                            <div class="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-800/50 dark:bg-blue-900/20">
                                <svg class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    Nueva fecha estimada de fin: <strong>{{ $previewDate->format('d/m/Y') }}</strong>
                                </p>
                            </div>
                        @endif
                    @endif

                    {{-- Campos de valor --}}
                    @if(in_array($extensionType, ['valor', 'tiempo_y_valor']))
                        <div>
                            <label for="extensionValue" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Valor de la prórroga (COP)
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-gray-500 text-sm" aria-hidden="true">$</span>
                                <input
                                    id="extensionValue"
                                    type="number"
                                    wire:model="extensionValue"
                                    min="0"
                                    placeholder="0"
                                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-7 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 @error('extensionValue') border-red-500 dark:border-red-500 @enderror"
                                />
                            </div>
                            @error('extensionValue')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                Este valor se sumará al total del contrato.
                            </p>
                        </div>

                        {{-- Selector de centro de costos --}}
                        @if($needsCostCenterSelection)
                            <div>
                                <label for="committedValueId" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Centro de costos a incrementar
                                </label>
                                <div class="mb-2 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-800/50 dark:bg-amber-900/20">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                    <p class="text-xs text-amber-700 dark:text-amber-300">
                                        Este contrato tiene múltiples líneas de valor comprometido. Seleccione a cuál se aplicará la prórroga.
                                    </p>
                                </div>
                                <select
                                    id="committedValueId"
                                    wire:model="committedValueId"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('committedValueId') border-red-500 dark:border-red-500 @enderror"
                                >
                                    <option value="">Seleccione un centro de costos...</option>
                                    @foreach($committedValueOptions as $option)
                                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('committedValueId')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    @endif

                    {{-- Fecha de aprobación --}}
                    <div>
                        <label for="approvalDate" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha de aprobación <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="approvalDate"
                            type="date"
                            wire:model="approvalDate"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('approvalDate') border-red-500 dark:border-red-500 @enderror"
                        />
                        @error('approvalDate')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Observaciones --}}
                    <div>
                        <label for="prorrogaReason" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Observaciones
                        </label>
                        <textarea
                            id="prorrogaReason"
                            wire:model="reason"
                            rows="3"
                            maxlength="1000"
                            placeholder="Motivo de la prórroga (opcional)"
                            class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 @error('reason') border-red-500 dark:border-red-500 @enderror"
                        ></textarea>
                        @error('reason')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-right text-xs text-gray-400 dark:text-gray-500">{{ strlen($reason) }}/1000</p>
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
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-800">
                        <span wire:loading wire:target="save">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                        Aplicar Prórroga
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
