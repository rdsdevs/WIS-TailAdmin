<?php

use Livewire\Component;
use App\Models\RH\Contract;
use App\Models\RH\Collaborator;
use App\Models\RH\ContractType;
use App\Models\RH\Position;

new class extends Component {

    // ── Props ─────────────────────────────────────────────────────────────────
    public ?string $contractId      = null;
    public ?string $collaboratorId  = null;

    // ── Búsqueda de colaborador ───────────────────────────────────────────────
    public string $collaboratorSearch    = '';
    public ?array $selectedCollaborator  = null;
    public bool   $showSuggestions       = false;

    // ── Campos del contrato ───────────────────────────────────────────────────
    public string  $contractTypeId = '';
    public string  $positionId     = '';
    public string  $contractNumber = '';
    public string  $contractCode   = '';
    public string  $startDate      = '';
    public ?string $endDate        = null;
    public string  $salary         = '';
    public string  $fees           = '';
    public string  $object         = '';
    public string  $obligations    = '';
    public string  $positionEmail  = '';
    public string  $status         = 'Vigente';

    public function mount(?string $contractId = null, ?string $collaboratorId = null): void
    {
        $this->contractId     = $contractId;
        $this->collaboratorId = $collaboratorId;

        if ($contractId) {
            $c = Contract::with('collaborator')->findOrFail($contractId);
            $this->collaboratorId   = (string) $c->collaborator_id;
            $this->contractTypeId   = (string) $c->contract_type_id;
            $this->positionId       = (string) ($c->position_id ?? '');
            $this->contractNumber   = $c->contract_number ?? '';
            $this->contractCode     = $c->contract_code ?? '';
            $this->startDate        = $c->start_date?->format('Y-m-d') ?? '';
            $this->endDate          = $c->end_date?->format('Y-m-d');
            $this->salary           = (string) ($c->salary ?? '');
            $this->fees             = (string) ($c->fees ?? '');
            $this->object           = $c->object ?? '';
            $this->obligations      = $c->obligations ?? '';
            $this->positionEmail    = $c->position_email ?? '';
            $this->status           = $c->status ?? 'Vigente';

            if ($c->collaborator) {
                $this->selectedCollaborator = [
                    'id'   => $c->collaborator->id,
                    'name' => $c->collaborator->full_name,
                    'doc'  => $c->collaborator->document_number,
                    'type' => $c->collaborator->type,
                ];
                $this->collaboratorSearch = $c->collaborator->full_name;
            }
        } elseif ($collaboratorId) {
            $collab = Collaborator::find($collaboratorId);
            if ($collab) {
                $this->selectedCollaborator = [
                    'id'   => $collab->id,
                    'name' => $collab->full_name,
                    'doc'  => $collab->document_number,
                    'type' => $collab->type,
                ];
                $this->collaboratorSearch = $collab->full_name;
            }
        }
    }

    public function updatedCollaboratorSearch(): void
    {
        if (strlen($this->collaboratorSearch) >= 2) {
            $this->showSuggestions  = true;
            $this->selectedCollaborator = null;
        } else {
            $this->showSuggestions = false;
        }
    }

    public function selectCollaborator(string $id): void
    {
        $collab = Collaborator::findOrFail($id);
        $this->selectedCollaborator = [
            'id'   => $collab->id,
            'name' => $collab->full_name,
            'doc'  => $collab->document_number,
            'type' => $collab->type,
        ];
        $this->collaboratorId    = $id;
        $this->collaboratorSearch = $collab->full_name;
        $this->showSuggestions   = false;
    }

    public function clearCollaborator(): void
    {
        $this->selectedCollaborator = null;
        $this->collaboratorId       = null;
        $this->collaboratorSearch   = '';
    }

    protected function rules(): array
    {
        $type = $this->selectedCollaborator['type'] ?? 'Empleado';

        return [
            'collaboratorId'  => 'required|uuid',
            'contractTypeId'  => 'required|uuid',
            'positionId'      => 'nullable|uuid',
            'contractNumber'  => 'nullable|string|max:50',
            'contractCode'    => 'nullable|string|max:50',
            'startDate'       => 'required|date',
            'endDate'         => 'nullable|date|after:startDate',
            'salary'          => $type === 'Empleado' ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'fees'            => $type === 'Contratista' ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'object'          => 'nullable|string|max:1000',
            'obligations'     => 'nullable|string|max:2000',
            'positionEmail'   => 'nullable|email|max:200',
            'status'          => 'required|in:Vigente,Terminado,Liquidado',
        ];
    }

    protected function messages(): array
    {
        return [
            'collaboratorId.required' => 'Seleccione un colaborador.',
            'contractTypeId.required' => 'Seleccione el tipo de contrato.',
            'startDate.required'      => 'La fecha de inicio es obligatoria.',
            'endDate.after'           => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'salary.required'         => 'El salario es obligatorio para empleados.',
            'fees.required'           => 'Los honorarios son obligatorios para contratistas.',
            'positionEmail.email'     => 'Ingrese un correo electrónico válido.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'institution_id'   => auth()->user()->institution_id,
            'collaborator_id'  => $this->collaboratorId,
            'contract_type_id' => $this->contractTypeId,
            'position_id'      => $this->positionId ?: null,
            'contract_number'  => $this->contractNumber ?: null,
            'contract_code'    => $this->contractCode ?: null,
            'start_date'       => $this->startDate,
            'end_date'         => $this->endDate ?: null,
            'salary'           => $this->salary ?: null,
            'fees'             => $this->fees ?: null,
            'object'           => $this->object ?: null,
            'obligations'      => $this->obligations ?: null,
            'position_email'   => $this->positionEmail ?: null,
            'status'           => $this->status,
        ];

        if ($this->contractId) {
            $contract = Contract::findOrFail($this->contractId);
            $this->authorize('update', $contract);
            $contract->update($data);
            session()->flash('success', 'Contrato actualizado correctamente.');
        } else {
            $this->authorize('create', Contract::class);
            Contract::create($data);
            session()->flash('success', 'Contrato registrado correctamente.');
        }

        $this->redirect(route('rh.contratos.index'), navigate: true);
    }

    public function getSuggestionsProperty(): array
    {
        if (!$this->showSuggestions || strlen($this->collaboratorSearch) < 2) {
            return [];
        }

        return Collaborator::query()
            ->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$this->collaboratorSearch}%")
                ->orWhere('first_surname', 'like', "%{$this->collaboratorSearch}%")
                ->orWhere('company_name', 'like', "%{$this->collaboratorSearch}%")
                ->orWhere('document_number', 'like', "%{$this->collaboratorSearch}%")
            )
            ->limit(8)
            ->get()
            ->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->full_name,
                'doc'  => $c->document_number,
                'type' => $c->type,
            ])
            ->toArray();
    }

    public function getContractTypesProperty()
    {
        return ContractType::orderBy('name')->get();
    }

    public function getPositionsProperty()
    {
        return Position::active()->orderBy('name')->get();
    }
};
?>

