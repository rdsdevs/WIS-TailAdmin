<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RH\Employee;
use App\Models\RH\Department;

new class extends Component {
    use WithPagination;

    public string $buscar = '';
    public string $estado = 'activo';
    public string $departamento_id = '';

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    public function updatingDepartamentoId(): void
    {
        $this->resetPage();
    }

    public function eliminar(string $id): void
    {
        $empleado = Employee::findOrFail($id);
        $empleado->delete();
        $this->dispatch('notificacion', mensaje: 'Empleado eliminado correctamente.');
    }

    public function render(): \Illuminate\View\View
    {
        $departamentos = Department::orderBy('name')->get();

        $empleados = Employee::query()
            ->with(['position', 'department'])
            ->when($this->buscar, fn ($q) =>
                $q->where('first_name', 'like', "%{$this->buscar}%")
                  ->orWhere('last_name', 'like', "%{$this->buscar}%")
                  ->orWhere('document_number', 'like', "%{$this->buscar}%")
            )
            ->when($this->departamento_id, fn ($q) =>
                $q->where('department_id', $this->departamento_id)
            )
            ->when($this->estado === 'activo', fn ($q) => $q->where('is_active', true))
            ->when($this->estado === 'inactivo', fn ($q) => $q->where('is_active', false))
            ->orderBy('first_name')
            ->paginate(15);

        return view('livewire.rh.employee-list', compact('empleados', 'departamentos'));
    }
};
?>

<div>
    {{-- Barra de herramientas --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        {{-- Filtros --}}
        <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">

            {{-- Búsqueda --}}
            <div class="relative w-full sm:max-w-xs">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-gray-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
                <input
                    wire:model.live.debounce.300ms="buscar"
                    type="search"
                    placeholder="Buscar por nombre o cédula..."
                    aria-label="Buscar empleados"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400"
                />
            </div>

            {{-- Filtro estado --}}
            <select
                wire:model.live="estado"
                aria-label="Filtrar por estado"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="todos">Todos los estados</option>
                <option value="activo">Activos</option>
                <option value="inactivo">Inactivos</option>
            </select>

            {{-- Filtro departamento --}}
            <select
                wire:model.live="departamento_id"
                aria-label="Filtrar por departamento"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="">Todos los departamentos</option>
                @foreach($departamentos as $dep)
                    <option value="{{ $dep->id }}">{{ $dep->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Acciones principales --}}
        <div class="flex items-center gap-2 shrink-0">
            {{-- Exportar Excel --}}
            <a href="{{ route('rh.empleados.export') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
               title="Exportar a Excel">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span class="hidden sm:inline">Exportar</span>
            </a>

            {{-- Nuevo empleado --}}
            @can('create', \App\Models\RH\Employee::class)
                <a href="{{ route('rh.empleados.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo empleado
                </a>
            @endcan
        </div>
    </div>

    {{-- Indicador de carga --}}
    <div wire:loading.delay class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Cargando...
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700" wire:loading.class="opacity-60 pointer-events-none">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Empleado
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Cargo
                    </th>
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">
                        Departamento
                    </th>
                    <th scope="col" class="hidden px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">
                        Salario
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Estado
                    </th>
                    <th scope="col" class="px-4 py-3">
                        <span class="sr-only">Acciones</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($empleados as $empleado)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        {{-- Empleado --}}
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $empleado->full_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">CC {{ $empleado->document_number }}</p>
                            </div>
                        </td>
                        {{-- Cargo --}}
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $empleado->position?->name ?? '—' }}
                        </td>
                        {{-- Departamento --}}
                        <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 md:table-cell">
                            {{ $empleado->department?->name ?? '—' }}
                        </td>
                        {{-- Salario --}}
                        <td class="hidden px-4 py-3 text-right text-gray-600 dark:text-gray-400 lg:table-cell">
                            @if($empleado->salary)
                                $ {{ number_format($empleado->salary, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        {{-- Estado --}}
                        <td class="px-4 py-3">
                            @if($empleado->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    Inactivo
                                </span>
                            @endif
                        </td>
                        {{-- Acciones --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Ver --}}
                                <a href="{{ route('rh.empleados.show', $empleado) }}"
                                   class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                   title="Ver detalle">
                                    Ver
                                </a>

                                {{-- Editar --}}
                                @can('update', $empleado)
                                    <a href="{{ route('rh.empleados.edit', $empleado) }}"
                                       class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                       title="Editar">
                                        Editar
                                    </a>
                                @endcan

                                {{-- Eliminar con confirmación --}}
                                @can('delete', $empleado)
                                    <div x-data="{ abierto: false }">
                                        <button
                                            @click="abierto = true"
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                            title="Eliminar">
                                            Eliminar
                                        </button>

                                        {{-- Modal confirmación --}}
                                        <div
                                            x-show="abierto"
                                            x-cloak
                                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                                            @keydown.escape.window="abierto = false"
                                            role="dialog"
                                            aria-modal="true"
                                            aria-labelledby="modal-titulo-{{ $empleado->id }}">
                                            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                                                <h3 id="modal-titulo-{{ $empleado->id }}"
                                                    class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    Confirmar eliminación
                                                </h3>
                                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                    ¿Está seguro de que desea eliminar a
                                                    <strong class="text-gray-900 dark:text-white">{{ $empleado->full_name }}</strong>?
                                                    Esta acción no se puede deshacer.
                                                </p>
                                                <div class="mt-5 flex justify-end gap-3">
                                                    <button
                                                        @click="abierto = false"
                                                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                                        Cancelar
                                                    </button>
                                                    <button
                                                        wire:click="eliminar('{{ $empleado->id }}')"
                                                        @click="abierto = false"
                                                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                                        Sí, eliminar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No se encontraron empleados.</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Intente ajustar los filtros de búsqueda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($empleados->hasPages())
        <div class="mt-4">
            {{ $empleados->links() }}
        </div>
    @endif
</div>
