<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Certificados\Certificate;

new class extends Component {
    use WithPagination;

    public string $search    = '';
    public string $filterTipo = ''; // '' | 'empleado' | 'contratista'
    public string $filterFrom = '';
    public string $filterTo   = '';
    public ?string $deletingId   = null;
    public ?string $deletingName = null;

    public function updatingSearch(): void   { $this->resetPage(); }
    public function updatingFilterTipo(): void { $this->resetPage(); }
    public function updatingFilterFrom(): void { $this->resetPage(); }
    public function updatingFilterTo(): void  { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->filterTipo = '';
        $this->filterFrom = '';
        $this->filterTo   = '';
        $this->search     = '';
        $this->resetPage();
    }

    public function confirmDelete(string $id): void
    {
        $cert = Certificate::where('institution_id', auth()->user()?->institution_id)
            ->findOrFail($id);
        $snap = $cert->collaborator_snapshot ?? [];
        $this->deletingId   = $id;
        $this->deletingName = $snap['full_name'] ?? $snap['company_name'] ?? 'este certificado';
    }

    public function cancelDelete(): void
    {
        $this->deletingId   = null;
        $this->deletingName = null;
    }

    public function delete(): void
    {
        $cert = Certificate::where('id', $this->deletingId)
            ->where('institution_id', auth()->user()?->institution_id)
            ->firstOrFail();

        $this->authorize('delete', $cert);
        $cert->delete();
        $this->deletingId   = null;
        $this->deletingName = null;
        session()->flash('exito', 'Certificado eliminado del historial.');
        $this->dispatch('$refresh');
    }

    public function getCertificatesProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $institutionId = auth()->user()?->institution_id;

        return Certificate::query()
            ->where('institution_id', $institutionId)
            ->with(['collaborator', 'signature', 'issuedBy'])
            ->when($this->search, function ($q): void {
                $buscar = $this->search;
                $q->whereHas('collaborator', fn ($q2) =>
                    $q2->where('first_name', 'like', "%{$buscar}%")
                       ->orWhere('first_surname', 'like', "%{$buscar}%")
                       ->orWhere('second_surname', 'like', "%{$buscar}%")
                       ->orWhere('document_number', 'like', "%{$buscar}%")
                       ->orWhere('company_name', 'like', "%{$buscar}%")
                );
            })
            ->when($this->filterTipo, fn ($q) => $q->where('certificate_type', $this->filterTipo))
            ->when($this->filterFrom, fn ($q) => $q->where('issued_at', '>=', $this->filterFrom . ' 00:00:00'))
            ->when($this->filterTo,   fn ($q) => $q->where('issued_at', '<=', $this->filterTo . ' 23:59:59'))
            ->orderByDesc('issued_at')
            ->paginate(15);
    }
};
?>

<div>
    {{-- Barra de herramientas --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">

        {{-- Búsqueda --}}
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="Buscar por nombre o documento..."
                   class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500" />
        </div>

        {{-- Tipo --}}
        <select wire:model.live="filterTipo"
                class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <option value="">Todos los tipos</option>
            <option value="empleado">Laboral</option>
            <option value="contratista">Contratación</option>
        </select>

        {{-- Desde --}}
        <input wire:model.live="filterFrom" type="date"
               class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" />

        {{-- Hasta --}}
        <input wire:model.live="filterTo" type="date"
               class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" />

        @if ($search || $filterTipo || $filterFrom || $filterTo)
            <button wire:click="clearFilters"
                    class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Limpiar
            </button>
        @endif

        {{-- Botón Generar --}}
        @canany(['generateEmployee', 'generateContractor'], \App\Models\Certificados\Certificate::class)
            <button onclick="document.getElementById('modal-generar-cert').dispatchEvent(new CustomEvent('open-modal'))"
                    class="ml-auto inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Generar certificado
            </button>
        @endcanany
    </div>

    {{-- Tabla --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if ($this->certificates->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">No se encontraron certificados</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Intente cambiar los filtros o genere un nuevo certificado.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Colaborador</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dirigido a</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Emitido</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Por</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach ($this->certificates as $cert)
                            @php
                                $snap = $cert->collaborator_snapshot ?? [];
                                $nombreCol = $snap['is_company'] ?? false
                                    ? ($snap['company_name'] ?? $snap['full_name'] ?? '—')
                                    : ($snap['full_name'] ?? '—');
                                $docNum = $snap['document_number'] ?? '';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ strtoupper($nombreCol) }}</div>
                                    @if ($docNum)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $snap['document_type_name'] ?? 'C.C.' }} {{ number_format((float) $docNum, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($cert->certificate_type === 'empleado')
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            Laboral
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                            Contratación
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $cert->addressed_to ?? 'A quien interese' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $cert->issued_at?->setTimezone('America/Bogota')->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $cert->issuedBy?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Verificar --}}
                                        <div class="relative group">
                                            <a href="{{ route('certificados.verificar', $cert->verification_code) }}"
                                               target="_blank"
                                               class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-blue-300 hover:text-blue-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:text-blue-400">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                </svg>
                                            </a>
                                            <div class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                                                Ver verificación
                                            </div>
                                        </div>

                                        {{-- Re-descargar PDF --}}
                                        <div class="relative group">
                                            <a href="{{ route('certificados.verificar', $cert->verification_code) }}"
                                               target="_blank"
                                               class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-emerald-300 hover:text-emerald-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:text-emerald-400">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                            </a>
                                            <div class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                                                Verificar en línea
                                            </div>
                                        </div>

                                        {{-- Eliminar --}}
                                        @can('delete', $cert)
                                            <div class="relative group">
                                                <button wire:click="confirmDelete('{{ $cert->id }}')"
                                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-red-300 hover:text-red-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:text-red-400">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                                <div class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                                                    Eliminar
                                                </div>
                                            </div>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if ($this->certificates->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    {{ $this->certificates->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Modal confirmación eliminar --}}
    @if ($deletingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:key="delete-modal">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Eliminar certificado</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <p class="mb-6 text-sm text-gray-700 dark:text-gray-300">
                    ¿Está seguro que desea eliminar el certificado de <strong>{{ $deletingName }}</strong> del historial?
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelDelete"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="delete"
                            class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal generar certificado --}}
    <livewire:certificados.generate-certificate-modal id="modal-generar-cert" />
</div>
