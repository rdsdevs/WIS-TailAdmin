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

    public string $tab      = 'vigentes'; // vigentes | por_vencer | terminados | todos
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
            'por_vencer'  => $query->where('status', 'Vigente')
                                   ->whereNotNull('end_date')
                                   ->where('end_date', '<=', now()->addDays(30))
                                   ->where('end_date', '>=', now()),
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
            'por_vencer' => Contract::where('institution_id', $institutionId)
                                    ->where('status', 'Vigente')
                                    ->whereNotNull('end_date')
                                    ->where('end_date', '<=', now()->addDays(30))
                                    ->where('end_date', '>=', now())
                                    ->count(),
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
        // No-op — Livewire re-renderiza automáticamente al recibir el evento
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
            {{-- Botón Filtrar en group --}}
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
            {{-- Selector de modo --}}
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

            {{-- Filtro por año --}}
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

            {{-- Filtro por rango --}}
            @if($filterMode === 'range')
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label for="filter-from" class="text-sm text-gray-600 dark:text-gray-400">Desde:</label>
                        <input type="date" id="filter-from" wire:model.live="filterFrom"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="filter-to" class="text-sm text-gray-600 dark:text-gray-400">Hasta:</label>
                        <input type="date" id="filter-to" wire:model.live="filterTo"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-4">
        <div class="flex divide-x divide-gray-200 overflow-hidden rounded-xl border border-gray-200 dark:divide-gray-700 dark:border-gray-700 w-fit">
            {{-- Tab: Vigentes --}}
            <button
                wire:click="$set('tab', 'vigentes')"
                class="{{ $tab === 'vigentes'
                    ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900'
                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60' }} flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Vigentes
                @if(isset($this->counts['vigentes']) && $this->counts['vigentes'] > 0)
                    <span class="{{ $tab === 'vigentes'
                        ? 'inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-white/20 px-1.5 text-xs font-medium text-white dark:bg-gray-900/20 dark:text-gray-900'
                        : 'inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gray-100 px-1.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $this->counts['vigentes'] }}
                    </span>
                @endif
            </button>

            {{-- Tab: Por vencer --}}
            <button
                wire:click="$set('tab', 'por_vencer')"
                class="{{ $tab === 'por_vencer'
                    ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900'
                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60' }} flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                Por vencer
                @if(isset($this->counts['por_vencer']) && $this->counts['por_vencer'] > 0)
                    <span class="{{ $tab === 'por_vencer'
                        ? 'inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-white/20 px-1.5 text-xs font-medium text-white dark:bg-gray-900/20 dark:text-gray-900'
                        : 'inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                        {{ $this->counts['por_vencer'] }}
                    </span>
                @endif
            </button>

            {{-- Tab: Terminados --}}
            <button
                wire:click="$set('tab', 'terminados')"
                class="{{ $tab === 'terminados'
                    ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900'
                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60' }} flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                Terminados
            </button>

            {{-- Tab: Todos --}}
            <button
                wire:click="$set('tab', 'todos')"
                class="{{ $tab === 'todos'
                    ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900'
                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60' }} flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                Todos
            </button>
        </div>
    </div>

    {{-- Flash --}}
    @if(session()->has('success'))
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 4000)"
             class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
             role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

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
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:table-cell">Inicio</th>
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:table-cell">Fin</th>
                    <th scope="col" class="hidden px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">Valor</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estado</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($this->contracts as $contract)
                    @php
                        $venceProximo = $contract->status === 'Vigente'
                            && $contract->end_date
                            && $contract->end_date->isFuture()
                            && $contract->end_date->diffInDays(now()) <= 30;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                                    {{ $contract->collaborator?->type === 'Empleado' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}
                                    text-xs font-semibold" aria-hidden="true">
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
                        <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 sm:table-cell">
                            {{ $contract->start_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="hidden px-4 py-3 sm:table-cell">
                            @if($contract->end_date)
                                <span class="{{ $venceProximo ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $contract->end_date->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-gray-500 dark:text-gray-500 text-xs italic">Indefinido</span>
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
                            @if($venceProximo)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    Por vencer
                                </span>
                            @elseif($contract->status === 'Vigente')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    Vigente
                                </span>
                            @elseif($contract->status === 'Liquidado')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                    Liquidado
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    {{ $contract->status ?? 'Terminado' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                {{-- Ver detalle --}}
                                @can('view', $contract)
                                    <div class="relative group inline-flex">
                                        <a href="{{ route('rh.contratos.show', $contract) }}"
                                           class="p-1.5 rounded-md text-blue-500 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-900/20 dark:hover:text-blue-300 transition-colors"
                                           aria-label="Ver detalle del contrato">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </a>
                                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Ver detalle</span>
                                    </div>
                                @endcan
                                {{-- Editar --}}
                                @can('update', $contract)
                                    <div class="relative group inline-flex">
                                        <a href="{{ route('rh.contratos.edit', $contract) }}"
                                           class="p-1.5 rounded-md text-amber-500 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-900/20 dark:hover:text-amber-300 transition-colors"
                                           aria-label="Editar contrato">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Editar</span>
                                    </div>
                                @endcan
                                {{-- Terminar --}}
                                @can('update', $contract)
                                    @if($contract->status === 'Vigente')
                                        <div class="relative group inline-flex">
                                            <button
                                                wire:click="terminate('{{ $contract->id }}')"
                                                wire:confirm="¿Desea terminar este contrato?"
                                                class="p-1.5 rounded-md text-yellow-600 hover:bg-yellow-50 hover:text-yellow-700 dark:text-yellow-400 dark:hover:bg-yellow-900/20 dark:hover:text-yellow-300 transition-colors"
                                                aria-label="Terminar contrato">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Terminar</span>
                                        </div>
                                    @endif
                                @endcan
                                {{-- Prorrogar --}}
                                @can('applyProroga', $contract)
                                    @if($contract->status === 'Vigente')
                                        <div class="relative group inline-flex">
                                            <button
                                                wire:click="$dispatch('open-proroga-modal', { contractId: '{{ $contract->id }}' })"
                                                class="p-1.5 rounded-md text-blue-500 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-900/20 dark:hover:text-blue-300 transition-colors"
                                                aria-label="Aplicar prórroga">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                                                </svg>
                                            </button>
                                            <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Prorrogar</span>
                                        </div>
                                    @endif
                                @endcan
                                {{-- Terminar anticipadamente --}}
                                @can('earlyTerminate', $contract)
                                    @if($contract->status === 'Vigente')
                                        <div class="relative group inline-flex">
                                            <button
                                                wire:click="$dispatch('open-early-termination-modal', { contractId: '{{ $contract->id }}' })"
                                                class="p-1.5 rounded-md text-orange-500 hover:bg-orange-50 hover:text-orange-700 dark:text-orange-400 dark:hover:bg-orange-900/20 dark:hover:text-orange-300 transition-colors"
                                                aria-label="Terminar anticipadamente">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                                </svg>
                                            </button>
                                            <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Terminar anticipadamente</span>
                                        </div>
                                    @endif
                                @endcan
                                {{-- Eliminar --}}
                                @can('delete', $contract)
                                    <div class="relative group inline-flex">
                                        <button
                                            wire:click="confirmDelete('{{ $contract->id }}')"
                                            class="p-1.5 rounded-md text-red-500 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300 transition-colors"
                                            aria-label="Eliminar contrato">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Eliminar</span>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()?->hasRole('contractor-manager') ? 7 : 8 }}" class="px-4 py-14 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    No hay contratos
                                    @if($tab === 'vigentes') vigentes
                                    @elseif($tab === 'por_vencer') por vencer
                                    @elseif($tab === 'terminados') terminados
                                    @endif
                                    @if($search) que coincidan con "{{ $search }}"@endif.
                                </p>
                            </div>
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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="modal-del-contrato">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="modal-del-contrato" class="text-base font-semibold text-gray-900 dark:text-white">Confirmar eliminación</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            ¿Eliminar el contrato de <strong class="text-gray-900 dark:text-white">{{ $deletingName }}</strong>? Esta acción no se puede deshacer.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="cancelDelete"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="delete" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60">
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: confirmar terminar contratos vencidos --}}
    @if($confirmingTerminateExpired)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="modal-terminar-vencidos">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                        <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="modal-terminar-vencidos" class="text-base font-semibold text-gray-900 dark:text-white">
                            Terminar contratos vencidos
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            ¿Está seguro de terminar los <strong class="text-gray-900 dark:text-white">{{ $this->expiredCount }} contrato(s) vencidos</strong>?
                            Esta acción no se puede deshacer.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="cancelTerminateExpired" type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button wire:click="terminateExpiredContracts" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-gray-800">
                        <span wire:loading wire:target="terminateExpiredContracts">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                        Sí, terminar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modales de prórroga y terminación anticipada --}}
    <livewire:rh.contract-proroga-modal />
    <livewire:rh.contract-early-termination-modal />
</div>
