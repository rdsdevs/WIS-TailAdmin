<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterRole = '';
    public int $perPage = 15;
    public ?string $deletingId = null;
    public ?string $deletingName = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
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
        $usuario = User::findOrFail($this->deletingId);
        $this->authorize('delete', $usuario);

        $usuario->delete();

        $this->deletingId   = null;
        $this->deletingName = null;

        session()->flash('exito', 'Usuario eliminado correctamente.');
    }

    public function getUsuariosProperty()
    {
        $isSuperAdmin = auth()->user()->hasRole('super-admin');

        return User::query()
            ->with(['institution', 'roles'])
            ->when(! $isSuperAdmin, fn ($q) => $q->where('institution_id', auth()->user()->institution_id))
            ->when($this->search, fn ($q) => $q->where(
                fn ($q2) => $q2
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('document_number', 'like', "%{$this->search}%")
            ))
            ->when($this->filterRole, fn ($q) => $q->whereHas(
                'roles',
                fn ($q2) => $q2->where('name', $this->filterRole)
            ))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function getRolesProperty()
    {
        return \Spatie\Permission\Models\Role::orderBy('name')->get();
    }

    public function getIsSuperAdminProperty(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }
};
?>

<div>
    {{-- Barra superior: búsqueda + filtros + acciones --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        {{-- Búsqueda --}}
        <div class="relative flex-1 sm:max-w-xs">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-gray-500" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input
                wire:model.live.debounce.400ms="search"
                type="search"
                placeholder="Buscar por nombre, correo o documento..."
                aria-label="Buscar usuarios"
                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
            />
            <div wire:loading wire:target="search" class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                <svg class="h-4 w-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        </div>

        {{-- Filtros + acciones --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Filtro por rol --}}
            <label for="filtro-rol" class="sr-only">Filtrar por rol</label>
            <select
                wire:model.live="filterRole"
                id="filtro-rol"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="">Todos los roles</option>
                @foreach($this->roles as $rol)
                    <option value="{{ $rol->name }}">{{ $rol->name }}</option>
                @endforeach
            </select>

            @can('create', \App\Models\User::class)
                <a href="{{ route('admin.usuarios.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo usuario
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash de éxito --}}
    @if(session()->has('exito'))
        <div
            x-data="{ visible: true }"
            x-show="visible"
            x-init="setTimeout(() => visible = false, 4000)"
            class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
            role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            {{ session('exito') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700" wire:loading.class="opacity-60">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Usuario</th>
                    <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:table-cell">Documento</th>
                    @if($this->isSuperAdmin)
                        <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">Institución</th>
                    @endif
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Roles</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estado</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($this->usuarios as $usuario)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        {{-- Usuario --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 text-sm font-semibold" aria-hidden="true">
                                    {{ strtoupper(substr($usuario->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate">
                                        {{ $usuario->name }}
                                    </p>
                                    @if($usuario->email)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[180px]">
                                            {{ $usuario->email }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Documento --}}
                        <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 sm:table-cell">
                            <span class="inline-flex items-center gap-1">
                                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $usuario->document_type }}
                                </span>
                                {{ $usuario->document_number }}
                            </span>
                        </td>

                        {{-- Institución (solo super-admin) --}}
                        @if($this->isSuperAdmin)
                            <td class="hidden px-4 py-3 text-sm text-gray-600 dark:text-gray-400 lg:table-cell">
                                {{ $usuario->institution?->name ?? '—' }}
                            </td>
                        @endif

                        {{-- Roles --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse($usuario->roles as $rol)
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ $rol->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Sin rol</span>
                                @endforelse
                            </div>
                        </td>

                        {{-- Estado --}}
                        <td class="px-4 py-3">
                            @if($usuario->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        {{-- Acciones --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.usuarios.show', $usuario) }}"
                                   class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                   aria-label="Ver perfil de {{ $usuario->name }}">
                                    Ver
                                </a>
                                @can('update', $usuario)
                                    <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                                       class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                       aria-label="Editar {{ $usuario->name }}">
                                        Editar
                                    </a>
                                @endcan
                                @can('delete', $usuario)
                                    <button
                                        wire:click="confirmDelete('{{ $usuario->id }}', '{{ addslashes($usuario->name) }}')"
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                        aria-label="Eliminar {{ $usuario->name }}">
                                        Eliminar
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $this->isSuperAdmin ? 6 : 5 }}" class="px-4 py-14 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        @if($search || $filterRole)
                                            No se encontraron usuarios con los filtros aplicados.
                                        @else
                                            No hay usuarios registrados aún.
                                        @endif
                                    </p>
                                    @if(!$search && !$filterRole)
                                        @can('create', \App\Models\User::class)
                                            <a href="{{ route('admin.usuarios.create') }}"
                                               class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                Registrar primer usuario
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
    @if($this->usuarios->hasPages())
        <div class="mt-4">
            {{ $this->usuarios->links() }}
        </div>
    @endif

    {{-- Modal de confirmación de eliminación --}}
    @if($deletingId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-delete-usuario">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="modal-delete-usuario" class="text-base font-semibold text-gray-900 dark:text-white">
                            Confirmar eliminación
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            ¿Está seguro de eliminar al usuario <strong class="text-gray-900 dark:text-white">{{ $deletingName }}</strong>?
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
