<?php

use Livewire\Component;
use App\Models\RH\Employee;
use App\Models\RH\Contractor;
use App\Models\RH\Contract;

new class extends Component {

    public string $contractable_type = 'employee';
    public string $contractable_id = '';
    public string $contract_type = 'indefinite';
    public string $start_date = '';
    public ?string $end_date = null;
    public string $salary = '';
    public string $position_name = '';
    public string $description = '';

    protected function rules(): array
    {
        return [
            'contractable_type' => 'required|in:employee,contractor',
            'contractable_id'   => 'required|uuid',
            'contract_type'     => 'required|in:indefinite,fixed_term,contractor,intern',
            'start_date'        => 'required|date',
            'end_date'          => $this->contract_type === 'fixed_term'
                                       ? 'required|date|after:start_date'
                                       : 'nullable|date|after:start_date',
            'salary'            => 'required|numeric|min:0',
            'position_name'     => 'required|string|max:200',
            'description'       => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'contractable_id.required'  => 'Debe seleccionar una persona.',
            'contract_type.required'    => 'Seleccione el tipo de contrato.',
            'start_date.required'       => 'La fecha de inicio es obligatoria.',
            'end_date.required'         => 'La fecha de fin es obligatoria para contratos a término fijo.',
            'end_date.after'            => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'salary.required'           => 'El salario es obligatorio.',
            'salary.min'                => 'El salario no puede ser negativo.',
            'position_name.required'    => 'El cargo en el contrato es obligatorio.',
        ];
    }

    public function updatedContractableType(): void
    {
        $this->contractable_id = '';
    }

    public function updatedContractType(): void
    {
        if ($this->contract_type !== 'fixed_term') {
            $this->end_date = null;
        }
    }

    public function save(): void
    {
        $this->authorize('create', \App\Models\RH\Contract::class);

        $datos = $this->validate();

        $contractableClass = $datos['contractable_type'] === 'employee'
            ? \App\Models\RH\Employee::class
            : \App\Models\RH\Contractor::class;

        Contract::create([
            'contractable_type' => $contractableClass,
            'contractable_id'   => $datos['contractable_id'],
            'contract_type'     => $datos['contract_type'],
            'start_date'        => $datos['start_date'],
            'end_date'          => $datos['end_date'] ?? null,
            'salary'            => $datos['salary'],
            'position'          => $datos['position_name'],
            'description'       => $datos['description'] ?? null,
            'is_active'         => true,
            'institution_id'    => auth()->user()->institution_id,
        ]);

        $this->dispatch('contrato-guardado');
        session()->flash('exito', 'Contrato registrado correctamente.');
        $this->redirect(route('rh.contratos.index'));
    }

    public function render(): \Illuminate\View\View
    {
        $empleados = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $contratistas = Contractor::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'company_name']);

        return view('livewire.rh.contract-form', compact('empleados', 'contratistas'));
    }
};
?>

<div>
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Datos del contrato</h3>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Complete la información del nuevo contrato.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">

            {{-- Tipo de persona --}}
            <div class="space-y-1.5">
                <label for="contractable_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tipo de persona <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select
                    id="contractable_type"
                    wire:model.live="contractable_type"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="employee">Empleado</option>
                    <option value="contractor">Contratista</option>
                </select>
            </div>

            {{-- Persona --}}
            <div class="space-y-1.5">
                <label for="contractable_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Persona <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select
                    id="contractable_id"
                    wire:model="contractable_id"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                           @error('contractable_id') border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700 @enderror
                           text-gray-900 dark:text-white"
                >
                    <option value="">Seleccione la persona</option>
                    @if($contractable_type === 'employee')
                        @foreach($empleados as $empleado)
                            <option value="{{ $empleado->id }}">{{ $empleado->full_name }}</option>
                        @endforeach
                    @else
                        @foreach($contratistas as $contratista)
                            <option value="{{ $contratista->id }}">{{ $contratista->full_name }}</option>
                        @endforeach
                    @endif
                </select>
                @error('contractable_id')
                    <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipo de contrato --}}
            <div class="space-y-1.5">
                <label for="contract_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tipo de contrato <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select
                    id="contract_type"
                    wire:model.live="contract_type"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="indefinite">Término indefinido</option>
                    <option value="fixed_term">Término fijo</option>
                    <option value="contractor">Contratista (prestación de servicios)</option>
                    <option value="intern">Practicante</option>
                </select>
            </div>

            {{-- Cargo en el contrato --}}
            <div class="space-y-1.5">
                <label for="position_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Cargo en el contrato <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="position_name"
                    wire:model.blur="position_name"
                    placeholder="Ej: Analista de sistemas"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                           @error('position_name') border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700 @enderror
                           text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500"
                />
                @error('position_name')
                    <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Fecha de inicio --}}
            <div class="space-y-1.5">
                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Fecha de inicio <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    type="date"
                    id="start_date"
                    wire:model.blur="start_date"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                           @error('start_date') border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700 @enderror
                           text-gray-900 dark:text-white"
                />
                @error('start_date')
                    <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Fecha de fin (solo término fijo) --}}
            <div class="space-y-1.5" x-data x-show="$wire.contract_type === 'fixed_term'" x-cloak>
                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Fecha de fin <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    type="date"
                    id="end_date"
                    wire:model.blur="end_date"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                           @error('end_date') border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700 @enderror
                           text-gray-900 dark:text-white"
                />
                @error('end_date')
                    <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Salario --}}
            <div class="space-y-1.5">
                <label for="salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Salario (COP) <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-500 dark:text-gray-400">$</span>
                    <input
                        type="number"
                        id="salary"
                        wire:model.blur="salary"
                        placeholder="0"
                        min="0"
                        step="1000"
                        class="w-full rounded-lg border py-2.5 pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               @error('salary') border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700 @enderror
                               text-gray-900 dark:text-white"
                    />
                </div>
                @error('salary')
                    <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Descripción --}}
            <div class="space-y-1.5 md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Descripción
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(opcional)</span>
                </label>
                <textarea
                    id="description"
                    wire:model="description"
                    rows="3"
                    placeholder="Descripción u observaciones del contrato..."
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                ></textarea>
            </div>

        </div>
    </div>

    {{-- Botones --}}
    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('rh.contratos.index') }}"
           class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
            Cancelar
        </a>
        <button
            wire:click="save"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-900">
            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Guardar contrato
        </button>
    </div>
</div>
