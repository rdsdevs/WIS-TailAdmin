<?php

declare(strict_types=1);

use App\Models\RH\Collaborator;
use Livewire\Component;

new class extends Component {

    public string $collaboratorId = '';
    public string $collaboratorType = '';

    public function getContratosProperty(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();

        $query = Collaborator::query()->where('id', $this->collaboratorId);

        if (! $user->hasRole('super-admin')) {
            $query->where('institution_id', $user->institution_id);
        }

        $collaborator = $query->firstOrFail();

        return $collaborator->contracts()
            ->with(['contractType', 'position.department', 'extensions'])
            ->orderByDesc('start_date')
            ->get();
    }
};

?>

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
     x-data="{ filtro: 'todos' }">

    {{-- Encabezado con contador + chips de filtro --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700">
        <div class="flex items-center gap-2">
            <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Historial laboral</h4>
            @if($this->contratos->isNotEmpty())
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    {{ $this->contratos->count() }} contrato{{ $this->contratos->count() !== 1 ? 's' : '' }}
                </span>
            @endif
        </div>

        {{-- Filtros Alpine (solo si hay contratos) --}}
        @if($this->contratos->isNotEmpty())
        <div class="flex divide-x divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700 text-xs">
            @foreach(['todos' => 'Todos', 'vigentes' => 'Vigentes', 'terminados' => 'Terminados', 'liquidados' => 'Liquidados'] as $val => $label)
            <button type="button"
                    @click="filtro = '{{ $val }}'"
                    :class="filtro === '{{ $val }}'
                        ? 'bg-gray-100 text-gray-900 font-semibold dark:bg-gray-700 dark:text-white'
                        : 'bg-white text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700/60'"
                    class="px-3 py-1.5 transition-colors">
                {{ $label }}
            </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Estado vacío --}}
    @if($this->contratos->isEmpty())
        <div class="flex flex-col items-center gap-3 px-5 py-12 text-center">
            <svg class="h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <p class="text-sm text-gray-400 dark:text-gray-500">Sin historial de contratos registrado.</p>
        </div>
    @else
        <div class="px-5 py-4 space-y-0">
            @foreach($this->contratos as $contrato)
                @php
                    $esVigente    = $contrato->status === 'Vigente';
                    $esBorrador   = $contrato->status === 'Borrador';
                    $esLiquidado  = in_array($contrato->status, ['Liquidado', 'Vencido']);
                    $esTerm       = $contrato->status === 'Terminado';
                    $esTemprano   = $esTerm && !is_null($contrato->early_termination_date ?? null);

                    // Por vencer: vigente con end_date dentro de 60 días
                    $esPorVencer = $esVigente
                        && $contrato->end_date
                        && $contrato->end_date->diffInDays(now()) <= 60
                        && $contrato->end_date->isFuture();

                    // Estado semántico final
                    $estado = match(true) {
                        $esVigente && $esPorVencer => 'por_vencer',
                        $esVigente                 => 'vigente',
                        $esTemprano                => 'terminado_anticipado',
                        $esTerm                    => 'terminado',
                        $esLiquidado               => 'liquidado',
                        $esBorrador                => 'borrador',
                        default                    => 'otro',
                    };

                    // Paleta por estado
                    $nodoBg = match($estado) {
                        'vigente'              => 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-700',
                        'por_vencer'           => 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-700',
                        'terminado_anticipado' => 'bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-700',
                        'terminado'            => 'bg-gray-100 border-gray-200 dark:bg-gray-800 dark:border-gray-600',
                        'liquidado'            => 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700',
                        default                => 'bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-600',
                    };

                    $nodoColor = match($estado) {
                        'vigente'              => 'text-emerald-600 dark:text-emerald-400',
                        'por_vencer'           => 'text-amber-500 dark:text-amber-400',
                        'terminado_anticipado' => 'text-orange-500 dark:text-orange-400',
                        'terminado'            => 'text-gray-400 dark:text-gray-500',
                        'liquidado'            => 'text-blue-500 dark:text-blue-400',
                        default                => 'text-gray-400 dark:text-gray-500',
                    };

                    $lineaColor = match($estado) {
                        'vigente'    => 'border-emerald-200 dark:border-emerald-800',
                        'por_vencer' => 'border-amber-200 dark:border-amber-800',
                        default      => 'border-gray-200 dark:border-gray-700',
                    };

                    $badgeClass = match($estado) {
                        'vigente'              => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                        'por_vencer'           => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        'terminado_anticipado' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        'terminado'            => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                        'liquidado'            => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        default                => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                    };

                    $badgeLabel = match($estado) {
                        'vigente'              => 'Vigente',
                        'por_vencer'           => 'Por vencer',
                        'terminado_anticipado' => 'Terminado anticipado',
                        'terminado'            => 'Terminado',
                        'liquidado'            => $contrato->status,
                        default                => $contrato->status ?? '—',
                    };

                    $isLast = $loop->last;

                    // Valor monetario correcto (evita bug decimal:2 truthy)
                    $tieneSalario   = (float)($contrato->salary ?? 0) > 0;
                    $tieneHonorario = (float)($contrato->fees   ?? 0) > 0;

                    // Alpine filtro key
                    $filtroKey = match($estado) {
                        'vigente', 'por_vencer'              => 'vigentes',
                        'terminado', 'terminado_anticipado'  => 'terminados',
                        'liquidado'                          => 'liquidados',
                        default                              => 'todos',
                    };
                @endphp

                {{-- Item filtrable con Alpine --}}
                <div x-show="filtro === 'todos' || filtro === '{{ $filtroKey }}'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-data="{ open: {{ $esVigente || $esPorVencer ? 'true' : 'false' }} }"
                     class="flex gap-4">

                    {{-- Columna izquierda: nodo + línea --}}
                    <div class="flex flex-col items-center pt-0.5">

                        {{-- Nodo con ping en vigente --}}
                        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full border {{ $nodoBg }}">

                            @if($esVigente && !$esPorVencer)
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-20"></span>
                            @endif

                            {{-- Icono según estado --}}
                            @if($esVigente && !$esPorVencer)
                                <svg class="h-5 w-5 {{ $nodoColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            @elseif($esPorVencer)
                                <svg class="h-5 w-5 {{ $nodoColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            @elseif($esTemprano)
                                <svg class="h-5 w-5 {{ $nodoColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            @elseif($esLiquidado)
                                <svg class="h-5 w-5 {{ $nodoColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                                </svg>
                            @elseif($esBorrador)
                                <svg class="h-5 w-5 {{ $nodoColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/>
                                </svg>
                            @else
                                <svg class="h-5 w-5 {{ $nodoColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13.5H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Línea conectora --}}
                        @unless($isLast)
                            <div class="my-1 w-0.5 flex-1 border-l-2 {{ $lineaColor }}" aria-hidden="true"></div>
                        @endunless
                    </div>

                    {{-- Contenido del item --}}
                    <div class="min-w-0 flex-1 pb-5">

                        {{-- Cabecera siempre visible (clickeable para expandir) --}}
                        <button type="button"
                                @click="open = !open"
                                class="flex w-full items-start justify-between gap-2 text-left">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                        {{ $contrato->contractType?->name ?? 'Sin tipo' }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                        {{ $badgeLabel }}
                                    </span>
                                    @if($contrato->contract_code)
                                        <span class="font-mono text-xs text-gray-400 dark:text-gray-500">
                                            {{ $contrato->contract_code }}
                                        </span>
                                    @endif
                                </div>

                                @if($contrato->position)
                                    <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $contrato->position->name }}
                                        @if($contrato->position->department)
                                            &bull; {{ $contrato->position->department->name }}
                                        @endif
                                    </p>
                                @endif
                            </div>

                            {{-- Fechas + chevron --}}
                            <div class="flex shrink-0 items-center gap-3">
                                <div class="text-right">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                        <span class="text-gray-400 dark:text-gray-500">Inicio </span>{{ $contrato->start_date?->format('d/m/Y') ?? '—' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                        <span>Fin </span>{{ $contrato->end_date ? $contrato->end_date->format('d/m/Y') : 'Indefinido' }}
                                    </p>
                                </div>
                                <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200"
                                     :class="open ? 'rotate-180' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                </svg>
                            </div>
                        </button>

                        {{-- Cuerpo expandible --}}
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="mt-3 space-y-2 rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/50">

                            {{-- Alerta por vencer --}}
                            @if($esPorVencer)
                                <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                    </svg>
                                    Vence en {{ abs((int) now()->diffInDays($contrato->end_date, false)) }} días ({{ $contrato->end_date->format('d/m/Y') }})
                                </div>
                            @endif

                            {{-- Valor económico --}}
                            @if($collaboratorType !== 'Contratista' && $tieneSalario)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Salario</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">$ {{ number_format((float) $contrato->salary, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($collaboratorType === 'Contratista' && $tieneHonorario)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Honorarios</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">$ {{ number_format((float) $contrato->fees, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            {{-- Terminación anticipada --}}
                            @if($esTemprano && ($contrato->early_termination_date ?? null))
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-orange-600 dark:text-orange-400">Terminación anticipada</span>
                                    <span class="font-medium text-orange-700 dark:text-orange-300">{{ $contrato->early_termination_date->format('d/m/Y') }}</span>
                                </div>
                            @endif

                            {{-- Prórrogas --}}
                            @if($contrato->extensions->count() > 0)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $contrato->extensions->count() }} prórroga{{ $contrato->extensions->count() !== 1 ? 's' : '' }}</span>
                                    <span class="font-medium text-gray-600 dark:text-gray-300">
                                        hasta {{ $contrato->extensions->sortByDesc('new_end_date')->first()->new_end_date?->format('d/m/Y') ?? '—' }}
                                    </span>
                                </div>
                            @endif

                            {{-- Barra de acciones: Button Group Icon + Tooltip --}}
                            <div class="flex items-center gap-1.5 pt-1">

                                {{-- Ver detalle --}}
                                <div class="relative group">
                                    <a href="{{ route('rh.contratos.show', $contrato) }}"
                                       class="flex items-center justify-center rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                       aria-label="Ver contrato">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                    </a>
                                    <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                                        Ver contrato
                                        <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                    </div>
                                </div>

                                {{-- Editar (Warning ámbar) --}}
                                @can('update', $contrato)
                                    <div class="relative group">
                                        <a href="{{ route('rh.contratos.edit', $contrato) }}"
                                           class="flex items-center justify-center rounded-lg p-1.5 text-amber-500 transition-colors hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                           aria-label="Editar contrato">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                            </svg>
                                        </a>
                                        <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                                            Editar
                                            <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                        </div>
                                    </div>
                                @endcan

                                {{-- Certificado laboral (deshabilitado por ahora) --}}
                                @if(!$esBorrador)
                                    <div class="relative group">
                                        <button type="button" disabled
                                                class="flex cursor-not-allowed items-center justify-center rounded-lg p-1.5 text-blue-300 opacity-50 dark:text-blue-600"
                                                aria-label="Certificado laboral (próximamente)">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                            </svg>
                                        </button>
                                        <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                                            Certificado (próximamente)
                                            <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
