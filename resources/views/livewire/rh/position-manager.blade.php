<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\RH\Position;
use App\Models\RH\Department;
use App\Services\RH\PositionService;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    // ── Filtros y búsqueda ───────────────────────────────────────────────────
    public string $search = '';
    public string $departmentFilter = '';

    // ── Estado del modal ─────────────────────────────────────────────────────
    public bool $isOpen = false;
    public ?string $positionId = null;

    // ── Datos del formulario ─────────────────────────────────────────────────
    public string $name = '';
    public string $code = '';
    public string $departmentId = '';
    public string $description = '';
    public bool $isActive = true;
    
    public array $emails = [];
    public array $functions = [];

    // ── Datos auxiliares ─────────────────────────────────────────────────────
    public function with(): array
    {
        return [
            'positions' => Position::query()
                ->with(['department', 'emails', 'functions'])
                ->byInstitution(auth()->user()->institution_id)
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
                ->orderBy('name')
                ->paginate(10),
            'departments' => Department::byInstitution(auth()->user()->institution_id)->orderBy('name')->get(),
        ];
    }

    // ── Acciones ─────────────────────────────────────────────────────────────
    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset(['positionId', 'name', 'code', 'departmentId', 'description', 'isActive', 'emails', 'functions']);
        $this->isOpen = true;
    }

    public function openEditModal(string $id): void
    {
        $this->resetValidation();
        $position = Position::with(['emails', 'functions'])->findOrFail($id);
        
        $this->positionId = $position->id;
        $this->name = $position->name;
        $this->code = $position->code ?? '';
        $this->departmentId = $position->department_id;
        $this->description = $position->description ?? '';
        $this->isActive = (bool) $position->is_active;
        
        $this->emails = $position->emails->pluck('email')->toArray();
        $this->functions = $position->functions->pluck('description')->toArray();
        
        $this->isOpen = true;
    }

    public function addEmail(): void
    {
        $this->emails[] = '';
    }

    public function removeEmail(int $index): void
    {
        unset($this->emails[$index]);
        $this->emails = array_values($this->emails);
    }

    public function addFunction(): void
    {
        $this->functions[] = '';
    }

    public function removeFunction(int $index): void
    {
        unset($this->functions[$index]);
        $this->functions = array_values($this->functions);
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'departmentId' => 'required|uuid|exists:departments,id',
            'description' => 'nullable|string',
            'isActive' => 'boolean',
            'emails.*' => 'required|email|max:150',
            'functions.*' => 'required|string',
        ];

        $this->validate($rules);

        $data = [
            'institution_id' => auth()->user()->institution_id,
            'department_id' => $this->departmentId,
            'name' => $this->name,
            'code' => $this->code ?: null,
            'description' => $this->description ?: null,
            'is_active' => $this->isActive,
            'emails' => $this->emails,
            'functions' => $this->functions,
        ];

        // Usamos el servicio para persistir
        $request = new \App\Http\Requests\RH\CreatePositionRequest($data);
        // Simulamos el objeto request para el servicio o inyectamos manualmente
        // Pero el servicio actual recibe el objeto Request de Laravel. 
        // Por simplicidad en Volt, llamaremos a la lógica directamente o refactorizaremos el servicio.
        
        $service = app(PositionService::class);

        if ($this->positionId) {
            $position = Position::findOrFail($this->positionId);
            // El servicio espera UpdatePositionRequest
            $position->update([
                'department_id' => $this->departmentId,
                'name' => $this->name,
                'code' => $this->code ?: null,
                'description' => $this->description ?: null,
                'is_active' => $this->isActive,
            ]);
            
            // Sincronizar emails
            $position->emails()->delete();
            foreach ($this->emails as $email) {
                $position->emails()->create(['email' => $email]);
            }
            
            // Sincronizar funciones
            $position->functions()->delete();
            foreach ($this->functions as $func) {
                $position->functions()->create(['description' => $func]);
            }
            
            $this->dispatch('notify', type: 'success', message: 'Cargo actualizado correctamente.');
        } else {
            $position = Position::create([
                'institution_id' => auth()->user()->institution_id,
                'department_id' => $this->departmentId,
                'name' => $this->name,
                'code' => $this->code ?: null,
                'description' => $this->description ?: null,
                'is_active' => $this->isActive,
            ]);
            
            foreach ($this->emails as $email) {
                $position->emails()->create(['email' => $email]);
            }
            
            foreach ($this->functions as $func) {
                $position->functions()->create(['description' => $func]);
            }
            
            $this->dispatch('notify', type: 'success', message: 'Cargo registrado correctamente.');
        }

        $this->isOpen = false;
    }

    public function toggleStatus(string $id): void
    {
        $position = Position::findOrFail($id);
        $position->is_active = !$position->is_active;
        $position->save();
        $this->dispatch('notify', type: 'success', message: 'Estado del cargo actualizado.');
    }
};
?>

