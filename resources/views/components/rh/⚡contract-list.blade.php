<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Models\RH\Contract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    public string $tab      = 'vigentes'; // vigentes | terminados | todos
    public string $search   = '';
    public ?string $deletingId   = null;
    public ?string $deletingName = null;

    public string $filterMode = 'none'; // 'none' | 'year' | 'range'
    public string $filterYear = '';
    public string $filterFrom = '';
    public string $filterTo   = '';

    public bool $confirmingTerminateExpired = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingFilterMode(): void { $this->resetPage(); }
    public function updatingFilterYear(): void  { $this->resetPage(); }
    public function updatingFilterFrom(): void  { $this->resetPage(); }
    public function updatingFilterTo(): void    { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->filterMode = 'none';
        $this->filterYear = '';
        $this->filterFrom = '';
        $this->filterTo   = '';
        $this->resetPage();
    }

    public function confirmDelete(string $id): void
    {
        $contract = Contract::with('collaborator')
            ->where('institution_id', auth()->user()?->institution_id)
            ->findOrFail($id);
        $this->deletingId   = $id;
        $this->deletingName = $contract->collaborator?->full_name ?? 'este contrato';
    }

    public function cancelDelete(): void
    {
        $this->deletingId   = null;
        $this->deletingName = null;
    }

    public function delete(): void
    {
        $contract = Contract::where('id', $this->deletingId)
            ->where('institution_id', auth()->user()?->institution_id)
            ->firstOrFail();

        $this->authorize('delete', $contract);
        $contract->delete();
        $this->deletingId   = null;
        $this->deletingName = null;
        session()->flash('success', 'Contrato eliminado correctamente.');
    }

    public function terminate(string $id): void
    {
        $contract = Contract::where('id', $id)
            ->where('institution_id', auth()->user()?->institution_id)
            ->firstOrFail();
        $this->authorize('terminate', $contract);
        app(\App\Services\RH\ContractService::class)->terminate($contract);
        session()->flash('success', 'Contrato terminado correctamente.');
    }

    public function getContractsProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $institutionId = auth()->user()?->institution_id;

        $query = Contract::query()
            ->where('institution_id', $institutionId)
            ->with(['collaborator', 'position', 'contractType'])
            ->when($this->search, fn ($q) => $q->whereHas('collaborator', fn ($q2) =>
                $q2->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('first_surname', 'like', "%{$this->search}%")
                   ->orWhere('company_name', 'like', "%{$this->search}%")
                   ->orWhere('document_number', 'like', "%{$this->search}%")
            ));

        $query = match($this->tab) {
            'vigentes'    => $query->where('status', 'Vigente'),
            'terminados'  => $query->whereIn('status', ['Terminado', 'Liquidado']),
            default       => $query,
        };

        $query
            ->when($this->filterMode === 'year' && $this->filterYear !== '', fn ($q) =>
                $q->whereYear('start_date', (int) $this->filterYear)
            )
            ->when($this->filterMode === 'range', function ($q) {
                if ($this->filterFrom !== '') {
                    $q->where('start_date', '>=', $this->filterFrom);
                }
                if ($this->filterTo !== '') {
                    $q->where('start_date', '<=', $this->filterTo);
                }
            });

        return $query->orderByDesc('start_date')->paginate(10);
    }

    public function getCountsProperty(): array
    {
        $institutionId = auth()->user()?->institution_id;

        return [
            'vigentes'   => Contract::where('institution_id', $institutionId)
                                    ->where('status', 'Vigente')->count(),
            'terminados' => Contract::where('institution_id', $institutionId)
                                    ->whereIn('status', ['Terminado', 'Liquidado'])->count(),
        ];
    }

    public function getExpiredCountProperty(): int
    {
        $institutionId = auth()->user()?->institution_id;

        return Contract::where('status', 'Vigente')
            ->where('institution_id', $institutionId)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->count();
    }

    public function confirmTerminateExpired(): void
    {
        $this->confirmingTerminateExpired = true;
    }

    public function cancelTerminateExpired(): void
    {
        $this->confirmingTerminateExpired = false;
    }

    public function terminateExpiredContracts(): void
    {
        $this->confirmingTerminateExpired = false;
        $user = auth()->user();

        $this->authorize('terminateMassExpired', Contract::class);

        $expired = Contract::where('status', 'Vigente')
            ->where('institution_id', $user->institution_id)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->get(['id', 'institution_id', 'contract_code']);

        if ($expired->isEmpty()) {
            session()->flash('success', 'No hay contratos vencidos pendientes de terminar.');

            return;
        }

        $ids   = $expired->pluck('id')->all();
        $total = count($ids);

        DB::transaction(function () use ($ids): void {
            Contract::whereIn('id', $ids)->update(['status' => 'Terminado']);
        });

        Log::info(
            "[Contratos] {$total} contrato(s) terminado(s) manualmente por el usuario {$user->id}.",
            ['ids' => $ids]
        );

        session()->flash('success', "{$total} contrato(s) vencido(s) terminado(s) correctamente.");
    }

    #[On('contract-updated')]
    public function refreshList(): void
    {
        // No-op
    }
};
?>

