<?php

declare(strict_types=1);

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\RH\CertificateSignature;

new class extends Component {
    public bool $open = false;

    // Paso 1: buscar colaborador
    public string $search   = '';
    public array  $results  = [];
    public bool   $searching = false;

    // Colaborador seleccionado
    public ?string $collaboratorId   = null;
    public string  $collaboratorName = '';
    public string  $collaboratorType = ''; // 'Empleado' | 'Contratista'
    public string  $collaboratorDoc  = '';

    // Paso 2: contratos
    public array   $contracts        = [];
    public array   $selectedContractIds = [];
    public bool    $allContracts = true;

    // Paso 3: opciones
    public bool   $showSalary          = false;
    public bool   $showPositionHistory = false;
    public bool   $showObject          = true;
    public bool   $showObligations     = true;
    public bool   $showValue           = true;
    public bool   $showProrrogas       = true;
    public bool   $showEarlyTermination = true;

    // Firma y dirigido a
    public ?string $signatureId = null;
    public string  $addressedTo = '';
    public array   $signatures  = [];

    public function mount(): void
    {
        $this->loadSignatures();
    }

    public function loadSignatures(): void
    {
        $this->signatures = CertificateSignature::where('institution_id', auth()->user()?->institution_id)
            ->where('is_active', true)
            ->get(['id', 'signer_name', 'signer_position'])
            ->toArray();

        if (count($this->signatures) === 1) {
            $this->signatureId = $this->signatures[0]['id'];
        }
    }

    public function openModal(): void
    {
        $this->reset(['collaboratorId', 'collaboratorName', 'collaboratorType', 'collaboratorDoc',
                      'search', 'results', 'contracts', 'selectedContractIds',
                      'showSalary', 'showPositionHistory', 'addressedTo']);
        $this->showObject = true;
        $this->showObligations = true;
        $this->showValue = true;
        $this->showProrrogas = true;
        $this->showEarlyTermination = true;
        $this->allContracts = true;
        $this->loadSignatures();
        $this->open = true;
    }

    #[On('open-for-collaborator')]
    public function openForCollaborator(string $collaboratorId): void
    {
        $this->openModal();
        $this->selectCollaborator($collaboratorId);
    }

    public function updatedSearch(): void
    {
        if (strlen(trim($this->search)) < 2) {
            $this->results = [];
            return;
        }

        $buscar = trim($this->search);
        $institutionId = auth()->user()?->institution_id;

        $this->results = Collaborator::where('institution_id', $institutionId)
            ->where(fn ($q) =>
                $q->where('first_name', 'like', "%{$buscar}%")
                  ->orWhere('first_surname', 'like', "%{$buscar}%")
                  ->orWhere('second_surname', 'like', "%{$buscar}%")
                  ->orWhere('company_name', 'like', "%{$buscar}%")
                  ->orWhere('document_number', 'like', "%{$buscar}%")
            )
            ->limit(8)
            ->get(['id', 'first_name', 'second_name', 'first_surname', 'second_surname',
                   'company_name', 'document_number', 'type', 'is_company'])
            ->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->full_name,
                'doc'  => $c->document_number,
                'type' => $c->type,
            ])
            ->toArray();
    }

    public function selectCollaborator(string $id): void
    {
        $collaborator = Collaborator::where('institution_id', auth()->user()?->institution_id)
            ->findOrFail($id);

        $this->collaboratorId   = $collaborator->id;
        $this->collaboratorName = $collaborator->full_name;
        $this->collaboratorType = $collaborator->type; // 'Empleado' | 'Contratista'
        $this->collaboratorDoc  = $collaborator->document_number ?? '';
        $this->search           = '';
        $this->results          = [];

        $this->loadContracts();
    }

    public function loadContracts(): void
    {
        if (! $this->collaboratorId) {
            return;
        }

        $this->contracts = Contract::where('collaborator_id', $this->collaboratorId)
            ->with('contractType')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn ($c) => [
                'id'            => $c->id,
                'code'          => $c->contract_code ?? '(sin código)',
                'type'          => $c->contractType?->name ?? '—',
                'start_date'    => $c->start_date?->format('d/m/Y') ?? '—',
                'end_date'      => $c->end_date?->format('d/m/Y') ?? 'Indefinido',
                'status'        => $c->status ?? '—',
            ])
            ->toArray();

        // Por defecto seleccionar todos los contratos
        $this->selectedContractIds = array_column($this->contracts, 'id');
    }

    public function toggleAllContracts(): void
    {
        $this->allContracts = ! $this->allContracts;
        if ($this->allContracts) {
            $this->selectedContractIds = array_column($this->contracts, 'id');
        } else {
            $this->selectedContractIds = [];
        }
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function getRouteProperty(): string
    {
        return $this->collaboratorType === 'Empleado'
            ? route('certificados.generar.empleado')
            : route('certificados.generar.contratista');
    }
};
?>

