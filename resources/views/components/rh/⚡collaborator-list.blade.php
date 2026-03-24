<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';
    public ?string $deletingId = null;
    public ?string $deletingName = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(string $id, string $name): void
    {
        $this->deletingId   = $id;
        $this->deletingName = $name;
    }

    public function cancelDelete(): void
    {
        $this->deletingId   = null;
        $this->deletingName = null;
    }

    public function delete(): void
    {
        $collaborator = Collaborator::where('id', $this->deletingId)
            ->where('institution_id', auth()->user()?->institution_id)
            ->firstOrFail();

        $this->authorize('delete', $collaborator);
        $collaborator->delete();

        $this->deletingId   = null;
        $this->deletingName = null;

        session()->flash('success', 'Colaborador eliminado correctamente.');
    }

    public function getCollaboratorsProperty(): LengthAwarePaginator
    {
        $institutionId = auth()->user()?->institution_id;

        return Collaborator::query()
            ->where('institution_id', $institutionId)
            ->with(['documentType', 'status', 'activeContract.position'])
            ->when($this->search, fn ($q) => $q->where(
                fn ($q2) => $q2
                    ->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('first_surname', 'like', "%{$this->search}%")
                    ->orWhere('second_surname', 'like', "%{$this->search}%")
                    ->orWhere('company_name', 'like', "%{$this->search}%")
                    ->orWhere('document_number', 'like', "%{$this->search}%")
            ))
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status_id', $this->filterStatus))
            ->orderBy('first_surname')
            ->orderBy('first_name')
            ->paginate(10);
    }

    public function getStatusesProperty(): Collection
    {
        return CollaboratorStatus::orderBy('name')->get();
    }
};
?>