<div class="space-y-6">
    {{-- Barra de Herramientas --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 items-center gap-3">
            <div class="relative w-full max-w-sm">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar cargo o código..."
                    class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            
            <select wire:model.live="departmentFilter" class="rounded-lg border border-gray-300 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Todos los departamentos</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <button wire:click="openCreateModal" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Cargo
        </button>
    </div>

    {{-- Tabla de Cargos --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                <tr>
                    <th class="px-6 py-4">Cargo / Código</th>
                    <th class="px-6 py-4">Departamento</th>
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
                            <div class="text-xs text-gray-500">{{ $pos->code ?: 'Sin código' }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                            {{ $pos->department->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    {{ $pos->emails->count() }} correos
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    {{ $pos->functions->count() }} funciones
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="toggleStatus('{{ $pos->id }}')" class="focus:outline-none">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pos->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $pos->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="openEditModal('{{ $pos->id }}')" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron cargos.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $positions->links() }}
        </div>
    </div>

    {{-- Modal de Crear / Editar --}}
    @if($isOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('isOpen', false)"></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                        {{ $positionId ? 'Editar Cargo' : 'Nuevo Cargo' }}
                    </h3>
                    <button wire:click="$set('isOpen', false)" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Cargo *</label>
                            <input wire:model="name" type="text" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código</label>
                            <input wire:model="code" type="text" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Departamento *</label>
                            <select wire:model="departmentId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccione...</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('departmentId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                        <textarea wire:model="description" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>

                    {{-- Repetidor de Correos --}}
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Correos Informativos</label>
                            <button wire:click="addEmail" type="button" class="text-xs font-medium text-blue-600 hover:text-blue-500">+ Agregar Correo</button>
                        </div>
                        <div class="space-y-2">
                            @foreach($emails as $index => $email)
                                <div class="flex items-center gap-2">
                                    <input wire:model="emails.{{ $index }}" type="email" placeholder="ejemplo@ascun.org.co"
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <button wire:click="removeEmail({{ $index }})" class="text-red-500 hover:text-red-700">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                @error("emails.{$index}") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            @endforeach
                        </div>
                    </div>

                    {{-- Repetidor de Funciones --}}
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Funciones del Cargo</label>
                            <button wire:click="addFunction" type="button" class="text-xs font-medium text-blue-600 hover:text-blue-500">+ Agregar Función</button>
                        </div>
                        <div class="space-y-2">
                            @foreach($functions as $index => $func)
                                <div class="flex items-start gap-2">
                                    <textarea wire:model="functions.{{ $index }}" rows="2" placeholder="Describa la función..."
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                    <button wire:click="removeFunction({{ $index }})" class="mt-2 text-red-500 hover:text-red-700">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                @error("functions.{$index}") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                    <button wire:click="$set('isOpen', false)" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button wire:click="save" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        {{ $positionId ? 'Guardar Cambios' : 'Registrar Cargo' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
