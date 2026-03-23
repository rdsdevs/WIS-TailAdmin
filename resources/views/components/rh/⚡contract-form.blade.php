<?php

use Livewire\Component;
use App\Models\RH\Contract;
use App\Models\RH\CommittedValue;
use App\Models\RH\Collaborator;
use App\Models\RH\ContractType;
use App\Models\RH\Position;
use App\Models\Contabilidad\AccountingAccount;
use App\Models\Contabilidad\CostCenter;
use Illuminate\Support\Facades\DB;

new class extends Component {

    // ── Props ─────────────────────────────────────────────────────────────────
    public ?string $contractId     = null;
    public ?string $collaboratorId = null;

    // ── Paso del wizard: 1 | 2 | 3 ───────────────────────────────────────────
    public int $step = 1;

    // ── Paso 1: búsqueda de colaborador ──────────────────────────────────────
    public string $collaboratorSearch   = '';
    public ?array $selectedCollaborator = null;
    public bool   $showSuggestions      = false;

    // ── Paso 2: datos del contrato ────────────────────────────────────────────
    public string  $contractTypeId = '';
    public string  $positionId     = '';
    public string  $contractNumber = '';
    public string  $startDate      = '';
    public ?string $endDate        = null;
    public string  $salary         = '';
    public string  $fees           = '';
    public string  $object         = '';
    public string  $obligations    = '';
    public string  $positionEmail  = '';
    public string  $status         = 'Vigente';

    // ── Paso 3: comprometidos ─────────────────────────────────────────────────
    // Cuenta contable: única para todo el contrato
    public string $accountingSearch          = '';
    public ?array $selectedAccount           = null; // ['id' => '', 'code' => '', 'name' => '']
    public bool   $showAccountSuggestions    = false;

    // Cada línea: ['accounting_account' => '', 'cost_center' => '', 'cost_center_name' => '', 'cost_center_search' => '', 'show_suggestions' => false, 'amount' => '']
    public array $committedLines = [];

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(?string $contractId = null, ?string $collaboratorId = null): void
    {
        $this->contractId     = $contractId;
        $this->collaboratorId = $collaboratorId;

        if ($contractId) {
            $c = Contract::with(['collaborator', 'committedValues'])->findOrFail($contractId);

            $this->collaboratorId  = (string) $c->collaborator_id;
            $this->contractTypeId  = (string) $c->contract_type_id;
            $this->positionId      = (string) ($c->position_id ?? '');
            $this->contractNumber  = $c->contract_number ?? '';
            $this->startDate       = $c->start_date?->format('Y-m-d') ?? '';
            $this->endDate         = $c->end_date?->format('Y-m-d');
            $this->salary          = (string) ($c->salary ?? '');
            $this->fees            = (string) ($c->fees ?? '');
            $this->object          = $c->object ?? '';
            $this->obligations     = $c->obligations ?? '';
            $this->positionEmail   = $c->position_email ?? '';
            $this->status          = $c->status ?? 'Vigente';

            if ($c->collaborator) {
                $this->selectedCollaborator = [
                    'id'   => $c->collaborator->id,
                    'name' => $c->collaborator->full_name,
                    'doc'  => $c->collaborator->document_number,
                    'type' => $c->collaborator->type,
                ];
                $this->collaboratorSearch = $c->collaborator->full_name;
            }

            // Cargar comprometidos existentes
            $this->committedLines = $c->committedValues
                ->map(fn ($cv) => [
                    'accounting_account'  => $cv->accounting_account ?? '',
                    'cost_center'         => $cv->cost_center ?? '',
                    'cost_center_name'    => '',
                    'cost_center_search'  => $cv->cost_center ?? '',
                    'show_suggestions'    => false,
                    'amount'              => (string) ($cv->amount ?? ''),
                ])
                ->toArray();

            // Inicializar cuenta contable desde la primera línea (si existe)
            $firstAccount = $c->committedValues->first()?->accounting_account;
            if ($firstAccount) {
                $this->accountingSearch = $firstAccount;
                // Intentar resolver el modelo si ya existe
                if (class_exists(AccountingAccount::class)) {
                    $acc = AccountingAccount::where('code', $firstAccount)->first();
                    if ($acc) {
                        $this->selectedAccount  = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name];
                        $this->accountingSearch = $acc->code . ' — ' . $acc->name;
                    }
                }
            }

        } elseif ($collaboratorId) {
            $collab = Collaborator::find($collaboratorId);
            if ($collab) {
                $this->collaboratorId       = $collab->id;
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

    // ── Autocompletado de colaborador ─────────────────────────────────────────

    public function updatedCollaboratorSearch(): void
    {
        if (strlen($this->collaboratorSearch) >= 2) {
            $this->showSuggestions      = true;
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

    // ── Autocompletado de cuenta contable ─────────────────────────────────────

    public function updatedAccountingSearch(): void
    {
        $this->showAccountSuggestions = strlen($this->accountingSearch) >= 1;
        $this->selectedAccount        = null;
    }

    public function selectAccount(string $id): void
    {
        $account = AccountingAccount::findOrFail($id);
        $this->selectedAccount = [
            'id'   => $account->id,
            'code' => $account->code,
            'name' => $account->name,
        ];
        $this->accountingSearch       = $account->code . ' — ' . $account->name;
        $this->showAccountSuggestions = false;

        // Propagar el código a todas las líneas existentes
        foreach ($this->committedLines as $i => $line) {
            $this->committedLines[$i]['accounting_account'] = $account->code;
        }
    }

    public function clearAccount(): void
    {
        $this->selectedAccount        = null;
        $this->accountingSearch       = '';
        $this->showAccountSuggestions = false;
    }

    // ── Autocompletado de centro de costos por línea ──────────────────────────

    public function selectCostCenter(int $lineIndex, string $id): void
    {
        $cc = CostCenter::findOrFail($id);
        $this->committedLines[$lineIndex]['cost_center']        = $cc->code;
        $this->committedLines[$lineIndex]['cost_center_name']   = $cc->name;
        $this->committedLines[$lineIndex]['cost_center_search'] = $cc->code . ' — ' . $cc->name;
        $this->committedLines[$lineIndex]['show_suggestions']   = false;
    }

    public function clearCostCenter(int $index): void
    {
        $this->committedLines[$index]['cost_center']        = '';
        $this->committedLines[$index]['cost_center_name']   = '';
        $this->committedLines[$index]['cost_center_search'] = '';
    }

    public function searchCostCenters(string $term): array
    {
        if (strlen($term) < 1 || !class_exists(CostCenter::class)) {
            return [];
        }

        $institutionId = auth()->user()->institution_id;

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

    // ── Navegación del wizard ─────────────────────────────────────────────────

    public function goToStep(int $target): void
    {
        // Validar el paso actual antes de avanzar
        if ($target > $this->step) {
            if ($this->step === 1) {
                $this->validateStep1();
            } elseif ($this->step === 2) {
                $this->validateStep2();
                // Si el año es < 2025 y se intenta ir al paso 3, no hay paso 3
                if ($target === 3 && ! $this->needsCommitted) {
                    $this->save();
                    return;
                }
            }
        }

        $this->step = $target;
        $this->resetValidation();
    }

    protected function validateStep1(): void
    {
        $this->validate(
            ['collaboratorId' => 'required|uuid'],
            ['collaboratorId.required' => 'Seleccione un colaborador para continuar.']
        );
    }

    protected function validateStep2(): void
    {
        $type = $this->selectedCollaborator['type'] ?? 'Empleado';

        $rules = [
            'contractTypeId' => 'required|uuid',
            'startDate'      => 'required|date',
            'endDate'        => 'nullable|date|after:startDate',
            'salary'         => $type === 'Empleado' ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'fees'           => $type === 'Contratista' ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'object'         => 'nullable|string|max:1000',
            'obligations'    => 'nullable|string|max:2000',
            'positionEmail'  => 'nullable|email|max:200',
            'status'         => 'required|in:Vigente,Terminado,Liquidado',
        ];

        $messages = [
            'contractTypeId.required' => 'Seleccione el tipo de contrato.',
            'startDate.required'      => 'La fecha de inicio es obligatoria.',
            'endDate.after'           => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'salary.required'         => 'El salario es obligatorio para empleados.',
            'fees.required'           => 'Los honorarios son obligatorios para contratistas.',
            'positionEmail.email'     => 'Ingrese un correo electrónico válido.',
        ];

        $this->validate($rules, $messages);
    }

    // ── Comprometidos ─────────────────────────────────────────────────────────

    public function addCommittedLine(): void
    {
        $this->committedLines[] = [
            'accounting_account'  => $this->selectedAccount['code'] ?? '',
            'cost_center'         => '',
            'cost_center_name'    => '',
            'cost_center_search'  => '',
            'show_suggestions'    => false,
            'amount'              => '',
        ];
    }

    public function removeCommittedLine(int $index): void
    {
        array_splice($this->committedLines, $index, 1);
        $this->committedLines = array_values($this->committedLines);
    }

    // ── Computed properties ───────────────────────────────────────────────────

    public function getContractYearProperty(): int
    {
        if (! $this->startDate) {
            return 0;
        }
        return (int) date('Y', strtotime($this->startDate));
    }

    public function getNeedsCommittedProperty(): bool
    {
        return $this->contractYear >= 2025;
    }

    public function getTotalCommittedProperty(): float
    {
        return array_sum(
            array_map(fn ($l) => (float) ($l['amount'] ?? 0), $this->committedLines)
        );
    }

    public function getContractValueProperty(): float
    {
        $type = $this->selectedCollaborator['type'] ?? 'Empleado';
        return $type === 'Contratista'
            ? (float) ($this->fees ?: 0)
            : (float) ($this->salary ?: 0);
    }

    public function getCommittedDifferenceProperty(): float
    {
        return $this->contractValue - $this->totalCommitted;
    }

    public function getTotalStepsProperty(): int
    {
        return $this->needsCommitted ? 3 : 2;
    }

    // ── Guardar ───────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validateStep1();
        $this->validateStep2();

        if ($this->needsCommitted) {
            // Validar que se haya seleccionado una cuenta contable
            if (empty($this->selectedAccount)) {
                $this->addError('accountingSearch', 'Seleccione una cuenta contable para continuar.');
                return;
            }

            // Validar comprometidos: deben existir y sumar exactamente el valor del contrato
            if (empty($this->committedLines)) {
                $this->addError('committedLines', 'Debe agregar al menos una línea de comprometido.');
                return;
            }

            foreach ($this->committedLines as $i => $line) {
                if (empty($line['cost_center'])) {
                    $this->addError("committedLines.{$i}.cost_center", 'El centro de costos es obligatorio.');
                    return;
                }
                if (empty($line['amount']) || (float) $line['amount'] <= 0) {
                    $this->addError("committedLines.{$i}.amount", 'El valor debe ser mayor a cero.');
                    return;
                }
            }

            if ($this->committedDifference != 0) {
                $this->addError('committedLines', 'La suma de comprometidos debe ser igual al valor del contrato.');
                return;
            }
        }

        if ($this->salary === '') { $this->salary = '0'; }
        if ($this->fees   === '') { $this->fees   = '0'; }

        $data = [
            'institution_id'   => auth()->user()->institution_id,
            'collaborator_id'  => $this->collaboratorId,
            'contract_type_id' => $this->contractTypeId,
            'position_id'      => $this->positionId ?: null,
            'contract_number'  => $this->contractNumber ?: null,
            'start_date'       => $this->startDate,
            'end_date'         => $this->endDate ?: null,
            'salary'           => $this->salary !== '' ? $this->salary : 0,
            'fees'             => $this->fees   !== '' ? $this->fees   : 0,
            'object'           => $this->object ?: null,
            'obligations'      => $this->obligations ?: null,
            'position_email'   => $this->positionEmail ?: null,
            'status'           => $this->status,
        ];

        DB::transaction(function () use ($data): void {
            if ($this->contractId) {
                $contract = Contract::findOrFail($this->contractId);
                $this->authorize('update', $contract);
                $contract->update($data);

                // Reemplazar comprometidos si el contrato requiere
                if ($this->needsCommitted) {
                    $accountCode = $this->selectedAccount['code'] ?? '';
                    $contract->committedValues()->delete();
                    foreach ($this->committedLines as $line) {
                        $contract->committedValues()->create([
                            'institution_id'     => auth()->user()->institution_id,
                            'accounting_account' => $accountCode,
                            'cost_center'        => $line['cost_center'],
                            'amount'             => $line['amount'],
                        ]);
                    }
                }

                session()->flash('success', 'Contrato actualizado correctamente.');
            } else {
                $this->authorize('create', Contract::class);
                $contract = Contract::create($data);

                if ($this->needsCommitted) {
                    $accountCode = $this->selectedAccount['code'] ?? '';
                    foreach ($this->committedLines as $line) {
                        $contract->committedValues()->create([
                            'institution_id'     => auth()->user()->institution_id,
                            'accounting_account' => $accountCode,
                            'cost_center'        => $line['cost_center'],
                            'amount'             => $line['amount'],
                        ]);
                    }
                }

                session()->flash('success', 'Contrato registrado correctamente.');
            }
        });

        $this->redirect(route('rh.contratos.index'), navigate: false);
    }

    // ── Computed: sugerencias de cuenta contable ──────────────────────────────

    public function getAccountSuggestionsProperty(): array
    {
        if (!$this->showAccountSuggestions || strlen($this->accountingSearch) < 1 || !class_exists(AccountingAccount::class)) {
            return [];
        }

        $institutionId = auth()->user()->institution_id;

        return AccountingAccount::active()
            ->where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', $institutionId))
            ->where(fn ($q) => $q
                ->where('code', 'like', "%{$this->accountingSearch}%")
                ->orWhere('name', 'like', "%{$this->accountingSearch}%")
            )
            ->limit(8)
            ->get()
            ->map(fn ($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name])
            ->toArray();
    }

    // ── Computed: listas de selects ───────────────────────────────────────────

    public function getSuggestionsProperty(): array
    {
        if (! $this->showSuggestions || strlen($this->collaboratorSearch) < 2) {
            return [];
        }

        return Collaborator::query()
            ->where('institution_id', auth()->user()->institution_id)
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
        $institutionId = auth()->user()->institution_id;

        return ContractType::where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', $institutionId))
            ->orderBy('name')->get();
    }

    public function getPositionsProperty()
    {
        return Position::active()
            ->where('institution_id', auth()->user()->institution_id)
            ->orderBy('name')->get();
    }
};
?>

<div class="mx-auto max-w-3xl">

    {{-- ── Indicador de pasos ──────────────────────────────────────────────── --}}
    <div class="mb-8 flex items-center">

        {{-- Paso 1 --}}
        <button
            type="button"
            wire:click="$set('step', 1)"
            class="flex items-center gap-2 focus:outline-none"
            aria-label="Ir al paso 1: Colaborador">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                {{ $step === 1
                    ? 'bg-blue-600 text-white'
                    : ($step > 1 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400') }}">
                @if($step > 1)
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                @else
                    1
                @endif
            </span>
            <span class="hidden text-xs font-medium sm:block
                {{ $step === 1 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}">
                Colaborador
            </span>
        </button>

        <div class="mx-2 h-px flex-1 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></div>

        {{-- Paso 2 --}}
        <button
            type="button"
            wire:click="$set('step', 2)"
            class="flex items-center gap-2 focus:outline-none"
            aria-label="Ir al paso 2: Datos del contrato">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                {{ $step === 2
                    ? 'bg-blue-600 text-white'
                    : ($step > 2 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400') }}">
                @if($step > 2)
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                @else
                    2
                @endif
            </span>
            <span class="hidden text-xs font-medium sm:block
                {{ $step === 2 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}">
                Contrato
            </span>
        </button>

        @if($this->needsCommitted || $step === 3)
            <div class="mx-2 h-px flex-1 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></div>

            {{-- Paso 3 (solo si aplica) --}}
            <button
                type="button"
                wire:click="$set('step', 3)"
                class="flex items-center gap-2 focus:outline-none"
                aria-label="Ir al paso 3: Info contable">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                    {{ $step === 3
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                    3
                </span>
                <span class="hidden text-xs font-medium sm:block
                    {{ $step === 3 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}">
                    Contable
                </span>
            </button>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- PASO 1: Colaborador                                               --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        @if($step === 1)
            <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Colaborador</h2>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Busque y seleccione el colaborador al que se le registrará el contrato.
            </p>

            <div>
                <label for="collaboratorSearch" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nombre o número de cédula <span class="text-red-500" aria-hidden="true">*</span>
                </label>

                @if($selectedCollaborator)
                    {{-- Colaborador seleccionado --}}
                    <div class="mt-1.5 flex items-center justify-between rounded-lg border border-blue-300 bg-blue-50 px-3 py-2.5 dark:border-blue-700 dark:bg-blue-900/20">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                                {{ $selectedCollaborator['type'] === 'Empleado'
                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                                    : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}">
                                {{ strtoupper(substr($selectedCollaborator['name'], 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedCollaborator['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $selectedCollaborator['doc'] }} &bull;
                                    @if($selectedCollaborator['type'] === 'Empleado')
                                        <span class="text-blue-600 dark:text-blue-400">Empleado</span>
                                    @else
                                        <span class="text-purple-600 dark:text-purple-400">Contratista</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="clearCollaborator"
                            class="rounded-md p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            aria-label="Cambiar colaborador">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @else
                    {{-- Buscador --}}
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
                                role="listbox"
                                aria-label="Resultados de búsqueda de colaboradores">
                                @foreach($this->suggestions as $s)
                                    <li>
                                        <button
                                            type="button"
                                            wire:click="selectCollaborator('{{ $s['id'] }}')"
                                            class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                            role="option">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                                                {{ $s['type'] === 'Empleado'
                                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                                                    : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}">
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
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- PASO 2: Datos del contrato                                        --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        @if($step === 2)
            <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Datos del contrato</h2>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Complete la información principal del contrato
                @if($selectedCollaborator)
                    para <strong class="font-medium text-gray-700 dark:text-gray-200">{{ $selectedCollaborator['name'] }}</strong>.
                @else
                    .
                @endif
            </p>

            <div class="space-y-5">

                {{-- Tipo de contrato --}}
                <div>
                    <label for="contractTypeId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tipo de contrato <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select
                        wire:model="contractTypeId"
                        id="contractTypeId"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
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
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Sin cargo asignado</option>
                        @foreach($this->positions as $pos)
                            <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Número de contrato y código --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contractNumber" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Número de contrato
                        </label>
                        <input
                            wire:model.blur="contractNumber"
                            id="contractNumber"
                            type="text"
                            placeholder="Ej: 001"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Código del contrato
                            <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(generado automáticamente)</span>
                        </label>
                        <div class="mt-1.5 flex h-[42px] items-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-400">
                            @if($contractNumber && $startDate)
                                {{ $contractNumber }}-{{ $this->contractYear }}
                            @else
                                <span class="italic">Se genera al ingresar número y fecha de inicio</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Fechas --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha de inicio <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <div x-data="{
                            init() {
                                flatpickr(this.$refs.fp, {
                                    dateFormat: 'Y-m-d',
                                    altInput: true,
                                    altFormat: 'd/m/Y',
                                    defaultDate: '{{ $this->startDate }}' || null,
                                    locale: {
                                        firstDayOfWeek: 1,
                                        weekdays: {
                                            shorthand: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
                                            longhand: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado']
                                        },
                                        months: {
                                            shorthand: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
                                            longhand: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
                                        }
                                    },
                                    onChange(dates, dateStr) {
                                        $wire.set('startDate', dateStr);
                                        $wire.dispatch('startDateUpdated');
                                    }
                                });
                            }
                        }">
                            <input
                                x-ref="fp"
                                wire:model.live="startDate"
                                type="text"
                                id="startDate"
                                placeholder="dd/mm/aaaa"
                                class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                autocomplete="off"
                                readonly
                            />
                        </div>
                        @error('startDate')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha de fin
                            <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(vacío = indefinido)</span>
                        </label>
                        <div x-data="{
                            init() {
                                flatpickr(this.$refs.fp, {
                                    dateFormat: 'Y-m-d',
                                    altInput: true,
                                    altFormat: 'd/m/Y',
                                    defaultDate: '{{ $this->endDate ?? '' }}' || null,
                                    locale: {
                                        firstDayOfWeek: 1,
                                        weekdays: {
                                            shorthand: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
                                            longhand: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado']
                                        },
                                        months: {
                                            shorthand: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
                                            longhand: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
                                        }
                                    },
                                    onChange(dates, dateStr) {
                                        $wire.set('endDate', dateStr);
                                    }
                                });
                            }
                        }">
                            <input
                                x-ref="fp"
                                wire:model="endDate"
                                type="text"
                                id="endDate"
                                placeholder="dd/mm/aaaa"
                                class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                autocomplete="off"
                                readonly
                            />
                        </div>
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
                        maxlength="1000"
                        placeholder="Describa el objeto del contrato..."
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    ></textarea>
                    <p class="mt-1 text-right text-xs text-gray-400 dark:text-gray-500">{{ strlen($object) }} / 1000</p>
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
                        maxlength="2000"
                        placeholder="Describa las obligaciones del contrato..."
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    ></textarea>
                    <p class="mt-1 text-right text-xs text-gray-400 dark:text-gray-500">{{ strlen($obligations) }} / 2000</p>
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
                            class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="Vigente">Vigente</option>
                            <option value="Terminado">Terminado</option>
                            <option value="Liquidado">Liquidado</option>
                        </select>
                    </div>
                </div>

            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- PASO 3: Info contable (solo año >= 2025)                          --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        @if($step === 3)
            <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Información contable</h2>
            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                Registre las cuentas contables y centros de costos. La suma de los valores comprometidos
                debe ser igual al valor total del contrato.
            </p>

            {{-- Valor del contrato (display) --}}
            <div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Valor del contrato</p>
                <p class="mt-0.5 text-xl font-semibold text-gray-900 dark:text-white">
                    $ {{ number_format($this->contractValue, 0, ',', '.') }}
                </p>
                @if($selectedCollaborator)
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        {{ $selectedCollaborator['type'] === 'Contratista' ? 'Honorarios' : 'Salario' }}
                        del colaborador {{ $selectedCollaborator['name'] }}
                    </p>
                @endif
            </div>

            {{-- Error global de comprometidos --}}
            @error('committedLines')
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800/50 dark:bg-red-900/20 dark:text-red-400" role="alert">
                    {{ $message }}
                </div>
            @enderror

            {{-- Cuenta contable (única para todo el contrato) --}}
            <div class="mb-5">
                <label for="accountingSearch" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Cuenta contable <span class="text-red-500" aria-hidden="true">*</span>
                </label>

                @if($selectedAccount)
                    {{-- Chip con la cuenta seleccionada --}}
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
                            id="accountingSearch"
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

                @error('accountingSearch')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tabla de líneas de comprometido (centro de costos + valor) --}}
            <div class="mb-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50">
                            <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Centro de costos
                            </th>
                            <th class="w-44 px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Valor (COP)
                            </th>
                            <th class="w-10 px-3 py-2.5">
                                <span class="sr-only">Eliminar</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($committedLines as $i => $line)
                            @php $ccSuggestions = $this->searchCostCenters($line['cost_center_search'] ?? ''); @endphp
                            <tr wire:key="committed-line-{{ $i }}">
                                <td class="px-3 py-2">
                                    <div class="relative">
                                        @if(!empty($line['cost_center']))
                                            {{-- Centro de costos seleccionado --}}
                                            <div class="flex items-center gap-1.5 rounded border border-blue-200 bg-blue-50 px-2 py-1.5 dark:border-blue-700/50 dark:bg-blue-900/20">
                                                <span class="shrink-0 font-mono text-xs font-semibold text-blue-700 dark:text-blue-300">{{ $line['cost_center'] }}</span>
                                                @if(!empty($line['cost_center_name']))
                                                    <span class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $line['cost_center_name'] }}</span>
                                                @endif
                                                <button
                                                    type="button"
                                                    wire:click="clearCostCenter({{ $i }})"
                                                    class="ml-auto shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                    aria-label="Cambiar centro de costos línea {{ $i + 1 }}">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @else
                                            {{-- Buscador de centro de costos --}}
                                            <input
                                                wire:model.live.debounce.300ms="committedLines.{{ $i }}.cost_center_search"
                                                type="text"
                                                placeholder="Buscar centro de costos..."
                                                autocomplete="off"
                                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                                                aria-label="Buscar centro de costos línea {{ $i + 1 }}"
                                            />
                                            @if(count($ccSuggestions) > 0)
                                                <ul class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                                    role="listbox"
                                                    aria-label="Resultados de búsqueda de centros de costos">
                                                    @foreach($ccSuggestions as $cc)
                                                        <li>
                                                            <button
                                                                type="button"
                                                                wire:click="selectCostCenter({{ $i }}, '{{ $cc['id'] }}')"
                                                                class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                                                role="option">
                                                                <span class="shrink-0 font-mono font-semibold text-gray-900 dark:text-white">{{ $cc['code'] }}</span>
                                                                <span class="truncate text-gray-500 dark:text-gray-400">{{ $cc['name'] }}</span>
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @elseif(strlen($line['cost_center_search'] ?? '') >= 1)
                                                <div class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                                    No se encontraron centros de costos.
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    @error("committedLines.{$i}.cost_center")
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        wire:model.live="committedLines.{{ $i }}.amount"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        placeholder="0"
                                        class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-right text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                                        aria-label="Valor comprometido línea {{ $i + 1 }}"
                                    />
                                    @error("committedLines.{$i}.amount")
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button
                                        type="button"
                                        wire:click="removeCommittedLine({{ $i }})"
                                        class="rounded p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                        aria-label="Eliminar línea {{ $i + 1 }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                                    No hay valores comprometidos. Agregue al menos uno para guardar el contrato.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Botón agregar línea --}}
            <button
                type="button"
                wire:click="addCommittedLine"
                class="mb-5 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-blue-300 px-3 py-2 text-sm font-medium text-blue-600 hover:border-blue-400 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-400 dark:hover:border-blue-600 dark:hover:bg-blue-900/20">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Agregar comprometido
            </button>

            {{-- Resumen: valor vs comprometido --}}
            @php
                $diff  = $this->committedDifference;
                $total = $this->totalCommitted;
                $value = $this->contractValue;

                if ($total == 0 && empty($committedLines)) {
                    $summaryColor = 'blue';   // Sin líneas aún
                } elseif ($diff == 0) {
                    $summaryColor = 'green';  // Cuadra perfectamente
                } elseif ($diff > 0) {
                    $summaryColor = 'yellow'; // Faltan por comprometer
                } else {
                    $summaryColor = 'red';    // Excede el valor del contrato
                }
            @endphp

            <div class="rounded-lg border px-4 py-3
                {{ $summaryColor === 'green'  ? 'border-green-200 bg-green-50 dark:border-green-800/50 dark:bg-green-900/20' : '' }}
                {{ $summaryColor === 'red'    ? 'border-red-200 bg-red-50 dark:border-red-800/50 dark:bg-red-900/20' : '' }}
                {{ $summaryColor === 'yellow' ? 'border-yellow-200 bg-yellow-50 dark:border-yellow-800/50 dark:bg-yellow-900/20' : '' }}
                {{ $summaryColor === 'blue'   ? 'border-blue-200 bg-blue-50 dark:border-blue-800/50 dark:bg-blue-900/20' : '' }}">

                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Valor contrato</p>
                        <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">$ {{ number_format($value, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total comprometido</p>
                        <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">$ {{ number_format($total, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ $diff > 0 ? 'Por comprometer' : ($diff < 0 ? 'Excedente' : 'Diferencia') }}
                        </p>
                        <p class="mt-0.5 font-semibold
                            {{ $summaryColor === 'green'  ? 'text-green-700 dark:text-green-400' : '' }}
                            {{ $summaryColor === 'red'    ? 'text-red-700 dark:text-red-400' : '' }}
                            {{ $summaryColor === 'yellow' ? 'text-yellow-700 dark:text-yellow-400' : '' }}
                            {{ $summaryColor === 'blue'   ? 'text-blue-700 dark:text-blue-400' : '' }}">
                            @if($diff == 0 && !empty($committedLines))
                                Cuadrado
                            @else
                                $ {{ number_format(abs($diff), 0, ',', '.') }}
                            @endif
                        </p>
                    </div>
                </div>

                @if($summaryColor === 'green')
                    <p class="mt-2 flex items-center gap-1 text-xs text-green-700 dark:text-green-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Los comprometidos cuadran con el valor del contrato. Puede registrar.
                    </p>
                @elseif($summaryColor === 'yellow')
                    <p class="mt-2 text-xs text-yellow-700 dark:text-yellow-400">
                        Faltan $ {{ number_format($diff, 0, ',', '.') }} por comprometer.
                    </p>
                @elseif($summaryColor === 'red')
                    <p class="mt-2 text-xs text-red-700 dark:text-red-400">
                        Los comprometidos exceden el valor del contrato en $ {{ number_format(abs($diff), 0, ',', '.') }}.
                    </p>
                @else
                    <p class="mt-2 text-xs text-blue-700 dark:text-blue-400">
                        Agregue líneas de comprometidos que sumen exactamente $ {{ number_format($value, 0, ',', '.') }}.
                    </p>
                @endif
            </div>
        @endif

    </div>

    {{-- ── Botones de navegación ────────────────────────────────────────────── --}}
    <div class="mt-4 flex items-center justify-between gap-3">

        {{-- Izquierda: cancelar o volver --}}
        @if($step === 1)
            <a href="{{ route('rh.contratos.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Cancelar
            </a>
        @else
            <button
                type="button"
                wire:click="$set('step', {{ $step - 1 }})"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Atrás
            </button>
        @endif

        {{-- Derecha: siguiente o guardar --}}
        @if($step === 1)
            <button
                type="button"
                wire:click="goToStep(2)"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Siguiente
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </button>

        @elseif($step === 2 && $this->needsCommitted)
            <button
                type="button"
                wire:click="goToStep(3)"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Siguiente: Info contable
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </button>

        @elseif($step === 2 && !$this->needsCommitted)
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Guardando...
                </span>
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    {{ $contractId ? 'Actualizar contrato' : 'Registrar contrato' }}
                </span>
            </button>

        @elseif($step === 3)
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                @disabled($this->committedDifference != 0)
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40">
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Guardando...
                </span>
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    {{ $contractId ? 'Actualizar contrato' : 'Registrar contrato' }}
                </span>
            </button>
        @endif

    </div>
</div>
