<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RH\Contract;
use Illuminate\Support\Carbon;

new class extends Component {
    use WithPagination;

    public string $tab      = 'vigentes'; // vigentes | por_vencer | terminados | todos
    public string $search   = '';
    public ?string $deletingId   = null;
    public ?string $deletingName = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTab(): void
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
        $this->authorize('delete', Contract::class);
        Contract::findOrFail($this->deletingId)->delete();
        $this->deletingId   = null;
        $this->deletingName = null;
        session()->flash('success', 'Contrato eliminado correctamente.');
    }

    public function terminate(string $id): void
    {
        $contract = Contract::findOrFail($id);
        $this->authorize('update', $contract);
        $contract->update(['status' => 'Terminado']);
        session()->flash('success', 'Contrato terminado correctamente.');
    }

    public function getContractsProperty()
    {
        $query = Contract::query()
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

        return $query->orderByDesc('start_date')->paginate(15);
    }

    public function getCountsProperty(): array
    {
        return [
            'vigentes'   => Contract::where('status', 'Vigente')->count(),
            'por_vencer' => Contract::where('status', 'Vigente')
                                    ->whereNotNull('end_date')
                                    ->where('end_date', '<=', now()->addDays(30))
                                    ->where('end_date', '>=', now())
                                    ->count(),
        ];
    }
};
?>

<div>
    {{-- Barra de búsqueda --}}
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="relative flex-1 sm:max-w-xs">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-gray-500" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input
                wire:model.live.debounce.400ms="search"
                type="search"
                placeholder="Buscar por colaborador o cédula..."
                aria-label="Buscar contratos"
                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
            />
        </div>
        @can('create', \App\Models\RH\Contract::class)
            <a href="{{ route('rh.contratos.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo contrato
            </a>
        @endcan
    </div>

    {{-- Tabs --}}
    <div class="mb-4 flex items-center gap-1 overflow-x-auto border-b border-gray-200 dark:border-gray-700">
        @foreach([
            ['key' => 'vigentes',   'label' => 'Vigentes',   'color' => 'blue'],
            ['key' => 'por_vencer', 'label' => 'Por vencer', 'color' => 'red'],
            ['key' => 'terminados', 'label' => 'Terminados', 'color' => 'gray'],
            ['key' => 'todos',      'label' => 'Todos',      'color' => 'blue'],
        ] as $t)
            <button
                wire:click="$set('tab', '{{ $t['key'] }}')"
                class="whitespace-nowrap px-4 py-2.5 text-sm font-medium focus:outline-none transition-colors
                    {{ $tab === $t['key']
                        ? 'border-b-2 border-' . $t['color'] . '-600 text-' . $t['color'] . '-600 dark:text-' . $t['color'] . '-400 dark:border-' . $t['color'] . '-400'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                {{ $t['label'] }}
                @if(isset($this->counts[$t['key']]) && $this->counts[$t['key']] > 0)
                    <span class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs font-medium
                        {{ $t['key'] === 'por_vencer'
                            ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                            : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' }}">
                        {{ $this->counts[$t['key']] }}
                    </span>
                @endif
            </button>
        @endforeach
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
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">Cargo</th>
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
                        <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 md:table-cell">
                            {{ $contract->position?->name ?? '—' }}
                        </td>
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
                            @if($contract->salary)
                                $ {{ number_format((float)$contract->salary, 0, ',', '.') }}
                            @elseif($contract->fees)
                                $ {{ number_format((float)$contract->fees, 0, ',', '.') }}
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
                            <div class="flex items-center justify-end gap-1">
                                @can('update', $contract)
                                    <a href="{{ route('rh.contratos.edit', $contract) }}"
                                       class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                       aria-label="Editar contrato">
                                        Editar
                                    </a>
                                @endcan
                                @can('update', $contract)
                                    @if($contract->status === 'Vigente')
                                        <button
                                            wire:click="terminate('{{ $contract->id }}')"
                                            wire:confirm="¿Desea terminar este contrato?"
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                            aria-label="Terminar contrato">
                                            Terminar
                                        </button>
                                    @endif
                                @endcan
                                @can('delete', $contract)
                                    <button
                                        wire:click="confirmDelete('{{ $contract->id }}', '{{ addslashes($contract->collaborator?->full_name ?? 'este contrato') }}')"
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                        aria-label="Eliminar contrato">
                                        Eliminar
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-14 text-center">
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
</div>
