<?php

use Livewire\Component;
use App\Helpers\MenuHelper;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;

new class extends Component {
    public bool $abierto = false;
    public string $busqueda = '';
    public array $navItems = [];
    public array $colaboradores = [];
    public array $contratos = [];
    public array $auditoria = [];

    public function mount(): void
    {
        $grupos = MenuHelper::getMenuGroups();
        $items = [];
        foreach ($grupos as $grupo) {
            foreach ($grupo['items'] as $item) {
                if (isset($item['path'])) {
                    $items[] = ['nombre' => $item['name'], 'ruta' => $item['path']];
                }
                if (isset($item['subItems'])) {
                    foreach ($item['subItems'] as $sub) {
                        $items[] = ['nombre' => $grupo['title'] . ' → ' . $sub['name'], 'ruta' => $sub['path']];
                    }
                }
            }
        }
        $this->navItems = $items;
    }

    public function abrir(): void
    {
        $this->abierto = true;
        $this->busqueda = '';
        $this->colaboradores = [];
        $this->contratos = [];
        $this->auditoria = [];
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->busqueda = '';
    }

    public function updatedBusqueda(): void
    {
        if (strlen($this->busqueda) < 2) {
            $this->colaboradores = [];
            $this->contratos = [];
            $this->auditoria = [];
            return;
        }
        $this->buscarColaboradores();
        $this->buscarContratos();
        if (auth()->user()->hasRole(['super-admin', 'admin'])) {
            $this->buscarAuditoria();
        }
    }

    private function buscarColaboradores(): void
    {
        $q = $this->busqueda;
        $institutionId = auth()->user()->institution_id;
        $isSuperAdmin = auth()->user()->hasRole('super-admin');

        $query = Collaborator::query()
            ->when(! $isSuperAdmin, fn ($q2) => $q2->where('institution_id', $institutionId))
            ->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$q}%")
                ->orWhere('first_surname', 'like', "%{$q}%")
                ->orWhere('company_name', 'like', "%{$q}%")
                ->orWhere('document_number', 'like', "%{$q}%")
            )
            ->limit(5)
            ->get();

        $this->colaboradores = $query->map(fn ($c) => [
            'nombre' => $c->full_name,
            'sub'    => $c->document_number ?? $c->company_name,
            'ruta'   => route('rh.colaboradores.show', $c->id),
        ])->toArray();
    }

    private function buscarContratos(): void
    {
        $q = $this->busqueda;
        $isSuperAdmin = auth()->user()->hasRole('super-admin');
        $institutionId = auth()->user()->institution_id;

        $query = Contract::query()
            ->with('collaborator:id,first_name,first_surname,company_name,is_company')
            ->when(! $isSuperAdmin, fn ($q2) => $q2->where('institution_id', $institutionId))
            ->where(fn ($q2) => $q2
                ->where('contract_number', 'like', "%{$q}%")
                ->orWhere('object', 'like', "%{$q}%")
            )
            ->limit(5)
            ->get();

        $this->contratos = $query->map(fn ($c) => [
            'nombre' => $c->contract_number ?? 'Sin número',
            'sub'    => Str::limit($c->object ?? '', 60),
            'extra'  => $c->collaborator?->full_name,
            'ruta'   => route('rh.contratos.show', $c->id),
        ])->toArray();
    }

    private function buscarAuditoria(): void
    {
        $q            = $this->busqueda;
        $isSuperAdmin = auth()->user()->hasRole('super-admin');
        $institutionId = auth()->user()->institution_id;

        $collaboratorIds = $isSuperAdmin ? null
            : \App\Models\RH\Collaborator::where('institution_id', $institutionId)->pluck('id');

        $contractIds = $isSuperAdmin ? null
            : \App\Models\RH\Contract::where('institution_id', $institutionId)->pluck('id');

        $this->auditoria = Audit::query()
            ->where(fn ($q2) => $q2
                ->where('auditable_type', 'like', "%{$q}%")
                ->orWhere('event', 'like', "%{$q}%")
            )
            ->when(! $isSuperAdmin, fn ($q2) => $q2->where(fn ($q3) => $q3
                ->where(fn ($q4) => $q4
                    ->where('auditable_type', 'like', '%Collaborator%')
                    ->whereIn('auditable_id', $collaboratorIds ?? [])
                )
                ->orWhere(fn ($q4) => $q4
                    ->where('auditable_type', 'like', '%Contract%')
                    ->whereIn('auditable_id', $contractIds ?? [])
                )
                ->orWhere('user_id', auth()->id())
            ))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'nombre' => class_basename($a->auditable_type),
                'sub'    => $a->event . ' · ' . $a->created_at->diffForHumans(),
                'ruta'   => '#',
            ])->toArray();
    }

    public function getNavFiltradosProperty(): array
    {
        if (strlen($this->busqueda) < 1) {
            return $this->navItems;
        }
        return array_values(array_filter(
            $this->navItems,
            fn ($item) => str_contains(strtolower($item['nombre']), strtolower($this->busqueda))
        ));
    }
};
?>