<div id="modal-generar-cert"
     x-on:open-modal.window="$wire.openModal()"
     x-on:open-modal="$wire.openModal()">

    @if ($open)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60" wire:key="modal-generar">
        <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-800">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Generar certificado</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @if ($collaboratorId)
                            {{ $collaboratorName }} &mdash;
                            <span class="font-medium {{ $collaboratorType === 'Empleado' ? 'text-blue-600' : 'text-purple-600' }}">
                                {{ $collaboratorType === 'Empleado' ? 'Certificado Laboral' : 'Certificado de Contratación' }}
                            </span>
                        @else
                            Seleccione el colaborador
                        @endif
                    </p>
                </div>
                <button wire:click="close" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700" aria-label="Cerrar modal">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-6">

                {{-- PASO 1: Buscar colaborador --}}
                @if (! $collaboratorId)
                    <div>
                        <label for="search-colaborador" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Buscar colaborador
                        </label>
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="search"
                                   id="search-colaborador"
                                   type="text"
                                   placeholder="Nombre, apellido o número de documento..."
                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                        </div>

                        @if (count($results) > 0)
                            <ul class="mt-2 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white shadow-lg dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800"
                                role="listbox" aria-label="Resultados de búsqueda">
                                @foreach ($results as $r)
                                    <li role="option">
                                        <button wire:click="selectCollaborator('{{ $r['id'] }}')"
                                                class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $r['type'] === 'Empleado' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }} text-xs font-bold" aria-hidden="true">
                                                {{ strtoupper(substr($r['name'], 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $r['name'] }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Doc. {{ $r['doc'] }} &mdash; {{ $r['type'] }}</p>
                                            </div>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @elseif (strlen(trim($search)) >= 2)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No se encontraron colaboradores.</p>
                        @endif
                    </div>
                @else
                    {{-- Colaborador seleccionado --}}
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full {{ $collaboratorType === 'Empleado' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }} text-sm font-bold" aria-hidden="true">
                                {{ strtoupper(substr($collaboratorName, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $collaboratorName }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Doc. {{ $collaboratorDoc }}</p>
                            </div>
                        </div>
                        <button wire:click="$set('collaboratorId', null)"
                                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 underline">
                            Cambiar
                        </button>
                    </div>

                    {{-- Formulario de generación --}}
                    <form method="POST"
                          action="{{ $this->route }}"
                          target="_blank">
                        @csrf

                        <input type="hidden" name="collaborator_id" value="{{ $collaboratorId }}">

                        {{-- Contratos --}}
                        <div class="mb-5">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Contratos a incluir
                                </p>
                                @if (count($contracts) > 1)
                                    <button type="button" wire:click="toggleAllContracts"
                                            class="text-xs text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $allContracts ? 'Deseleccionar todos' : 'Seleccionar todos' }}
                                    </button>
                                @endif
                            </div>

                            @if (empty($contracts))
                                <p class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-gray-600">
                                    Este colaborador no tiene contratos registrados.
                                </p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($contracts as $i => $c)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border {{ in_array($c['id'], $selectedContractIds) ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} p-3 transition-colors hover:border-blue-300">
                                            <input type="checkbox"
                                                   name="contract_ids[]"
                                                   value="{{ $c['id'] }}"
                                                   wire:model.live="selectedContractIds"
                                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $c['type'] }}
                                                    @if ($c['code'] !== '(sin código)') &mdash; <span class="font-mono text-xs">{{ $c['code'] }}</span> @endif
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $c['start_date'] }} → {{ $c['end_date'] }}
                                                    <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                                        {{ $c['status'] === 'Vigente' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                                        {{ $c['status'] }}
                                                    </span>
                                                </p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Opciones según tipo --}}
                        @if ($collaboratorType === 'Empleado')
                            <div class="mb-5">
                                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Información a incluir</p>
                                <div class="space-y-2">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                        <input type="checkbox" name="options[show_salary]" value="1"
                                               wire:model="showSalary"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Incluir salario</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                        <input type="checkbox" name="options[show_position_history]" value="1"
                                               wire:model="showPositionHistory"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Incluir historial de cargos</span>
                                    </label>
                                </div>
                            </div>
                        @else
                            <div class="mb-5">
                                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Información a incluir</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                        <input type="checkbox" name="options[show_object]" value="1"
                                               wire:model="showObject"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Objeto</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                        <input type="checkbox" name="options[show_obligations]" value="1"
                                               wire:model="showObligations"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Obligaciones</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                        <input type="checkbox" name="options[show_value]" value="1"
                                               wire:model="showValue"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Valor del contrato</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                        <input type="checkbox" name="options[show_prorrogas]" value="1"
                                               wire:model="showProrrogas"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Prórrogas</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 col-span-2">
                                        <input type="checkbox" name="options[show_early_termination]" value="1"
                                               wire:model="showEarlyTermination"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Terminación anticipada</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        {{-- Firma --}}
                        <div class="mb-5">
                            <label for="certificate_signature_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Firma digital <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            @if (empty($signatures))
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300"
                                     role="alert">
                                    No hay firmas activas configuradas.
                                    @can('create', \App\Models\RH\CertificateSignature::class)
                                        <a href="{{ route('certificados.firmas.create') }}" class="underline">Crear firma</a>
                                    @endcan
                                </div>
                            @else
                                <select name="certificate_signature_id"
                                        id="certificate_signature_id"
                                        wire:model.live="signatureId"
                                        required
                                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">Seleccione una firma...</option>
                                    @foreach ($signatures as $firma)
                                        <option value="{{ $firma['id'] }}">
                                            {{ $firma['signer_name'] }} — {{ $firma['signer_position'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        {{-- Dirigido a --}}
                        <div class="mb-6">
                            <label for="addressed_to" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Dirigido a
                            </label>
                            <input type="text"
                                   name="addressed_to"
                                   id="addressed_to"
                                   wire:model="addressedTo"
                                   placeholder="A quien interese (dejar vacío para texto genérico)"
                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500" />
                        </div>

                        {{-- Acciones --}}
                        <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <button type="button" wire:click="close"
                                    class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                Cancelar
                            </button>
                            <button type="submit"
                                    x-bind:disabled="$wire.selectedContractIds.length === 0 || !$wire.signatureId"
                                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Generar y descargar PDF
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
        </div>
    </div>
    @endif
</div>
