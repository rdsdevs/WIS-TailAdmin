<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\RH\Position;
use App\Models\RH\PositionFunction;
use App\Models\RH\PositionEmail;

new class extends Component {
    use WithPagination;

    // ── Filtros y búsqueda ───────────────────────────────────────────────────
    public string $search       = '';
    public string $filterStatus = ''; // '' | 'active' | 'inactive'

    // ── Estado del modal de confirmación de eliminación ───────────────────────
    public bool $confirmingDelete  = false;
    public ?string $deleteTargetId = null;
    public string $deleteTargetName = '';

    // ── Reset de paginación al cambiar filtros ────────────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    // ── Datos auxiliares ─────────────────────────────────────────────────────
    public function with(): array
    {
        $institutionId = auth()->user()->institution_id;

        $base       = Position::query()->byInstitution($institutionId);
        $positionIds = (clone $base)->select('id');

        $total       = (clone $base)->count();
        $conFunciones = (clone $base)->whereHas('functions')->count();
        $conCorreos   = (clone $base)->whereHas('emails')->count();

        // Las stats siempre muestran totales globales de la institución,
        // independientemente del filtro de tab o búsqueda activo (diseño intencional).
        $stats = [
            'total'          => $total,
            'activos'        => (clone $base)->where('is_active', true)->count(),
            'inactivos'      => (clone $base)->where('is_active', false)->count(),
            'totalFunciones' => PositionFunction::whereIn('position_id', $positionIds)->count(),
            'conFunciones'   => $conFunciones,
            'sinFunciones'   => max(0, $total - $conFunciones),
            'totalCorreos'   => PositionEmail::whereIn('position_id', $positionIds)->count(),
            'conCorreos'     => $conCorreos,
            'sinCorreos'     => max(0, $total - $conCorreos),
            'nuevos30dias'   => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $positions = (clone $base)
            ->with(['emails', 'functions'])
            ->when($this->search,
                fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus === 'active',
                fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive',
                fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10);

        return compact('positions', 'stats');
    }

    // ── Acciones ─────────────────────────────────────────────────────────────
    public function toggleStatus(string $id): void
    {
        $position = Position::findOrFail($id);
        $this->authorize('update', $position);
        $position->is_active = ! $position->is_active;
        $position->save();
        $this->dispatch('notify', type: 'success', message: 'Estado del cargo actualizado.');
    }

    public function confirmDelete(string $id): void
    {
        $position = Position::findOrFail($id);
        $this->authorize('delete', $position);
        $this->deleteTargetId   = $id;
        $this->deleteTargetName = $position->name;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        if (! $this->deleteTargetId) {
            return;
        }

        $position = Position::findOrFail($this->deleteTargetId);
        $this->authorize('delete', $position);
        $position->delete();

        $this->confirmingDelete  = false;
        $this->deleteTargetId    = null;
        $this->deleteTargetName  = '';

        $this->dispatch('notify', type: 'success', message: 'Cargo eliminado correctamente.');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
        $this->deleteTargetId   = null;
        $this->deleteTargetName = '';
    }
};
?>

<div class="space-y-5">

    {{-- ── Stats Cards ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

        {{-- Widget 1: Total Cargos + Activos / Inactivos --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Cargos</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    {{ $stats['activos'] }} activos
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                    {{ $stats['inactivos'] }} inactivos
                </span>
            </div>
        </div>

        {{-- Widget 2: Total Funciones + Con / Sin funciones --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Funciones</p>
                    <p class="mt-1 text-2xl font-bold text-violet-600 dark:text-violet-400">{{ $stats['totalFunciones'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-500 dark:bg-violet-900/20 dark:text-violet-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
                    {{ $stats['conFunciones'] }} con funciones
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                    {{ $stats['sinFunciones'] }} sin funciones
                </span>
            </div>
        </div>

        {{-- Widget 3: Total Correos + Con / Sin correos --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Correos</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['totalCorreos'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-500 dark:bg-emerald-900/20 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                    {{ $stats['conCorreos'] }} con correos
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                    {{ $stats['sinCorreos'] }} sin correos
                </span>
            </div>
        </div>

        {{-- Widget 4: Nuevos en los últimos 30 días --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nuevos (30 días)</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['nuevos30dias'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-900/20 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                Cargos registrados desde el {{ now()->subDays(30)->format('d/m/Y') }}
            </p>
        </div>

    </div>

    {{-- ── Barra de Herramientas ────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
         x-data="{ searchOpen: false }">

        {{-- Búsqueda expandible --}}
        <div class="flex items-center gap-2">
            <button
                @click="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $refs.searchInput.focus())"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-gray-50 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            <div x-show="searchOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-x-2"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="relative">
                <input
                    x-ref="searchInput"
                    wire:model.live.debounce.400ms="search"
                    @keydown.escape="searchOpen = false; $wire.set('search', '')"
                    type="text"
                    placeholder="Buscar cargo..."
                    class="h-9 w-56 rounded-lg border border-gray-300 bg-white pl-3 pr-8 text-sm text-gray-900 shadow-sm placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                <button
                    @click="searchOpen = false; $wire.set('search', '')"
                    class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Botones de acción --}}
        <div class="flex items-center divide-x divide-gray-200 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800">

            @can('create', App\Models\RH\Position::class)
            <a href="{{ route('rh.cargos.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50 transition-colors">
                <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Cargo
            </a>
            @endcan

            @can('import', App\Models\RH\Position::class)
            <a href="{{ route('rh.cargos.importar') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50 transition-colors">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                </svg>
                Importar
            </a>
            @endcan

        </div>
    </div>

    {{-- ── Tabs de Filtro ───────────────────────────────────────────────────── --}}
    <div class="flex items-center divide-x divide-gray-200 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800 w-fit">

        <button wire:click="$set('filterStatus', '')"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors
                       {{ $filterStatus === '' ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50' }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z"/>
            </svg>
            Todos
            <span class="rounded-full bg-gray-200 px-1.5 py-0.5 text-xs leading-none dark:bg-gray-600 dark:text-gray-300">
                {{ $stats['total'] }}
            </span>
        </button>

        <button wire:click="$set('filterStatus', 'active')"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors
                       {{ $filterStatus === 'active' ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50' }}">
            <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            Activos
            <span class="rounded-full bg-green-100 px-1.5 py-0.5 text-xs leading-none text-green-700 dark:bg-green-900/30 dark:text-green-400">
                {{ $stats['activos'] }}
            </span>
        </button>

        <button wire:click="$set('filterStatus', 'inactive')"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors
                       {{ $filterStatus === 'inactive' ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50' }}">
            <svg class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            Inactivos
            <span class="rounded-full bg-red-100 px-1.5 py-0.5 text-xs leading-none text-red-700 dark:bg-red-900/30 dark:text-red-400">
                {{ $stats['inactivos'] }}
            </span>
        </button>

    </div>

    {{-- ── Tabla de Cargos ──────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                <tr>
                    <th class="px-6 py-4">Cargo</th>
                    <th class="px-6 py-4">Correos / Funciones</th>
                    <th class="px-6 py-4 text-center">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($positions as $pos)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $pos->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $pos->emails->count() }} correos
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    {{ $pos->functions->count() }} funciones
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="toggleStatus('{{ $pos->id }}')" class="focus:outline-none">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pos->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $pos->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @can('update', $pos)
                                <a href="{{ route('rh.cargos.edit', $pos) }}" title="Editar cargo"
                                    class="rounded-lg p-1.5 text-blue-600 hover:bg-blue-50 hover:text-blue-900 dark:text-blue-400 dark:hover:bg-blue-900/20 dark:hover:text-blue-300">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endcan
                                @can('delete', $pos)
                                <button wire:click="confirmDelete('{{ $pos->id }}')" title="Eliminar cargo"
                                    class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-500">
                                <svg class="h-10 w-10 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z"/>
                                </svg>
                                <p class="text-sm">No se encontraron cargos{{ $search ? ' con ese nombre' : '' }}.</p>
                                @can('create', App\Models\RH\Position::class)
                                <a href="{{ route('rh.cargos.create') }}" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    Crear el primer cargo
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $positions->links() }}
        </div>
    </div>

    {{-- ── Modal de Confirmación de Eliminación ────────────────────────────── --}}
    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-delete-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true"></div>

            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md dark:bg-gray-800">
                <div class="px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white" id="modal-delete-title">
                                Eliminar cargo
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                ¿Está seguro de que desea eliminar el cargo
                                <span class="font-medium text-gray-900 dark:text-white">"{{ $deleteTargetName }}"</span>?
                                Esta acción no se puede deshacer.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/50 flex justify-end gap-3">
                    <button wire:click="cancelDelete"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button wire:click="delete"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