<div class="mx-auto max-w-2xl">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

        <div class="space-y-5">

            {{-- Búsqueda de colaborador --}}
            <div>
                <label for="collaboratorSearch" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Colaborador <span class="text-red-500" aria-hidden="true">*</span>
                </label>

                @if($selectedCollaborator)
                    <div class="mt-1.5 flex items-center justify-between rounded-lg border border-blue-300 bg-blue-50 px-3 py-2.5 dark:border-blue-700 dark:bg-blue-900/20">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full
                                {{ $selectedCollaborator['type'] === 'Empleado' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}
                                text-xs font-semibold">
                                {{ strtoupper(substr($selectedCollaborator['name'], 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedCollaborator['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $selectedCollaborator['doc'] }} &bull;
                                    @if($selectedCollaborator['type'] === 'Empleado')
                                        <span class="text-blue-600 dark:text-blue-400">Empleado</span>
                                    @else
                                        <span class="text-purple-600 dark:text-purple-400">Contratista</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="clearCollaborator"
                            class="rounded-md p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            aria-label="Cambiar colaborador">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @else
                    <div class="relative mt-1.5">
                        <input
                            wire:model.live.debounce.300ms="collaboratorSearch"
                            id="collaboratorSearch"
                            type="text"
                            placeholder="Buscar por nombre o número de cédula..."
                            autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                        />
                        @if($showSuggestions && count($this->suggestions) > 0)
                            <ul class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                role="listbox" aria-label="Resultados de búsqueda de colaboradores">
                                @foreach($this->suggestions as $s)
                                    <li>
                                        <button
                                            type="button"
                                            wire:click="selectCollaborator('{{ $s['id'] }}')"
                                            class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                            role="option">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full
                                                {{ $s['type'] === 'Empleado' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}
                                                text-xs font-semibold">
                                                {{ strtoupper(substr($s['name'], 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ $s['name'] }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $s['doc'] }} &bull; {{ $s['type'] }}</p>
                                            </div>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @elseif($showSuggestions && strlen($this->collaboratorSearch) >= 2)
                            <div class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-500 shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                No se encontraron colaboradores.
                            </div>
                        @endif
                    </div>
                @endif
                @error('collaboratorId')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipo de contrato --}}
            <div>
                <label for="contractTypeId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tipo de contrato <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select
                    wire:model="contractTypeId"
                    id="contractTypeId"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Seleccione el tipo...</option>
                    @foreach($this->contractTypes as $ct)
                        <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                    @endforeach
                </select>
                @error('contractTypeId')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cargo --}}
            <div>
                <label for="positionId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Cargo
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(opcional)</span>
                </label>
                <select
                    wire:model="positionId"
                    id="positionId"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Sin cargo asignado</option>
                    @foreach($this->positions as $pos)
                        <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Números de contrato --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="contractNumber" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Número de contrato
                    </label>
                    <input
                        wire:model.blur="contractNumber"
                        id="contractNumber"
                        type="text"
                        placeholder="Ej: 001-2025"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    />
                </div>
                <div>
                    <label for="contractCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Código del contrato
                    </label>
                    <input
                        wire:model.blur="contractCode"
                        id="contractCode"
                        type="text"
                        placeholder="Ej: PS-2025-001"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    />
                </div>
            </div>

            {{-- Fechas --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Fecha de inicio <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        wire:model="startDate"
                        id="startDate"
                        type="date"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                    @error('startDate')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Fecha de fin
                        <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(vacío = indefinido)</span>
                    </label>
                    <input
                        wire:model="endDate"
                        id="endDate"
                        type="date"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                    @error('endDate')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Salario / Honorarios --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Salario (empleados) --}}
                <div>
                    <label for="salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Salario (COP)
                        @if($selectedCollaborator && $selectedCollaborator['type'] === 'Empleado')
                            <span class="text-red-500" aria-hidden="true">*</span>
                        @else
                            <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(empleados)</span>
                        @endif
                    </label>
                    <input
                        wire:model.blur="salary"
                        id="salary"
                        type="number"
                        min="0"
                        step="1000"
                        placeholder="Ej: 1300000"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    />
                    @error('salary')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Honorarios (contratistas) --}}
                <div>
                    <label for="fees" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Honorarios (COP)
                        @if($selectedCollaborator && $selectedCollaborator['type'] === 'Contratista')
                            <span class="text-red-500" aria-hidden="true">*</span>
                        @else
                            <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(contratistas)</span>
                        @endif
                    </label>
                    <input
                        wire:model.blur="fees"
                        id="fees"
                        type="number"
                        min="0"
                        step="1000"
                        placeholder="Ej: 3000000"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    />
                    @error('fees')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Objeto del contrato --}}
            <div>
                <label for="object" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Objeto del contrato
                </label>
                <textarea
                    wire:model.blur="object"
                    id="object"
                    rows="3"
                    placeholder="Describa el objeto del contrato..."
                    class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                ></textarea>
            </div>

            {{-- Obligaciones --}}
            <div>
                <label for="obligations" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Obligaciones
                </label>
                <textarea
                    wire:model.blur="obligations"
                    id="obligations"
                    rows="3"
                    placeholder="Describa las obligaciones del contrato..."
                    class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                ></textarea>
            </div>

            {{-- Correo del cargo y estado --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="positionEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Correo del cargo
                    </label>
                    <input
                        wire:model.blur="positionEmail"
                        id="positionEmail"
                        type="email"
                        placeholder="cargo@ascun.edu.co"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    />
                    @error('positionEmail')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Estado del contrato <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select
                        wire:model="status"
                        id="status"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="Vigente">Vigente</option>
                        <option value="Terminado">Terminado</option>
                        <option value="Liquidado">Liquidado</option>
                    </select>
                </div>
            </div>

        </div>

        {{-- Botones --}}
        <div class="mt-6 flex items-center justify-between gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
            <a href="{{ route('rh.contratos.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                Cancelar
            </a>
            <button
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
                <span wire:loading wire:target="save">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </span>
                <span wire:loading.remove wire:target="save">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
                {{ $contractId ? 'Actualizar contrato' : 'Registrar contrato' }}
            </button>
        </div>
    </div>
</div>
