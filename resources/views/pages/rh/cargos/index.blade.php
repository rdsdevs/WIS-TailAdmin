@extends('layouts.app')

@section('content')
    {{-- Breadcrumb --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Cargos</h2>
        <nav aria-label="Migas de pan">
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Inicio
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">Cargos</li>
            </ol>
        </nav>
    </div>

    {{-- Notificación de sesión --}}
    @if(session('exito'))
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 4000)"
             class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
             role="alert">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            {{ session('exito') }}
        </div>
    @endif

    @include('layouts.partials.rh-subnav')

    {{-- Estado y modal Alpine --}}
    <div
        x-data="{
            modalAbierto: false,
            modoEdicion: false,
            cargo: { id: '', nombre: '', descripcion: '', department_id: '' },
            abrirCrear() {
                this.modoEdicion = false;
                this.cargo = { id: '', nombre: '', descripcion: '', department_id: '' };
                this.modalAbierto = true;
            },
            abrirEditar(id, nombre, descripcion, department_id) {
                this.modoEdicion = true;
                this.cargo = { id, nombre, descripcion, department_id };
                this.modalAbierto = true;
            }
        }">

        {{-- Barra superior: búsqueda + botón nuevo --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            {{-- Filtro por departamento --}}
            <form method="GET" action="{{ route('rh.cargos.index') }}" class="flex items-center gap-2">
                <label for="filtro-departamento" class="sr-only">Filtrar por departamento</label>
                <select
                    id="filtro-departamento"
                    name="department_id"
                    onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Todos los departamentos</option>
                    @foreach($departamentos ?? [] as $dep)
                        <option value="{{ $dep->id }}" {{ request('department_id') === $dep->id ? 'selected' : '' }}>
                            {{ $dep->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            @can('create', \App\Models\RH\Position::class)
                <button
                    @click="abrirCrear()"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo cargo
                </button>
            @endcan
        </div>

        {{-- Tabla de cargos --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Nombre del cargo
                        </th>
                        <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">
                            Departamento
                        </th>
                        <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">
                            Descripción
                        </th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Empleados
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
                    @forelse($cargos as $cargo)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $cargo->name }}
                            </td>
                            <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 md:table-cell">
                                {{ $cargo->department?->name ?? '—' }}
                            </td>
                            <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 lg:table-cell">
                                {{ $cargo->description ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center rounded-full bg-blue-50 px-3 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                                    {{ $cargo->contracts_count ?? $cargo->contracts->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($cargo->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @can('update', $cargo)
                                        <button
                                            @click="abrirEditar(
                                                '{{ $cargo->id }}',
                                                '{{ addslashes($cargo->name) }}',
                                                '{{ addslashes($cargo->description ?? '') }}',
                                                '{{ $cargo->department_id ?? '' }}'
                                            )"
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                                            Editar
                                        </button>
                                    @endcan

                                    @can('delete', $cargo)
                                        <div x-data="{ confirmar: false }">
                                            <button
                                                @click="confirmar = true"
                                                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                                Eliminar
                                            </button>

                                            <div
                                                x-show="confirmar"
                                                x-cloak
                                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                                                @keydown.escape.window="confirmar = false"
                                                role="dialog"
                                                aria-modal="true"
                                                aria-labelledby="modal-del-cargo-{{ $cargo->id }}">
                                                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                                                    <h3
                                                        id="modal-del-cargo-{{ $cargo->id }}"
                                                        class="text-lg font-semibold text-gray-900 dark:text-white">
                                                        Confirmar eliminación
                                                    </h3>
                                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                        ¿Está seguro de que desea eliminar el cargo
                                                        <strong class="text-gray-900 dark:text-white">{{ $cargo->name }}</strong>?
                                                        Esta acción no se puede deshacer.
                                                    </p>
                                                    <div class="mt-5 flex justify-end gap-3">
                                                        <button
                                                            @click="confirmar = false"
                                                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                            Cancelar
                                                        </button>
                                                        <form method="POST" action="{{ route('rh.cargos.destroy', $cargo) }}" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                                                Sí, eliminar
                                                            </button>
                                                        </form>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                                    </svg>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No se encontraron cargos.</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">Cree el primer cargo usando el botón superior.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if(isset($cargos) && method_exists($cargos, 'hasPages') && $cargos->hasPages())
            <div class="mt-4">
                {{ $cargos->links() }}
            </div>
        @endif

        {{-- Modal crear/editar cargo --}}
        <div
            x-show="modalAbierto"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="modalAbierto = false"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="modoEdicion ? 'modal-editar-cargo' : 'modal-crear-cargo'">

            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3
                    :id="modoEdicion ? 'modal-editar-cargo' : 'modal-crear-cargo'"
                    class="text-lg font-semibold text-gray-900 dark:text-white"
                    x-text="modoEdicion ? 'Editar cargo' : 'Nuevo cargo'">
                </h3>

                <form
                    :action="modoEdicion ? '{{ url('rh/cargos') }}/' + cargo.id : '{{ route('rh.cargos.store') }}'"
                    method="POST"
                    class="mt-4 space-y-4"
                    novalidate>
                    @csrf
                    <template x-if="modoEdicion">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    {{-- Nombre --}}
                    <div class="space-y-1.5">
                        <label for="modal-cargo-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nombre del cargo <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="modal-cargo-nombre"
                            name="name"
                            x-model="cargo.nombre"
                            placeholder="Ej: Analista de sistemas"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                        />
                        @error('name')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Departamento --}}
                    <div class="space-y-1.5">
                        <label for="modal-cargo-departamento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Departamento <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select
                            id="modal-cargo-departamento"
                            name="department_id"
                            x-model="cargo.department_id"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Seleccione el departamento</option>
                            @foreach($departamentos ?? [] as $dep)
                                <option value="{{ $dep->id }}">{{ $dep->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Descripción --}}
                    <div class="space-y-1.5">
                        <label for="modal-cargo-descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Descripción
                            <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(opcional)</span>
                        </label>
                        <textarea
                            id="modal-cargo-descripcion"
                            name="description"
                            x-model="cargo.descripcion"
                            rows="3"
                            placeholder="Descripción de las funciones del cargo..."
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                        ></textarea>
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="modalAbierto = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