<div>
    {{-- Banner: contratos vencidos pendientes --}}
    @if(auth()->user()?->hasAnyRole(['super-admin', 'admin', 'rh-manager', 'contractor-manager']) && $this->expiredCount > 0)
        <div class="mb-4 flex flex-col gap-3 rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-800/50 dark:bg-orange-900/20 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/40">
                    <svg class="h-4 w-4 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-orange-800 dark:text-orange-300">
                        {{ $this->expiredCount }} contrato{{ $this->expiredCount === 1 ? '' : 's' }} vencido{{ $this->expiredCount === 1 ? '' : 's' }} sin terminar
                    </p>
                    <p class="mt-0.5 text-xs text-orange-700 dark:text-orange-400">
                        {{ $this->expiredCount === 1 ? 'Este contrato tiene' : 'Estos contratos tienen' }} fecha de finalización anterior a hoy y aún {{ $this->expiredCount === 1 ? 'figura' : 'figuran' }} como <strong>Vigente</strong>.
                        El proceso automático nocturno los terminará, o puede hacerlo ahora manualmente.
                    </p>
                </div>
            </div>
            <button
                wire:click="confirmTerminateExpired"
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:bg-orange-700 dark:hover:bg-orange-600 dark:focus:ring-offset-gray-900">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Terminar vencidos
            </button>
        </div>
    @endif

    {{-- Barra de herramientas: búsqueda expandible + acciones --}}
    <div class="mb-4 flex items-center justify-between gap-3">
        {{-- Búsqueda expandible --}}
        <div x-data="{ open: false }" class="relative flex items-center">
            <button
                type="button"
                @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                @keydown.escape.window="open = false"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                :class="{ 'border-brand-500 bg-brand-50 text-brand-600 dark:border-brand-600 dark:bg-brand-900/20 dark:text-brand-400': open || $wire.search.length > 0 }"
                aria-label="Buscar">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </button>
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 -translate-x-2"
                x-transition:enter-end="opacity-100 scale-100 translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-x-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-x-2"
                @click.outside="open = false"
                class="absolute left-10 z-20 w-64 sm:w-72"
                style="display:none">
                <input
                    x-ref="searchInput"
                    wire:model.live.debounce.400ms="search"
                    type="search"
                    placeholder="Buscar por colaborador o cédula..."
                    aria-label="Buscar contratos"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-3 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                />
            </div>
        </div>
        @can('create', \App\Models\RH\Contract::class)
            <div class="flex divide-x divide-gray-200 overflow-hidden rounded-xl border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                <a href="{{ route('rh.contratos.create') }}"
                   class="flex items-center gap-2 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo contrato
                </a>
            </div>
        @endcan
    </div>

    {{-- Panel de filtros --}}
    <div x-data="{ open: {{ $filterMode !== 'none' ? 'true' : 'false' }} }" class="mb-4">
        <div class="flex items-center gap-2">
            <div class="flex overflow-hidden rounded-xl border {{ $filterMode !== 'none' ? 'border-brand-400 dark:border-brand-600' : 'border-gray-200 dark:border-gray-700' }}">
                <button @click="open = !open" type="button"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors
                        {{ $filterMode !== 'none'
                            ? 'bg-brand-50 text-brand-700 hover:bg-brand-100 dark:bg-brand-900/20 dark:text-brand-400'
                            : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60' }}">
                    <svg class="h-4 w-4 shrink-0 {{ $filterMode !== 'none' ? 'text-brand-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591L15.75 12.75v6.75a.75.75 0 01-.427.671l-3 1.5a.75.75 0 01-1.073-.681v-8.24L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3Z" />
                    </svg>
                    Filtrar
                    @if($filterMode !== 'none')
                        <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    @endif
                </button>
                @if($filterMode !== 'none')
                    <button wire:click="clearFilters" type="button"
                        class="flex items-center gap-1 border-l border-brand-300 bg-brand-50 px-2.5 py-2 text-xs text-brand-600 transition-colors hover:bg-brand-100 dark:border-brand-700 dark:bg-brand-900/20 dark:text-brand-400 dark:hover:bg-brand-900/30"
                        aria-label="Limpiar filtros">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
            <div class="mb-4 flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por:</span>
                <label class="inline-flex cursor-pointer items-center gap-1.5">
                    <input type="radio" wire:model.live="filterMode" value="year"
                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Año</span>
                </label>
                <label class="inline-flex cursor-pointer items-center gap-1.5">
                    <input type="radio" wire:model.live="filterMode" value="range"
                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Rango de fechas</span>
                </label>
                <label class="inline-flex cursor-pointer items-center gap-1.5">
                    <input type="radio" wire:model.live="filterMode" value="none"
                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Sin filtro</span>
                </label>
            </div>

            @if($filterMode === 'year')
                <div class="flex items-center gap-3">
                    <label for="filter-year" class="text-sm text-gray-600 dark:text-gray-400">Año de inicio:</label>
                    <select id="filter-year" wire:model.live="filterYear"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Seleccionar año --</option>
                        @for($y = now()->year; $y >= 2000; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            @endif

            @if($filterMode === 'range')
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="shrink-0 text-sm text-gray-600 dark:text-gray-400">Desde:</label>
                        <div class="w-44" wire:ignore>
                            <x-form.date-picker id="filter-from" wireModel="filterFrom" :value="$filterFrom" placeholder="dd/mm/aaaa" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="shrink-0 text-sm text-gray-600 dark:text-gray-400">Hasta:</label>
                        <div class="w-44" wire:ignore>
                            <x-form.date-picker id="filter-to" wireModel="filterTo" :value="$filterTo" placeholder="dd/mm/aaaa" />
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Tabs mejoradas --}}
    <div class="mb-4">
        <div class="flex divide-x divide-gray-200 overflow-hidden rounded-xl border border-gray-200 dark:divide-gray-700 dark:border-gray-700 w-fit">
            <button wire:click="$set('tab', 'vigentes')"
                class="{{ $tab === 'vigentes' ? 'bg-gray-100 text-gray-900 font-semibold dark:bg-gray-700 dark:text-white' : 'bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700/60' }} flex items-center gap-2 px-4 py-2 text-sm transition-colors">
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                Vigentes
                @if(isset($this->counts['vigentes']) && $this->counts['vigentes'] > 0)
                    <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gray-200 px-1.5 text-xs font-medium text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                        {{ $this->counts['vigentes'] }}
                    </span>
                @endif
            </button>

            <button wire:click="$set('tab', 'terminados')"
                class="{{ $tab === 'terminados' ? 'bg-gray-100 text-gray-900 font-semibold dark:bg-gray-700 dark:text-white' : 'bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700/60' }} flex items-center gap-2 px-4 py-2 text-sm transition-colors">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                Terminados
                @if(isset($this->counts['terminados']) && $this->counts['terminados'] > 0)
                    <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gray-200 px-1.5 text-xs font-medium text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                        {{ $this->counts['terminados'] }}
                    </span>
                @endif
            </button>

            <button wire:click="$set('tab', 'todos')"
                class="{{ $tab === 'todos' ? 'bg-gray-100 text-gray-900 font-semibold dark:bg-gray-700 dark:text-white' : 'bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700/60' }} flex items-center gap-2 px-4 py-2 text-sm transition-colors">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                Todos
            </button>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700" wire:loading.class="opacity-60">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Colaborador</th>
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">Tipo contrato</th>
                    @unless(auth()->user()?->hasRole('contractor-manager'))
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">Cargo</th>
                    @endunless
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Vigencia</th>
                    <th scope="col" class="hidden px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">Valor</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estado</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($this->contracts as $contract)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                                    {{ $contract->collaborator?->type === 'Empleado' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}
                                    text-xs font-semibold">
                                    @if($contract->collaborator?->is_company)
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                    @else
                                        {{ strtoupper(substr($contract->collaborator?->first_name ?? '', 0, 1) . substr($contract->collaborator?->first_surname ?? '', 0, 1)) }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate max-w-[140px]">
                                        {{ $contract->collaborator?->full_name ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $contract->collaborator?->document_number }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 md:table-cell">
                            {{ $contract->contractType?->name ?? '—' }}
                        </td>
                        @unless(auth()->user()?->hasRole('contractor-manager'))
                        <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 md:table-cell">
                            {{ $contract->position?->name ?? '—' }}
                        </td>
                        @endunless
                        <td class="px-4 py-3">
                            <div class="text-gray-700 dark:text-gray-300">
                                <span class="font-medium">{{ $contract->start_date?->format('d/m/Y') ?? '—' }}</span>
                                <span class="mx-1 text-gray-400">-</span>
                                <span class="font-medium">
                                    @if($contract->end_date)
                                        {{ $contract->end_date->format('d/m/Y') }}
                                    @else
                                        <span class="text-xs italic opacity-70">Indefinido</span>
                                    @endif
                                </span>
                            </div>
                            @if($contract->start_date && $contract->end_date)
                                @php
                                    $diff = $contract->start_date->diff($contract->end_date);
                                    $meses = ($diff->y * 12) + $diff->m;
                                    $dias = $diff->d;
                                @endphp
                                <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                                    Duración del contrato: <span class="font-medium text-gray-600 dark:text-gray-300">{{ $meses }} meses y {{ $dias }} días</span>
                                </p>
                            @endif
                        </td>
                        <td class="hidden px-4 py-3 text-right text-gray-600 dark:text-gray-400 lg:table-cell">
                            @if((float)$contract->fees > 0)
                                $ {{ number_format((float)$contract->fees, 0, ',', '.') }}
                            @elseif((float)$contract->salary > 0)
                                $ {{ number_format((float)$contract->salary, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($contract->status === 'Vigente')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">
                                    Vigente
                                </span>
                            @elseif($contract->status === 'Terminado')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800">
                                    Terminado
                                </span>
                            @elseif($contract->status === 'Liquidado')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800">
                                    Liquidado
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    {{ $contract->status ?? 'Desconocido' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $canTerminate         = auth()->user()->can('update', $contract) && $contract->status === 'Vigente';
                                $canProrrogar         = auth()->user()->can('applyProroga', $contract) && $contract->canBeProrrogated();
                                $canProrrogarAvanzado = auth()->user()->can('applyProrrogaAdvanced', $contract) && $contract->isFromPreviousYear() && $contract->status === 'Terminado';
                                $canEarlyTerminate    = auth()->user()->can('earlyTerminate', $contract) && $contract->status === 'Vigente';
                                $hasDropdown          = $canTerminate || $canProrrogar || $canProrrogarAvanzado || $canEarlyTerminate;
                            @endphp
                            <div class="flex items-center justify-end gap-0.5">
                                @can('view', $contract)
                                    <a href="{{ route('rh.contratos.show', $contract) }}" class="p-1.5 rounded-md text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Ver detalle">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </a>
                                @endcan
                                @can('update', $contract)
                                    <a href="{{ route('rh.contratos.edit', $contract) }}" class="p-1.5 rounded-md text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                    </a>
                                @endcan
                                @can('delete', $contract)
                                    <button wire:click="confirmDelete('{{ $contract->id }}')" class="p-1.5 rounded-md text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Eliminar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                @endcan

                                @if($hasDropdown)
                                    <div x-data="{ open: false, top: 0, right: 0 }" class="inline-flex">
                                        <button @click.stop="const r = $el.getBoundingClientRect(); top = r.bottom + 4; right = window.innerWidth - r.right; open = !open"
                                            class="p-1.5 rounded-md text-gray-400 hover:bg-gray-100 transition-colors dark:hover:bg-gray-700" title="Más acciones">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10 8.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM11.5 15.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" /></svg>
                                        </button>
                                        <div x-show="open" @click.outside="open = false" :style="`position:fixed;top:${top}px;right:${right}px;z-index:9999;`"
                                            class="w-52 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800" style="display: none;">
                                            @if($canTerminate)
                                                <button wire:click="terminate('{{ $contract->id }}')" wire:confirm="¿Desea terminar este contrato?" @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-yellow-700 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20">
                                                    Terminar
                                                </button>
                                            @endif
                                            @if($canProrrogar)
                                                <button wire:click="$dispatch('open-proroga-modal', { contractId: '{{ $contract->id }}' })" @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                                    Prorrogar
                                                </button>
                                            @endif
                                            @if($canEarlyTerminate)
                                                <button wire:click="$dispatch('open-early-termination-modal', { contractId: '{{ $contract->id }}' })" @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                                    Terminar anticipadamente
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()?->hasRole('contractor-manager') ? 6 : 7 }}" class="px-4 py-14 text-center">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No hay contratos para mostrar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($this->contracts->hasPages())
        <div class="mt-4">
            {{ $this->contracts->links() }}
        </div>
    @endif

    {{-- Modal eliminar --}}
    @if($deletingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Confirmar eliminación</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">¿Eliminar el contrato de <strong>{{ $deletingName }}</strong>?</p>
                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="cancelDelete" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
                    <button wire:click="delete" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Sí, eliminar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: terminar contratos vencidos --}}
    @if($confirmingTerminateExpired)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Terminar contratos vencidos</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">¿Desea terminar los {{ $this->expiredCount }} contratos vencidos?</p>
                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="cancelTerminateExpired" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
                    <button wire:click="terminateExpiredContracts" class="rounded-lg bg-orange-600 px-4 py-2 text-sm text-white">Sí, terminar</button>
                </div>
            </div>
        </div>
    @endif

    <livewire:rh.contract-proroga-modal />
    <livewire:rh.contract-early-termination-modal />
</div>