<div>
    {{-- Solo renderizar el panel cuando está abierto --}}
    @if($this->abierto)
        <div
            class="fixed inset-0 z-[9999] flex items-start justify-center pt-20 px-4 sm:pt-28"
            role="dialog"
            aria-modal="true"
            aria-labelledby="command-palette-title"
            @abrir-palette.window="$wire.abrir()"
            @keydown.escape.window="$wire.cerrar()">

            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                @click="$wire.cerrar()"
                aria-hidden="true">
            </div>

            {{-- Panel --}}
            <div class="relative w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10">

                {{-- Input de búsqueda --}}
                <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3.5 dark:border-gray-700">
                    <svg class="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" fill="currentColor"/>
                    </svg>
                    <input
                        wire:model.live.debounce.300ms="busqueda"
                        type="search"
                        id="command-palette-title"
                        placeholder="Buscar páginas, colaboradores, contratos..."
                        aria-label="Buscar en el sistema"
                        autocomplete="off"
                        x-init="$nextTick(() => $el.focus())"
                        class="flex-1 bg-transparent text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none dark:text-white dark:placeholder:text-gray-500"
                    />
                    <button
                        type="button"
                        wire:click="cerrar"
                        class="rounded border border-gray-200 bg-gray-50 px-1.5 py-1 text-xs text-gray-500 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        aria-label="Cerrar command palette">
                        Esc
                    </button>
                </div>

                {{-- Indicador de carga --}}
                <div wire:loading wire:target="busqueda" class="border-b border-blue-100 bg-blue-50 px-4 py-2 dark:border-blue-900/30 dark:bg-blue-900/10">
                    <div class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400">
                        <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Buscando...
                    </div>
                </div>

                {{-- Resultados --}}
                <div class="max-h-[420px] overflow-y-auto" wire:loading.remove wire:target="busqueda">

                    @php
                        $navFiltrados = $this->navFiltrados;
                        $hayResultados = count($navFiltrados) > 0
                            || count($this->colaboradores) > 0
                            || count($this->contratos) > 0
                            || count($this->auditoria) > 0;
                    @endphp

                    @if(strlen($this->busqueda) >= 2 && !$hayResultados)
                        {{-- Sin resultados --}}
                        <div class="flex flex-col items-center gap-2 px-4 py-10 text-center">
                            <svg class="h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sin resultados para tu búsqueda</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Intente con otro término</p>
                        </div>
                    @else

                        {{-- Sección: Navegación --}}
                        @if(count($navFiltrados) > 0)
                            <div>
                                <div class="sticky top-0 bg-gray-50 px-4 py-2 dark:bg-gray-800/80">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Navegación</p>
                                </div>
                                <ul role="listbox" aria-label="Páginas">
                                    @foreach($navFiltrados as $item)
                                        <li role="option">
                                            <a href="{{ $item['ruta'] }}"
                                               wire:click="cerrar"
                                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800/60">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500" aria-hidden="true">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                    </svg>
                                                </span>
                                                <span class="flex-1 truncate">{{ $item['nombre'] }}</span>
                                                <span class="text-xs text-gray-400 dark:text-gray-600">{{ $item['ruta'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Sección: Colaboradores --}}
                        @if(count($this->colaboradores) > 0)
                            <div class="border-t border-gray-100 dark:border-gray-700/50">
                                <div class="sticky top-0 bg-gray-50 px-4 py-2 dark:bg-gray-800/80">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Colaboradores</p>
                                </div>
                                <ul role="listbox" aria-label="Colaboradores">
                                    @foreach($this->colaboradores as $item)
                                        <li role="option">
                                            <a href="{{ $item['ruta'] }}"
                                               wire:click="cerrar"
                                               class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-xs font-semibold" aria-hidden="true">
                                                    {{ strtoupper(substr($item['nombre'], 0, 2)) }}
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white">{{ $item['nombre'] }}</p>
                                                    @if($item['sub'])
                                                        <p class="truncate text-xs text-gray-400 dark:text-gray-500">{{ $item['sub'] }}</p>
                                                    @endif
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Sección: Contratos --}}
                        @if(count($this->contratos) > 0)
                            <div class="border-t border-gray-100 dark:border-gray-700/50">
                                <div class="sticky top-0 bg-gray-50 px-4 py-2 dark:bg-gray-800/80">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Contratos</p>
                                </div>
                                <ul role="listbox" aria-label="Contratos">
                                    @foreach($this->contratos as $item)
                                        <li role="option">
                                            <a href="{{ $item['ruta'] }}"
                                               wire:click="cerrar"
                                               class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500" aria-hidden="true">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                    </svg>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $item['nombre'] }}</p>
                                                    @if($item['sub'])
                                                        <p class="truncate text-xs text-gray-400 dark:text-gray-500">{{ $item['sub'] }}</p>
                                                    @endif
                                                    @if(!empty($item['extra']))
                                                        <p class="text-xs text-blue-500 dark:text-blue-400">{{ $item['extra'] }}</p>
                                                    @endif
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Sección: Auditoría --}}
                        @if(count($this->auditoria) > 0)
                            <div class="border-t border-gray-100 dark:border-gray-700/50">
                                <div class="sticky top-0 bg-gray-50 px-4 py-2 dark:bg-gray-800/80">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Auditoría</p>
                                </div>
                                <ul role="listbox" aria-label="Registros de auditoría">
                                    @foreach($this->auditoria as $item)
                                        <li role="option">
                                            <a href="{{ $item['ruta'] }}"
                                               wire:click="cerrar"
                                               class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500" aria-hidden="true">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $item['nombre'] }}</p>
                                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $item['sub'] }}</p>
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Estado vacío inicial --}}
                        @if(strlen($this->busqueda) < 1 && count($navFiltrados) === 0)
                            <div class="flex flex-col items-center gap-2 px-4 py-10 text-center">
                                <svg class="h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Escribe para buscar...</p>
                            </div>
                        @endif

                    @endif
                </div>

                {{-- Pie con atajos --}}
                <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50/80 px-4 py-2.5 dark:border-gray-700 dark:bg-gray-800/50">
                    <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                        <span class="flex items-center gap-1">
                            <kbd class="inline-flex items-center rounded border border-gray-300 bg-white px-1 py-0.5 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">↑↓</kbd>
                            navegar
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="inline-flex items-center rounded border border-gray-300 bg-white px-1 py-0.5 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">Enter</kbd>
                            seleccionar
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="inline-flex items-center rounded border border-gray-300 bg-white px-1 py-0.5 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">Esc</kbd>
                            cerrar
                        </span>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">WIS ASCUN</span>
                </div>
            </div>
        </div>
    @else
        {{-- Escucha para abrir, incluso cuando el panel está cerrado --}}
        <div
            @abrir-palette.window="$wire.abrir()"
            @keydown.escape.window="$wire.cerrar()"
            style="display:none"
            aria-hidden="true">
        </div>
    @endif
</div>