<div>
    {{-- Barra superior: búsqueda + filtros + acciones --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
                    placeholder="Buscar por nombre o cédula..."
                    aria-label="Buscar colaboradores"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-3 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                />
            </div>
        </div>

        {{-- Filtro estado --}}
        <div class="flex items-center gap-2">
            <label for="filtro-estado" class="sr-only">Filtrar por estado</label>
            <select
                wire:model.live="filterStatus"
                id="filtro-estado"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="">Todos los estados</option>
                @foreach($this->statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </select>

            @can('create', \App\Models\RH\Collaborator::class)
                <a href="{{ route('rh.colaboradores.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo colaborador
                </a>
            @endcan

            @can('create', \App\Models\RH\Collaborator::class)
                <a href="{{ route('rh.colaboradores.export', ['tipo' => $filterType ?: 'todos']) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Exportar
                </a>
            @endcan

            @can('import', \App\Models\RH\Collaborator::class)
                <a href="{{ route('rh.colaboradores.importar') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-green-300 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Importar
                </a>
            @endcan
        </div>
    </div>

    {{-- Tabs tipo de colaborador --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <button
            wire:click="$set('filterType', '')"
            class="{{ $filterType === ''
                ? 'rounded-lg border border-gray-900 bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 dark:border-gray-200 dark:text-white'
                : 'rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' }}">
            Todos
        </button>
        <button
            wire:click="$set('filterType', 'Empleado')"
            class="{{ $filterType === 'Empleado'
                ? 'rounded-lg border border-gray-900 bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 dark:border-gray-200 dark:text-white'
                : 'rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' }}">
            Empleados
        </button>
        <button
            wire:click="$set('filterType', 'Contratista')"
            class="{{ $filterType === 'Contratista'
                ? 'rounded-lg border border-gray-900 bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 dark:border-gray-200 dark:text-white'
                : 'rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' }}">
            Contratistas
        </button>
    </div>

    {{-- Flash de éxito --}}
    @if(session()->has('success'))
        <div
            x-data="{ visible: true }"
            x-show="visible"
            x-init="setTimeout(() => visible = false, 4000)"
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
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tipo</th>
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">Cargo actual</th>
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:table-cell">Contacto</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estado</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($this->collaborators as $collaborator)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        {{-- Colaborador --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full
                                    {{ $collaborator->type === 'Empleado' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' }}
                                    text-sm font-semibold" aria-hidden="true">
                                    @if($collaborator->is_company)
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                    @else
                                        {{ strtoupper(substr($collaborator->first_name ?? '', 0, 1) . substr($collaborator->first_surname ?? '', 0, 1)) }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate">
                                        {{ $collaborator->full_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $collaborator->documentType?->code ?? 'CC' }} {{ $collaborator->document_number }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- Tipo --}}
                        <td class="px-4 py-3">
                            @if($collaborator->type === 'Empleado')
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    Empleado
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                    Contratista
                                </span>
                            @endif
                        </td>

                        {{-- Cargo actual --}}
                        <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 md:table-cell">
                            {{ $collaborator->activeContract?->position?->name ?? '—' }}
                        </td>

                        {{-- Contacto --}}
                        <td class="hidden px-4 py-3 sm:table-cell">
                            @if($collaborator->email)
                                <a href="mailto:{{ $collaborator->email }}" class="block text-xs text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 truncate max-w-[160px]">
                                    {{ $collaborator->email }}
                                </a>
                            @endif
                            @if($collaborator->phone)
                                <a href="tel:{{ $collaborator->phone }}" class="block text-xs text-gray-500 dark:text-gray-500">
                                    {{ $collaborator->phone }}
                                </a>
                            @endif
                        </td>

                        {{-- Estado --}}
                        <td class="px-4 py-3">
                            @if($collaborator->status)
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    <i class="{{ $collaborator->status->icon_class }}" aria-hidden="true"></i>
                                    {{ $collaborator->status->name }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>

                        {{-- Acciones --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                {{-- Ver --}}
                                <div class="relative group inline-flex">
                                    <a href="{{ route('rh.colaboradores.show', $collaborator) }}"
                                       class="p-1.5 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors"
                                       aria-label="Ver perfil de {{ $collaborator->full_name }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                    <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Ver</span>
                                </div>
                                {{-- Editar --}}
                                @can('update', $collaborator)
                                    <div class="relative group inline-flex">
                                        <a href="{{ route('rh.colaboradores.edit', $collaborator) }}"
                                           class="p-1.5 rounded-md text-amber-500 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-900/20 dark:hover:text-amber-300 transition-colors"
                                           aria-label="Editar {{ $collaborator->full_name }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Editar</span>
                                    </div>
                                @endcan
                                {{-- Nuevo contrato --}}
                                @can('create', \App\Models\RH\Contract::class)
                                    <div class="relative group inline-flex">
                                        <a href="{{ route('rh.contratos.create', ['collaborator_id' => $collaborator->id]) }}"
                                           class="p-1.5 rounded-md text-green-600 hover:bg-green-50 hover:text-green-700 dark:text-green-400 dark:hover:bg-green-900/20 dark:hover:text-green-300 transition-colors"
                                           aria-label="Nuevo contrato para {{ $collaborator->full_name }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </a>
                                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity dark:bg-gray-700 z-10">Contrato</span>
                                    </div>
                                @endcan
                                {{-- Eliminar --}}
                                @can('delete', $collaborator)
                                    <div class="relative group inline-flex">
                                        <button
                                            wire:click="confirmDelete('{{ $collaborator->id }}', '{{ addslashes($collaborator->full_name) }}')"
                                            class="p-1.5 rounded-md text-red-500 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300 transition-colors"
                                            aria-label="Eliminar {{ $collaborator->full_name }}">
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
                        <td colspan="6" class="px-4 py-14 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        @if($search || $filterType || $filterStatus)
                                            No se encontraron colaboradores con los filtros aplicados.
                                        @else
                                            No hay colaboradores registrados aún.
                                        @endif
                                    </p>
                                    @if(!$search && !$filterType && !$filterStatus)
                                        @can('create', \App\Models\RH\Collaborator::class)
                                            <a href="{{ route('rh.colaboradores.create') }}"
                                               class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                Registrar primer colaborador
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($this->collaborators->hasPages())
        <div class="mt-4">
            {{ $this->collaborators->links() }}
        </div>
    @endif

    {{-- Modal de confirmación de eliminación --}}
    @if($deletingId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-delete-collaborator">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="modal-delete-collaborator" class="text-base font-semibold text-gray-900 dark:text-white">
                            Confirmar eliminación
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            ¿Está seguro de eliminar a <strong class="text-gray-900 dark:text-white">{{ $deletingName }}</strong>?
                            Esta acción no se puede deshacer.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button
                        wire:click="cancelDelete"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button
                        wire:click="delete"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60">
                        <span wire:loading wire:target="delete">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
